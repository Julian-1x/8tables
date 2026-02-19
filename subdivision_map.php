<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: Login/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$error = "";
$success = "";

function getFixedHouseNumbers(): array
{
    $houses = [];
    for ($block = 1; $block <= 5; $block++) {
        for ($lot = 1; $lot <= 6; $lot++) {
            $houses[] = sprintf('Block %02d Lot %02d', $block, $lot);
        }
    }
    return $houses;
}

function getFixedEmptyLandNumbers(): array
{
    $lands = [];
    for ($lot = 1; $lot <= 5; $lot++) {
        $lands[] = sprintf('Block 06 Lot %02d', $lot);
    }
    return $lands;
}

$fixedHouseNumbers = getFixedHouseNumbers();
$fixedEmptyLandNumbers = getFixedEmptyLandNumbers();
$allFixedLotNumbers = array_merge($fixedHouseNumbers, $fixedEmptyLandNumbers);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lot_owner'])) {
    $targetLot = trim((string) ($_POST['target_lot'] ?? ''));
    $ownerName = trim((string) ($_POST['owner_name'] ?? ''));

    if (!in_array($targetLot, $allFixedLotNumbers, true)) {
        $error = "Invalid lot selected.";
    } else {
        try {
            $checkStmt = $db->prepare("SELECT id FROM houses WHERE is_deleted = 0 AND house_number = :house_number LIMIT 1");
            $checkStmt->execute([':house_number' => $targetLot]);
            $houseId = $checkStmt->fetchColumn();

            if ($houseId) {
                $status = $ownerName !== '' ? 'Occupied' : 'Vacant';
                $updateStmt = $db->prepare(
                    "UPDATE houses
                     SET owner_name = :owner_name, status = :status, updated_at = NOW()
                     WHERE id = :id"
                );
                $updateStmt->execute([
                    ':owner_name' => $ownerName,
                    ':status' => $status,
                    ':id' => $houseId
                ]);
            } else {
                if ($ownerName === '') {
                    $error = "Owner name is required when assigning a new house to this lot.";
                } else {
                    $insertStmt = $db->prepare(
                        "INSERT INTO houses (house_number, owner_name, status, created_at)
                         VALUES (:house_number, :owner_name, 'Occupied', NOW())"
                    );
                    $insertStmt->execute([
                        ':house_number' => $targetLot,
                        ':owner_name' => $ownerName
                    ]);
                }
            }

            if ($error === "") {
                addAuditLog(
                    $db,
                    $_SESSION['user_id'],
                    'ASSIGN_OWNER_FROM_MAP',
                    "Updated owner assignment from subdivision map for $targetLot"
                );
                header("Location: subdivision_map.php?lot=" . urlencode($targetLot) . "&message=owner_saved");
                exit();
            }
        } catch (PDOException $exception) {
            $error = "Failed to save owner assignment.";
            error_log("Subdivision map save owner error: " . $exception->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['build_empty_land'])) {
    $targetLot = trim((string) ($_POST['target_lot'] ?? ''));
    $clientName = trim((string) ($_POST['client_name'] ?? ''));

    if (!in_array($targetLot, $fixedEmptyLandNumbers, true)) {
        $error = "Invalid empty land lot selected.";
    } elseif ($clientName === '') {
        $error = "Client name is required to build on empty land.";
    } else {
        try {
            $checkStmt = $db->prepare("SELECT id FROM houses WHERE is_deleted = 0 AND house_number = :house_number LIMIT 1");
            $checkStmt->execute([':house_number' => $targetLot]);
            $existingHouseId = $checkStmt->fetchColumn();

            if ($existingHouseId) {
                $error = "This lot is already built.";
            } else {
                $insertStmt = $db->prepare(
                    "INSERT INTO houses (house_number, owner_name, status, created_at)
                     VALUES (:house_number, :owner_name, :status, NOW())"
                );
                $insertStmt->execute([
                    ':house_number' => $targetLot,
                    ':owner_name' => $clientName,
                    ':status' => 'Occupied'
                ]);

                addAuditLog(
                    $db,
                    $_SESSION['user_id'],
                    'BUILD_EMPTY_LAND',
                    "Built new house on $targetLot for client $clientName"
                );

                header("Location: subdivision_map.php?lot=" . urlencode($targetLot) . "&message=built");
                exit();
            }
        } catch (PDOException $exception) {
            $error = "Failed to build on selected empty land.";
            error_log("Build empty land error: " . $exception->getMessage());
        }
    }
}

if (isset($_GET['message']) && $_GET['message'] === 'built') {
    $success = "New house successfully created from empty land.";
} elseif (isset($_GET['message']) && $_GET['message'] === 'owner_saved') {
    $success = "Lot owner assignment updated.";
}

$houseMap = [];
try {
    $placeholders = implode(',', array_fill(0, count($allFixedLotNumbers), '?'));
    $query = "SELECT house_number, owner_name, status
              FROM houses
              WHERE is_deleted = 0 AND house_number IN ($placeholders)";
    $stmt = $db->prepare($query);
    $stmt->execute($allFixedLotNumbers);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $row) {
        $houseMap[$row['house_number']] = $row;
    }
} catch (PDOException $exception) {
    $error = "Error loading subdivision map data.";
    error_log("Subdivision map load error: " . $exception->getMessage());
}

$slots = [];

foreach ($fixedHouseNumbers as $label) {
    $row = $houseMap[$label] ?? null;
    $ownerName = trim((string) ($row['owner_name'] ?? ''));
    $isOccupied = (($row['status'] ?? '') === 'Occupied') || $ownerName !== '';

    $slots[] = [
        'lot_label' => $label,
        'status' => $isOccupied ? 'occupied' : 'vacant',
        'subtitle' => $ownerName !== '' ? $ownerName : 'No owner assigned',
        'state_label' => $isOccupied ? 'OCCUPIED' : 'VACANT'
    ];
}

foreach ($fixedEmptyLandNumbers as $label) {
    $row = $houseMap[$label] ?? null;
    if ($row !== null) {
        $ownerName = trim((string) ($row['owner_name'] ?? ''));
        $isOccupied = (($row['status'] ?? '') === 'Occupied') || $ownerName !== '';
        $slots[] = [
            'lot_label' => $label,
            'status' => $isOccupied ? 'occupied' : 'vacant',
            'subtitle' => $ownerName !== '' ? $ownerName : 'No owner assigned',
            'state_label' => $isOccupied ? 'OCCUPIED' : 'VACANT'
        ];
    } else {
        $slots[] = [
            'lot_label' => $label,
            'status' => 'empty_land',
            'subtitle' => 'Empty land',
            'state_label' => 'TO BE BUILT'
        ];
    }
}

$selectedLot = trim((string) ($_GET['lot'] ?? ''));
$selectedSlot = null;
$selectedDetails = null;

foreach ($slots as $slot) {
    if ($slot['lot_label'] === $selectedLot) {
        $selectedSlot = $slot;
        break;
    }
}

if ($selectedSlot !== null && $selectedSlot['status'] !== 'empty_land') {
    try {
        $houseInfoQuery = "SELECT id, house_number, owner_name, status
                           FROM houses
                           WHERE is_deleted = 0 AND house_number = :house_number
                           LIMIT 1";
        $houseStmt = $db->prepare($houseInfoQuery);
        $houseStmt->execute([':house_number' => $selectedSlot['lot_label']]);
        $house = $houseStmt->fetch(PDO::FETCH_ASSOC);

        if ($house) {
            $houseId = (int) $house['id'];

            $residentsStmt = $db->prepare("SELECT COUNT(*) FROM residents WHERE is_deleted = 0 AND house_id = :house_id");
            $residentsStmt->execute([':house_id' => $houseId]);
            $residentsCount = (int) $residentsStmt->fetchColumn();

            $vehiclesStmt = $db->prepare("SELECT COUNT(*) FROM vehicles WHERE is_deleted = 0 AND house_id = :house_id");
            $vehiclesStmt->execute([':house_id' => $houseId]);
            $vehiclesCount = (int) $vehiclesStmt->fetchColumn();

            $pendingMaintenanceStmt = $db->prepare("SELECT COUNT(*) FROM maintenance_requests WHERE is_deleted = 0 AND house_id = :house_id AND status IN ('Pending', 'In Progress')");
            $pendingMaintenanceStmt->execute([':house_id' => $houseId]);
            $pendingMaintenance = (int) $pendingMaintenanceStmt->fetchColumn();

            $lastPaymentStmt = $db->prepare("SELECT amount, payment_date, status FROM payments WHERE is_deleted = 0 AND house_id = :house_id ORDER BY payment_date DESC LIMIT 1");
            $lastPaymentStmt->execute([':house_id' => $houseId]);
            $lastPayment = $lastPaymentStmt->fetch(PDO::FETCH_ASSOC);

            $selectedDetails = [
                'owner_name' => trim((string) ($house['owner_name'] ?? '')),
                'status' => (string) ($house['status'] ?? 'Vacant'),
                'residents_count' => $residentsCount,
                'vehicles_count' => $vehiclesCount,
                'pending_maintenance' => $pendingMaintenance,
                'last_payment' => $lastPayment ?: null
            ];
        }
    } catch (PDOException $exception) {
        error_log("Subdivision map details error: " . $exception->getMessage());
    }
}

$occupiedCount = 0;
$vacantCount = 0;
$emptyLandCount = 0;

foreach ($slots as $slot) {
    if ($slot['status'] === 'occupied') {
        $occupiedCount++;
    } elseif ($slot['status'] === 'vacant') {
        $vacantCount++;
    } else {
        $emptyLandCount++;
    }
}

$TOTAL_LOTS = count($slots);
$columns = 7;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subdivision Map - Subdivision Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .map-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
        }

        .summary-card .label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 6px;
        }

        .summary-card .value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .map-panel {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 20px;
        }

        .map-legend {
            display: flex;
            gap: 18px;
            margin-bottom: 16px;
            flex-wrap: wrap;
            font-size: 14px;
            color: #374151;
        }

        .legend-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .dot-occupied { background: #16a34a; }
        .dot-vacant { background: #dc2626; }
        .dot-empty-land { background: #6b7280; }

        .house-grid {
            display: grid;
            grid-template-columns: repeat(<?php echo $columns; ?>, minmax(120px, 1fr));
            gap: 12px;
        }

        .house-tile {
            border-radius: 10px;
            color: #fff;
            min-height: 94px;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .house-link {
            text-decoration: none;
            color: inherit;
            display: block;
            border-radius: 10px;
        }

        .house-link:focus-visible {
            outline: 3px solid #1d4ed8;
            outline-offset: 2px;
        }

        .house-link.selected .house-tile {
            box-shadow: 0 0 0 3px #111827, 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .house-tile.occupied { background: #16a34a; }
        .house-tile.vacant { background: #dc2626; }
        .house-tile.empty_land { background: #6b7280; }

        .house-number {
            font-size: 14px;
            font-weight: 700;
            line-height: 1.2;
        }

        .house-owner {
            font-size: 12px;
            opacity: 0.95;
            line-height: 1.25;
        }

        .house-status {
            font-size: 11px;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            opacity: 0.95;
        }

        .error-msg {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .success-msg {
            background: #ecfdf3;
            color: #166534;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .details-panel {
            margin-top: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px;
        }

        .details-title {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #0f172a;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
            gap: 10px;
        }

        .detail-item {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px;
        }

        .detail-label {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 14px;
            color: #0f172a;
            font-weight: 600;
        }

        .build-form {
            margin-top: 12px;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .build-input {
            flex: 1;
            min-width: 220px;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            padding: 9px 10px;
            font-size: 14px;
        }

        .build-btn {
            border: none;
            background: #2563eb;
            color: #fff;
            border-radius: 7px;
            padding: 9px 12px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
        }

        .build-btn:hover {
            background: #1d4ed8;
        }

        .owner-form-note {
            margin-top: 10px;
            font-size: 12px;
            color: #475569;
        }

        @media (max-width: 900px) {
            .house-grid {
                grid-template-columns: repeat(5, minmax(100px, 1fr));
            }
        }

        @media (max-width: 640px) {
            .house-grid {
                grid-template-columns: repeat(2, minmax(120px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php include("includes/header.php"); ?>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="map-summary">
        <div class="summary-card">
            <div class="label">Total Lots</div>
            <div class="value"><?php echo $TOTAL_LOTS; ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Occupied Houses</div>
            <div class="value" style="color:#16a34a;"><?php echo $occupiedCount; ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Vacant Houses</div>
            <div class="value" style="color:#dc2626;"><?php echo $vacantCount; ?></div>
        </div>
        <div class="summary-card">
            <div class="label">Empty Land</div>
            <div class="value" style="color:#6b7280;"><?php echo $emptyLandCount; ?></div>
        </div>
    </div>

    <div class="map-panel">
        <div class="map-legend">
            <span class="legend-item"><span class="legend-dot dot-occupied"></span>Occupied House</span>
            <span class="legend-item"><span class="legend-dot dot-vacant"></span>Vacant House</span>
            <span class="legend-item"><span class="legend-dot dot-empty-land"></span>Empty Land</span>
        </div>

        <div class="house-grid">
            <?php foreach ($slots as $slot): ?>
                <?php $isSelected = ($selectedLot !== '' && $selectedLot === $slot['lot_label']); ?>
                <a
                    href="?lot=<?php echo urlencode($slot['lot_label']); ?>"
                    class="house-link<?php echo $isSelected ? ' selected' : ''; ?>"
                    title="View details for <?php echo htmlspecialchars($slot['lot_label']); ?>"
                >
                    <div class="house-tile <?php echo $slot['status']; ?>">
                        <div class="house-number"><?php echo htmlspecialchars($slot['lot_label']); ?></div>
                        <div class="house-owner"><?php echo htmlspecialchars($slot['subtitle']); ?></div>
                        <div class="house-status"><?php echo htmlspecialchars($slot['state_label']); ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($selectedSlot !== null): ?>
            <div class="details-panel">
                <h3 class="details-title">Selected Lot: <?php echo htmlspecialchars($selectedSlot['lot_label']); ?></h3>

                <?php if ($selectedSlot['status'] === 'empty_land'): ?>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Type</div>
                            <div class="detail-value">Empty Land</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Development Status</div>
                            <div class="detail-value">To Be Built</div>
                        </div>
                    </div>
                    <form method="POST" class="build-form">
                        <input type="hidden" name="target_lot" value="<?php echo htmlspecialchars($selectedSlot['lot_label']); ?>">
                        <input
                            type="text"
                            name="client_name"
                            class="build-input"
                            placeholder="Client full name"
                            required
                        >
                        <button type="submit" name="build_empty_land" class="build-btn">Build House For Client</button>
                    </form>

                    <form method="POST" class="build-form">
                        <input type="hidden" name="target_lot" value="<?php echo htmlspecialchars($selectedSlot['lot_label']); ?>">
                        <input
                            type="text"
                            name="owner_name"
                            class="build-input"
                            placeholder="Quick assign owner from map"
                            required
                        >
                        <button type="submit" name="save_lot_owner" class="build-btn">Save Owner</button>
                    </form>
                <?php else: ?>
                    <div class="details-grid">
                        <div class="detail-item">
                            <div class="detail-label">Owner</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($selectedDetails['owner_name'] !== '' ? $selectedDetails['owner_name'] : 'No owner assigned'); ?>
                            </div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Status</div>
                            <div class="detail-value"><?php echo htmlspecialchars($selectedDetails['status']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Residents</div>
                            <div class="detail-value"><?php echo (int) $selectedDetails['residents_count']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Vehicles</div>
                            <div class="detail-value"><?php echo (int) $selectedDetails['vehicles_count']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Pending Maintenance</div>
                            <div class="detail-value"><?php echo (int) $selectedDetails['pending_maintenance']; ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Last Payment</div>
                            <div class="detail-value">
                                <?php
                                if (!empty($selectedDetails['last_payment'])) {
                                    $lp = $selectedDetails['last_payment'];
                                    echo 'PHP ' . number_format((float) $lp['amount'], 2) .
                                        ' on ' . date('M j, Y', strtotime($lp['payment_date'])) .
                                        ' (' . htmlspecialchars($lp['status']) . ')';
                                } else {
                                    echo 'No payment record';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <form method="POST" class="build-form">
                        <input type="hidden" name="target_lot" value="<?php echo htmlspecialchars($selectedSlot['lot_label']); ?>">
                        <input
                            type="text"
                            name="owner_name"
                            class="build-input"
                            placeholder="Update owner or leave blank to set vacant"
                            value="<?php echo htmlspecialchars($selectedDetails['owner_name'] ?? ''); ?>"
                        >
                        <button type="submit" name="save_lot_owner" class="build-btn">Save Owner</button>
                    </form>
                    <div class="owner-form-note">
                        Leave owner blank to mark this lot as vacant.
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include("includes/footer.php"); ?>
</body>
</html>
