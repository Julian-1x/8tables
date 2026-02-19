<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: Login/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success = "";
$error = "";

function getFixedHouseNumbers(): array
{
    $houses = [];
    for ($block = 1; $block <= 5; $block++) {
        for ($lot = 1; $lot <= 6; $lot++) {
            $houses[] = sprintf('Block %02d Lot %02d', $block, $lot);
        }
    }
    for ($lot = 1; $lot <= 5; $lot++) {
        $houses[] = sprintf('Block 06 Lot %02d', $lot);
    }
    return $houses;
}

$fixedHouseNumbers = getFixedHouseNumbers();

// Ensure the 30 fixed houses exist in DB.
try {
    $placeholders = implode(',', array_fill(0, count($fixedHouseNumbers), '?'));
    $stmt = $db->prepare("SELECT house_number FROM houses WHERE house_number IN ($placeholders)");
    $stmt->execute($fixedHouseNumbers);
    $existing = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    $existingMap = array_flip($existing);

    $insertQuery = "INSERT INTO houses (house_number, owner_name, status, created_at) VALUES (:house_number, :owner_name, :status, NOW())";
    $insertStmt = $db->prepare($insertQuery);

    foreach ($fixedHouseNumbers as $houseNumber) {
        if (!isset($existingMap[$houseNumber])) {
            $insertStmt->execute([
                ':house_number' => $houseNumber,
                ':owner_name' => '',
                ':status' => 'Vacant'
            ]);
        }
    }
} catch (PDOException $exception) {
    $error = "Error preparing fixed houses.";
    error_log("Fixed houses setup error: " . $exception->getMessage());
}

// Handle owner assignment update.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_owner'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $ownerName = trim($_POST['owner_name'] ?? '');
    $status = $ownerName !== '' ? 'Occupied' : 'Vacant';

    if ($id <= 0) {
        $error = "Invalid house selection.";
    } else {
        try {
            $query = "UPDATE houses
                      SET owner_name = :owner_name, status = :status, updated_at = NOW()
                      WHERE id = :id AND is_deleted = 0";
            $stmt = $db->prepare($query);
            $stmt->execute([
                ':owner_name' => $ownerName,
                ':status' => $status,
                ':id' => $id
            ]);

            if ($stmt->rowCount() > 0) {
                $success = "House owner assignment updated.";
                addAuditLog(
                    $db,
                    $_SESSION['user_id'],
                    'UPDATE_HOUSE_OWNER',
                    "Updated owner assignment for house ID: $id"
                );
            } else {
                $success = "No changes were needed.";
            }
        } catch (PDOException $exception) {
            $error = "Error updating owner assignment.";
            error_log("House owner update error: " . $exception->getMessage());
        }
    }
}

// Load only fixed houses in fixed order.
$houses = [];
try {
    $placeholders = implode(',', array_fill(0, count($fixedHouseNumbers), '?'));
    $orderPlaceholders = implode(',', array_fill(0, count($fixedHouseNumbers), '?'));
    $query = "SELECT id, house_number, owner_name, status
              FROM houses
              WHERE is_deleted = 0 AND house_number IN ($placeholders)
              ORDER BY FIELD(house_number, $orderPlaceholders)";
    $stmt = $db->prepare($query);
    $stmt->execute(array_merge($fixedHouseNumbers, $fixedHouseNumbers));
    $houses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $exception) {
    $error = "Error loading fixed houses.";
    error_log("Fixed houses load error: " . $exception->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Houses Management - Subdivision Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .house-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .house-table th, .house-table td {
            border: 1px solid #ddd;
            padding: 14px;
            text-align: left;
            vertical-align: middle;
        }
        .house-table th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }
        .house-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-occupied {
            background: #d4edda;
            color: #155724;
        }
        .status-vacant {
            background: #fff3cd;
            color: #856404;
        }
        .success-msg {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #3498db;
            border-radius: 8px;
            padding: 12px 14px;
            margin-bottom: 18px;
            color: #1f2937;
        }
        .owner-form {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .owner-input {
            width: 100%;
            min-width: 220px;
            padding: 9px 10px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }
        .save-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 9px 12px;
            cursor: pointer;
            font-size: 13px;
            white-space: nowrap;
        }
        .save-btn:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include("includes/header.php"); ?>

        <div class="main-content">
            <div class="page-header">
                <h1>Houses Management</h1>
                <div class="total-count">Fixed Houses: <strong><?php echo count($houses); ?></strong></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="info-box">
                Fixed subdivision setup is active. House lots are fixed (35 total). You can only assign/update the owner.
                Status updates automatically: owner set = Occupied, empty owner = Vacant.
            </div>

            <table class="house-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Fixed House Lot</th>
                        <th>Owner Assignment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($houses as $index => $house): ?>
                        <?php $status = ($house['status'] ?? 'Vacant') === 'Occupied' ? 'Occupied' : 'Vacant'; ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><strong><?php echo htmlspecialchars($house['house_number']); ?></strong></td>
                            <td>
                                <form method="POST" class="owner-form">
                                    <input type="hidden" name="id" value="<?php echo (int) $house['id']; ?>">
                                    <input
                                        type="text"
                                        name="owner_name"
                                        class="owner-input"
                                        placeholder="Enter owner name (leave blank for vacant)"
                                        value="<?php echo htmlspecialchars($house['owner_name'] ?? ''); ?>"
                                    >
                                    <button type="submit" name="update_owner" class="save-btn">Save</button>
                                </form>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($status); ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include("includes/footer.php"); ?>
</body>
</html>
