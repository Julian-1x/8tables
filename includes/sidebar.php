<?php
// sidebar.php
// Check if user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: /8tables/Login/login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h2>Subdivision MS</h2>
    <ul>
        <li>
            <a href="/8tables/dashboard.php"
                class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
        </li>
        <li>
            <a href="/8tables/houses.php" class="<?php echo $current_page == 'houses.php' ? 'active' : ''; ?>">
                Houses
            </a>
        </li>
        <li>
            <a href="/8tables/residents.php"
                class="<?php echo $current_page == 'residents.php' ? 'active' : ''; ?>">
                Residents
            </a>
        </li>
        <li>
            <a href="/8tables/vehicles.php" class="<?php echo $current_page == 'vehicles.php' ? 'active' : ''; ?>">
                Vehicles
            </a>
        </li>
        <li>
            <a href="/8tables/payments.php" class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
                Payments
            </a>
        </li>
        <li>
            <a href="/8tables/maintenance.php"
                class="<?php echo $current_page == 'maintenance.php' ? 'active' : ''; ?>">
                Maintenance
            </a>
        </li>
        <li>
            <a href="/8tables/reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                Reports
            </a>
        </li>
        <li>
            <a href="/8tables/audit_log.php"
                class="<?php echo $current_page == 'audit_log.php' ? 'active' : ''; ?>">
                Audit Log
            </a>
        </li>
        <li>
            <a href="/8tables/archive_manager.php"
                class="<?php echo $current_page == 'archive_manager.php' ? 'active' : ''; ?>">
                Archive Manager
            </a>
        </li>
        <li>
            <a href="/8tables/subdivision_map.php"
                class="<?php echo $current_page == 'subdivision_map.php' ? 'active' : ''; ?>">
                Subdivision Map
            </a>
        </li>
        <li>
            <a href="/8tables/Login/logout.php" class="logout-btn">
                Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>)
            </a>
        </li>
    </ul>
</div>
