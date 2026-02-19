<?php
// sidebar.php
// Check if user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$basePath = rtrim(str_replace('/Login', '', dirname($scriptPath)), '/');
if ($basePath === '.' || $basePath === '/') {
    $basePath = '';
}
$appUrl = static function (string $path = '') use ($basePath): string {
    return ($basePath === '' ? '' : $basePath) . '/' . ltrim($path, '/');
};

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $appUrl('Login/login.php'));
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Navigation -->
<div class="sidebar">
    <h2>Subdivision MS</h2>
    <ul>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('dashboard.php')); ?>"
                class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                Dashboard
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('houses.php')); ?>" class="<?php echo $current_page == 'houses.php' ? 'active' : ''; ?>">
                Houses
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('residents.php')); ?>"
                class="<?php echo $current_page == 'residents.php' ? 'active' : ''; ?>">
                Residents
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('vehicles.php')); ?>" class="<?php echo $current_page == 'vehicles.php' ? 'active' : ''; ?>">
                Vehicles
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('payments.php')); ?>" class="<?php echo $current_page == 'payments.php' ? 'active' : ''; ?>">
                Payments
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('maintenance.php')); ?>"
                class="<?php echo $current_page == 'maintenance.php' ? 'active' : ''; ?>">
                Maintenance
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('reports.php')); ?>" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                Reports
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('audit_log.php')); ?>"
                class="<?php echo $current_page == 'audit_log.php' ? 'active' : ''; ?>">
                Audit Log
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('archive_manager.php')); ?>"
                class="<?php echo $current_page == 'archive_manager.php' ? 'active' : ''; ?>">
                Archive Manager
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('subdivision_map.php')); ?>"
                class="<?php echo $current_page == 'subdivision_map.php' ? 'active' : ''; ?>">
                Subdivision Map
            </a>
        </li>
        <li>
            <a href="<?php echo htmlspecialchars($appUrl('Login/logout.php')); ?>" class="logout-btn">
                Logout (<?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?>)
            </a>
        </li>
    </ul>
</div>
