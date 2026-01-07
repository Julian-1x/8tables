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

// Handle Clear Log
if (isset($_GET['clear_log'])) {
    try {
        $query = "DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $success = "Audit logs older than 30 days have been cleared!";
        addAuditLog($db, $_SESSION['user_id'], 'CLEAR_AUDIT_LOG', "Cleared audit logs older than 30 days");
    } catch(PDOException $exception) {
        $error = "Error clearing audit log: " . $exception->getMessage();
    }
}

// Get all audit logs from database
$audit_logs = [];
try {
    $query = "SELECT al.*, u.username 
              FROM audit_logs al 
              LEFT JOIN users u ON al.user_id = u.id 
              ORDER BY al.created_at DESC 
              LIMIT 1000";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
    $error = "Error loading audit logs: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Subdivision Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .audit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .clear-btn {
            background: #e74c3c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .clear-btn:hover {
            background: #c0392b;
        }
        
        .audit-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        
        .audit-table th,
        .audit-table td {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: left;
        }
        
        .audit-table th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
            position: sticky;
            top: 0;
        }
        
        .audit-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        .audit-table tr:hover {
            background-color: #f1f2f6;
        }
        
        .action-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .action-login { background: #d4edda; color: #155724; }
        .action-user_login { background: #d4edda; color: #155724; }
        .action-user_logout { background: #d6d8db; color: #1b1e21; }
        .action-add { background: #d1ecf1; color: #0c5460; }
        .action-delete { background: #f8d7da; color: #721c24; }
        .action-soft_delete { background: #f8d7da; color: #721c24; }
        .action-update { background: #fff3cd; color: #856404; }
        .action-register { background: #e2e3e5; color: #383d41; }
        .action-clear_audit_log { background: #e74c3c; color: white; }
        
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
        
        .log-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .timestamp {
            font-family: monospace;
            font-size: 14px;
            color: #666;
        }
        
        .ip-address {
            font-family: monospace;
            font-size: 12px;
            color: #888;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .empty-state p {
            font-size: 18px;
            color: #7f8c8d;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include("includes/header.php"); ?>

        <div class="main-content">
            <div class="audit-header">
                <h1>📋 Audit Log</h1>
                <a href="?clear_log=true" class="clear-btn" 
                   onclick="return confirm('⚠️ WARNING: This will delete audit logs older than 30 days. This action cannot be undone! Are you sure?')">
                   🗑️ Clear Old Logs
                </a>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="log-info">
                <p><strong>📊 System Activity Monitor</strong><br>
                Total Logs: <strong><?php echo count($audit_logs); ?></strong> | 
                Showing last 1,000 entries | 
                Actions are automatically logged for security and monitoring purposes.</p>
            </div>

            <!-- Audit Log Table -->
            <?php if (!empty($audit_logs)): ?>
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_logs as $log): ?>
                            <tr>
                                <td class="timestamp"><?php echo date('M j, Y H:i:s', strtotime($log['created_at'] ?? $log['timestamp'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($log['username'] ?: 'System'); ?></strong></td>
                                <td>
                                    <span class="action-badge action-<?php echo strtolower(str_replace('_', '-', $log['action'])); ?>">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td class="ip-address"><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>📝 No audit log entries found.</p>
                    <p style="font-size: 14px; margin-top: 10px;">System activities will appear here once they occur.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include("includes/footer.php"); ?>
</body>
</html>