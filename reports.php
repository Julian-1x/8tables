<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: Login/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

function getFixedReportHouseNumbers(): array
{
    $lots = [];
    for ($block = 1; $block <= 5; $block++) {
        for ($lot = 1; $lot <= 6; $lot++) {
            $lots[] = sprintf('Block %02d Lot %02d', $block, $lot);
        }
    }
    for ($lot = 1; $lot <= 5; $lot++) {
        $lots[] = sprintf('Block 06 Lot %02d', $lot);
    }
    return $lots;
}

$fixedHouseNumbers = getFixedReportHouseNumbers();
$TOTAL_FIXED_LOTS = count($fixedHouseNumbers);

// Get data from database
$houses = [];
$residents = [];
$payments = [];
$vehicles = [];

$error = "";

try {
    // Get fixed houses only
    $placeholders = implode(',', array_fill(0, count($fixedHouseNumbers), '?'));
    $orderPlaceholders = implode(',', array_fill(0, count($fixedHouseNumbers), '?'));
    $query = "SELECT *
              FROM houses
              WHERE is_deleted = 0
                AND house_number IN ($placeholders)
              ORDER BY FIELD(house_number, $orderPlaceholders)";
    $stmt = $db->prepare($query);
    $stmt->execute(array_merge($fixedHouseNumbers, $fixedHouseNumbers));
    $dbHouses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $houseMap = [];
    foreach ($dbHouses as $house) {
        $houseMap[$house['house_number']] = $house;
    }

    foreach ($fixedHouseNumbers as $houseNumber) {
        $house = $houseMap[$houseNumber] ?? null;
        if ($house) {
            $houses[] = $house;
        } else {
            $houses[] = [
                'house_number' => $houseNumber,
                'owner_name' => '',
                'status' => 'Vacant',
                'contact_number' => null
            ];
        }
    }

    // Get residents with house info
    $query = "SELECT r.*, h.house_number 
              FROM residents r 
              LEFT JOIN houses h ON r.house_id = h.id 
              WHERE r.is_deleted = 0
              ORDER BY h.house_number";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $residents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get payments with house info
    $query = "SELECT p.*, h.house_number 
              FROM payments p 
              LEFT JOIN houses h ON p.house_id = h.id 
              WHERE p.is_deleted = 0
              ORDER BY p.payment_date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get vehicles with house info
    $query = "SELECT v.*, h.house_number 
              FROM vehicles v 
              LEFT JOIN houses h ON v.house_id = h.id 
              WHERE v.is_deleted = 0
              ORDER BY h.house_number";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $exception) {
    $error = "Error loading data: " . $exception->getMessage();
}

// Calculate statistics
$total_residents = count($residents);
$total_houses = $TOTAL_FIXED_LOTS;
$total_vehicles = count($vehicles);

// Calculate house status counts
$occupied_houses = 0;
$vacant_houses = 0;
foreach ($houses as $house) {
    if ($house['status'] === 'Occupied') {
        $occupied_houses++;
    } else {
        $vacant_houses++;
    }
}

// Calculate occupancy rate
$occupancy_rate = $total_houses > 0 ? round(($occupied_houses / $total_houses) * 100, 2) : 0;

// Calculate payment statistics
$total_payments = count($payments);
$paid_count = 0;
$pending_count = 0;
$overdue_count = 0;
$total_collected = 0;

foreach ($payments as $payment) {
    if ($payment['status'] === 'Paid') {
        $paid_count++;
        $total_collected += floatval($payment['amount']);
    } elseif ($payment['status'] === 'Pending') {
        $pending_count++;
    } elseif ($payment['status'] === 'Overdue') {
        $overdue_count++;
    }
}

// Group residents by house
$residents_by_house = [];
foreach ($residents as $resident) {
    $house_num = $resident['house_number'];
    if (!isset($residents_by_house[$house_num])) {
        $residents_by_house[$house_num] = [];
    }
    $residents_by_house[$house_num][] = $resident;
}

// Group vehicles by type
$vehicles_by_type = [];
foreach ($vehicles as $vehicle) {
    $type = $vehicle['vehicle_type'];
    if (!isset($vehicles_by_type[$type])) {
        $vehicles_by_type[$type] = 0;
    }
    $vehicles_by_type[$type]++;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Subdivision Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .report-section {
            background: white;
            padding: 25px;
            margin-bottom: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 5px solid #2c3e50;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .occupied { background: #d4edda; color: #155724; }
        .vacant { background: #fff3cd; color: #856404; }
        
        .chart-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .chart {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .house-table, .resident-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .house-table th, .house-table td,
        .resident-table th, .resident-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        .house-table th, .resident-table th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }
        
        .house-table tr:nth-child(even),
        .resident-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .house-table tr:hover,
        .resident-table tr:hover {
            background-color: #f1f2f6;
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
        
        .report-title {
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .section-title {
            color: #34495e;
            margin-bottom: 15px;
        }
        
        .house-section {
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .print-btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .print-btn:hover {
            background: #2980b9;
        }
        
        @media print {
            .sidebar, .print-btn {
                display: none;
            }
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include("includes/header.php"); ?>

        <div class="main-content">
            <div class="page-header">
                <h1>📊 Reports & Analytics</h1>
                <button class="print-btn" onclick="window.print()">🖨️ Print Report</button>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Summary Statistics -->
            <div class="report-section">
                <h2 class="report-title">📈 Summary Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card" style="border-left-color: #e74c3c;">
                        <div class="stat-number"><?php echo $total_houses; ?></div>
                        <div class="stat-label">Total Houses</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #2ecc71;">
                        <div class="stat-number"><?php echo $total_residents; ?></div>
                        <div class="stat-label">Total Residents</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-number"><?php echo $total_vehicles; ?></div>
                        <div class="stat-label">Total Vehicles</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #9b59b6;">
                        <div class="stat-number"><?php echo $occupied_houses; ?></div>
                        <div class="stat-label">Occupied Houses</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #1abc9c;">
                        <div class="stat-number"><?php echo $vacant_houses; ?></div>
                        <div class="stat-label">Vacant Houses</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #3498db;">
                        <div class="stat-number"><?php echo $occupancy_rate; ?>%</div>
                        <div class="stat-label">Occupancy Rate</div>
                    </div>
                </div>

                <!-- Payment Statistics -->
                <h3 class="section-title">💰 Payment Summary</h3>
                <div class="stats-grid">
                    <div class="stat-card" style="border-left-color: #27ae60;">
                        <div class="stat-number"><?php echo $total_payments; ?></div>
                        <div class="stat-label">Total Payments</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #27ae60;">
                        <div class="stat-number"><?php echo $paid_count; ?></div>
                        <div class="stat-label">Paid</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-number"><?php echo $pending_count; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #e74c3c;">
                        <div class="stat-number"><?php echo $overdue_count; ?></div>
                        <div class="stat-label">Overdue</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #9b59b6;">
                        <div class="stat-number">₱<?php echo number_format($total_collected, 2); ?></div>
                        <div class="stat-label">Total Collected</div>
                    </div>
                </div>
            </div>

            <!-- House Status Report -->
            <div class="report-section">
                <h2 class="report-title">🏠 House Status Report</h2>
                <table class="house-table">
                    <thead>
                        <tr>
                            <th>House Number</th>
                            <th>Owner</th>
                            <th>Status</th>
                            <th>Contact</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($houses)): ?>
                            <?php foreach ($houses as $house): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($house['house_number']); ?></td>
                                    <td><?php echo htmlspecialchars($house['owner_name']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($house['status']); ?>">
                                            <?php echo $house['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($house['contact_number'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 20px;">No houses data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Resident Distribution -->
            <div class="report-section">
                <h2 class="report-title">👥 Resident Distribution</h2>
                <?php if (!empty($residents_by_house)): ?>
                    <?php foreach ($residents_by_house as $house_num => $house_residents): ?>
                        <div class="house-section">
                            <h4 class="section-title">🏠 House <?php echo htmlspecialchars($house_num); ?> 
                                <small>(<?php echo count($house_residents); ?> resident<?php echo count($house_residents) !== 1 ? 's' : ''; ?>)</small>
                            </h4>
                            <table class="resident-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Relationship</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($house_residents as $resident): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($resident['full_name'] ?? $resident['name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($resident['contact_number'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($resident['relationship'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align: center; color: #7f8c8d; padding: 20px;">No residents data available.</p>
                <?php endif; ?>
            </div>

            <!-- Vehicle Summary -->
            <div class="report-section">
                <h2 class="report-title">🚗 Vehicle Summary</h2>
                <?php if (!empty($vehicles_by_type)): ?>
                    <div class="stats-grid">
                        <?php foreach ($vehicles_by_type as $type => $count): ?>
                            <div class="stat-card" style="border-left-color: #3498db;">
                                <div class="stat-number"><?php echo $count; ?></div>
                                <div class="stat-label"><?php echo htmlspecialchars($type); ?>s</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <table class="house-table">
                    <thead>
                        <tr>
                            <th>House No.</th>
                            <th>Vehicle Type</th>
                            <th>Plate Number</th>
                            <th>Color</th>
                            <th>Model</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($vehicles)): ?>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vehicle['house_number']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['plate_number']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['color']); ?></td>
                                    <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align:center; padding: 20px;">No vehicles data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Contact List -->
            <div class="report-section">
                <h2 class="report-title">📞 Resident Contact List</h2>
                <table class="resident-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>House Number</th>
                            <th>Contact Number</th>
                            <th>Relationship</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($residents)): ?>
                            <?php foreach ($residents as $resident): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($resident['full_name'] ?? $resident['name'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($resident['house_number']); ?></td>
                                    <td><?php echo htmlspecialchars($resident['contact_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($resident['relationship'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center; padding: 20px;">No residents data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include("includes/footer.php"); ?>
</body>
</html>
