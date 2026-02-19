<?php
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? $_SESSION['username'] : '';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<?php if ($isLoggedIn): ?>
    <!-- Dashboard Layout -->
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <h2>Subdivision MS</h2>
            <ul>
                <li><a href="/8tables/dashboard.php"
                        class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="/8tables/houses.php"
                        class="<?php echo $current_page === 'houses.php' ? 'active' : ''; ?>">Houses</a></li>
                <li><a href="/8tables/residents.php"
                        class="<?php echo $current_page === 'residents.php' ? 'active' : ''; ?>">Residents</a></li>
                <li><a href="/8tables/payments.php"
                        class="<?php echo $current_page === 'payments.php' ? 'active' : ''; ?>">Payments</a></li>
                <li><a href="/8tables/vehicles.php"
                        class="<?php echo $current_page === 'vehicles.php' ? 'active' : ''; ?>">Vehicles</a></li>
                <li><a href="/8tables/maintenance.php"
                        class="<?php echo $current_page === 'maintenance.php' ? 'active' : ''; ?>">Maintenance</a></li>
                <li><a href="/8tables/reports.php"
                        class="<?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">Reports</a></li>
                <li><a href="/8tables/audit_log.php"
                        class="<?php echo $current_page === 'audit_log.php' ? 'active' : ''; ?>">Audit Log</a></li>
                <li><a href="/8tables/archive_manager.php"
                        class="<?php echo $current_page === 'archive_manager.php' ? 'active' : ''; ?>">Archive</a></li>
                <li><a href="/8tables/subdivision_map.php"
                        class="<?php echo $current_page === 'subdivision_map.php' ? 'active' : ''; ?>">Subdivision Map</a></li>
                <li><a href="/8tables/Login/logout.php" class="logout-btn">Logout
                        (<?php echo htmlspecialchars($username); ?>)</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Page Header -->
            <header class="page-header">
                <h1>
                    <?php
                    // Dynamic page title based on current file
                    $pageTitles = [
                        'dashboard.php' => 'Dashboard',
                        'residents.php' => 'Residents Management',
                        'houses.php' => 'Houses Management',
                        'vehicles.php' => 'Vehicles Management',
                        'payments.php' => 'Payments Management',
                        'maintenance.php' => 'Maintenance Requests',
                        'reports.php' => 'Reports & Analytics',
                        'audit_log.php' => 'Audit Logs',
                        'archive_manager.php' => 'Archive Manager',
                        'subdivision_map.php' => 'Subdivision Map'
                    ];
                    $currentFile = basename($_SERVER['PHP_SELF']);
                    echo $pageTitles[$currentFile] ?? 'Subdivision Management System';
                    ?>
                </h1>
                <div class="user-info">
                    Welcome, <strong><?php echo htmlspecialchars($username); ?></strong>
                    <span class="current-date"><?php echo date('F j, Y'); ?></span>
                </div>
            </header>

        <?php else: ?>
            <!-- Login Page Layout -->
            <div class="login-page">
            <?php endif; ?>
