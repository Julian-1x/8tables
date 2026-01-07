<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: Login/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Initialize counts with default values
$counts = [
    'houses' => 0,
    'residents' => 0,
    'vehicles' => 0,
    'payments_this_month' => 0,
    'maintenance_pending' => 0
];

$error = "";

try {
    // Count houses
    $query = "SELECT COUNT(*) as count FROM houses WHERE is_deleted = 0";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $counts['houses'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Count residents
    $query = "SELECT COUNT(*) as count FROM residents WHERE is_deleted = 0";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $counts['residents'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Count vehicles
    $query = "SELECT COUNT(*) as count FROM vehicles WHERE is_deleted = 0";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $counts['vehicles'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Count total payments this month
    $current_month = date('Y-m');
    $query = "SELECT COUNT(*) as count FROM payments WHERE is_deleted = 0 AND DATE_FORMAT(payment_date, '%Y-%m') = :current_month";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":current_month", $current_month);
    $stmt->execute();
    $counts['payments_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    // Count pending maintenance requests
    $query = "SELECT COUNT(*) as count FROM maintenance_requests WHERE is_deleted = 0 AND status IN ('Pending', 'In Progress')";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $counts['maintenance_pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

} catch (PDOException $exception) {
    $error = "Error loading dashboard data: " . $exception->getMessage();
    error_log($error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Subdivision Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-left: 5px solid #3498db;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }

        .card.houses {
            border-left-color: #e74c3c;
        }

        .card.residents {
            border-left-color: #2ecc71;
        }

        .card.vehicles {
            border-left-color: #f39c12;
        }

        .card.payments {
            border-left-color: #9b59b6;
        }

        .card.maintenance {
            border-left-color: #e67e22;
        }

        .card-number {
            font-size: 2.5em;
            font-weight: bold;
            margin: 10px 0;
            color: #2c3e50;
        }

        .card-label {
            font-size: 1.1em;
            color: #7f8c8d;
            font-weight: bold;
        }

        .quick-links {
            margin-top: 40px;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .quick-links h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
        }

        .quick-links a {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 12px 24px;
            margin: 8px;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.3s, transform 0.2s;
            font-weight: bold;
        }

        .quick-links a:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .welcome-message {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }

        .recent-activity {
            margin-top: 40px;
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .activity-list {
            list-style: none;
            padding: 0;
        }

        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #ecf0f1;
            display: flex;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            margin-right: 10px;
            font-size: 18px;
        }
    </style>
</head>

<body>
    <?php include("includes/header.php"); ?>

    <div class="welcome-message">
        <h1>📊 Dashboard</h1>
        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>! Here's your system
            overview for <?php echo date('F j, Y'); ?>.</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="dashboard-cards">
        <div class="card houses">
            <div class="card-label">Total Houses</div>
            <div class="card-number"><?php echo $counts['houses']; ?></div>
            <div>🏠 Properties</div>
        </div>

        <div class="card residents">
            <div class="card-label">Total Residents</div>
            <div class="card-number"><?php echo $counts['residents']; ?></div>
            <div>👥 People</div>
        </div>

        <div class="card vehicles">
            <div class="card-label">Total Vehicles</div>
            <div class="card-number"><?php echo $counts['vehicles']; ?></div>
            <div>🚗 Registered</div>
        </div>

        <div class="card payments">
            <div class="card-label">Payments This Month</div>
            <div class="card-number"><?php echo $counts['payments_this_month']; ?></div>
            <div>💰 <?php echo date('F Y'); ?></div>
        </div>

        <!-- MAINTENANCE CARD ADDED -->
        <div class="card maintenance">
            <div class="card-label">Pending Maintenance</div>
            <div class="card-number"><?php echo $counts['maintenance_pending']; ?></div>
            <div>🛠️ Requests</div>
        </div>
    </div>

    <div class="quick-links">
        <h3>🚀 Quick Actions</h3>
        <a href="houses.php">🏠 Add New House</a>
        <a href="residents.php">👥 Add New Resident</a>
        <a href="payments.php">💰 Record Payment</a>
        <a href="vehicles.php">🚗 Register Vehicle</a>
        <!-- MAINTENANCE LINK ADDED -->
        <a href="maintenance.php">🛠️ New Maintenance</a>
        <!-- END OF MAINTENANCE LINK -->
        <a href="reports.php">📈 View Reports</a>
    </div>

    <div class="recent-activity">
        <h3>📝 Recent Activity</h3>
        <ul class="activity-list">
            <li class="activity-item">
                <span class="activity-icon">📊</span>
                <span>System started successfully</span>
            </li>
            <li class="activity-item">
                <span class="activity-icon">👋</span>
                <span>Welcome to Subdivision Management System</span>
            </li>
            <li class="activity-item">
                <span class="activity-icon">💡</span>
                <span>Add your first house to get started</span>
            </li>
        </ul>
    </div>

    <?php include("includes/footer.php"); ?>
</body>

</html>