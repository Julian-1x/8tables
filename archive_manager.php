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

// Handle Restore from Archive
if (isset($_GET['restore'])) {
    $id = $_GET['restore'];
    $table = $_GET['table'];

    try {
        $query = "UPDATE $table SET is_deleted = 0, deleted_at = NULL WHERE id = :id AND is_deleted = 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute() && $stmt->rowCount() > 0) {
            $success = "Item restored successfully!";
            addAuditLog($db, $_SESSION['user_id'], 'RESTORE_ITEM', "Restored from $table, ID: $id");
        } else {
            $error = "Item not found or already restored!";
        }
    } catch (PDOException $exception) {
        $error = "Error restoring item: " . $exception->getMessage();
    }
}

// Get all archived items from all tables
$archived_items = [
    'houses' => [],
    'residents' => [],
    'payments' => [],
    'vehicles' => [],
    'maintenance_requests' => []  // MAINTENANCE ADDED
];

$total_archived = 0;

// Get archived houses
try {
    $query = "SELECT id, house_number as name, owner_name, 'House' as type, 'houses' as table_name, deleted_at FROM houses WHERE is_deleted = 1 ORDER BY deleted_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $archived_items['houses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_archived += count($archived_items['houses']);
} catch (PDOException $e) {
    // Skip error
}

// Get archived residents
try {
    $query = "SELECT r.id, r.name, h.house_number, 'Resident' as type, 'residents' as table_name, r.deleted_at 
              FROM residents r 
              LEFT JOIN houses h ON r.house_id = h.id 
              WHERE r.is_deleted = 1 
              ORDER BY r.deleted_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $archived_items['residents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_archived += count($archived_items['residents']);
} catch (PDOException $e) {
    // Skip error
}

// Get archived payments
try {
    $query = "SELECT p.id, CONCAT('Payment ₱', p.amount, ' - ', p.due_month, ' (', h.house_number, ')') as name, 'Payment' as type, 'payments' as table_name, p.deleted_at 
              FROM payments p 
              LEFT JOIN houses h ON p.house_id = h.id 
              WHERE p.is_deleted = 1 
              ORDER BY p.deleted_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $archived_items['payments'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_archived += count($archived_items['payments']);
} catch (PDOException $e) {
    // Skip error
}

// Get archived vehicles
try {
    $query = "SELECT v.id, CONCAT(v.plate_number, ' (', v.model, ') - ', h.house_number) as name, 'Vehicle' as type, 'vehicles' as table_name, v.deleted_at 
              FROM vehicles v 
              LEFT JOIN houses h ON v.house_id = h.id 
              WHERE v.is_deleted = 1 
              ORDER BY v.deleted_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $archived_items['vehicles'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_archived += count($archived_items['vehicles']);
} catch (PDOException $e) {
    // Skip error
}

// Get archived maintenance requests
try {
    $query = "SELECT mr.id, CONCAT('Maintenance: ', mr.title, ' - ', h.house_number) as name, 
              'Maintenance' as type, 'maintenance_requests' as table_name, mr.deleted_at 
              FROM maintenance_requests mr 
              LEFT JOIN houses h ON mr.house_id = h.id 
              WHERE mr.is_deleted = 1 
              ORDER BY mr.deleted_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $archived_items['maintenance_requests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_archived += count($archived_items['maintenance_requests']);
} catch (PDOException $e) {
    // Skip error
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive Manager - Subdivision Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .archive-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .archive-table th,
        .archive-table td {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: left;
        }

        .archive-table th {
            background-color: #2c3e50;
            color: white;
            font-weight: bold;
        }

        .archive-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .archive-table tr:hover {
            background-color: #f1f2f6;
        }

        .restore-btn {
            background: #27ae60;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }

        .restore-btn:hover {
            background: #219150;
        }

        .archive-section {
            margin-bottom: 30px;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .archive-section h3 {
            margin-top: 0;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
            padding-bottom: 10px;
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

        .total-count {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
        }

        .type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .type-house {
            background: #d4edda;
            color: #155724;
        }

        .type-resident {
            background: #d1ecf1;
            color: #0c5460;
        }

        .type-payment {
            background: #fff3cd;
            color: #856404;
        }

        .type-vehicle {
            background: #e2e3e5;
            color: #383d41;
        }

        .type-maintenance {
            background: #f8d7da;
            color: #721c24;
        }

        // MAINTENANCE ADDED
        .timestamp {
            font-size: 12px;
            color: #6c757d;
            font-family: monospace;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include("includes/header.php"); ?>

        <div class="main-content">
            <div class="page-header">
                <h1>📁 Archive Manager</h1>
                <div class="total-count">Total Archived: <strong><?php echo $total_archived; ?></strong></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div
                style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 25px; border-left: 4px solid #3498db;">
                <p><strong>📋 Archive Overview</strong><br>
                    This section contains all deleted items that can be restored. Items are automatically moved here
                    when deleted from the system.</p>
            </div>

            <?php
            $tables = [
                'houses' => '🏠 Houses',
                'residents' => '👥 Residents',
                'payments' => '💰 Payments',
                'vehicles' => '🚗 Vehicles',
                'maintenance_requests' => '🛠️ Maintenance Requests'  // MAINTENANCE ADDED
            ];

            foreach ($tables as $type => $label):
                $items = $archived_items[$type] ?? [];
                $count = count($items);
                ?>
                <div class="archive-section">
                    <h3><?php echo $label; ?> <span style="color: #6c757d; font-size: 14px;">(<?php echo $count; ?>
                            archived)</span></h3>

                    <?php if ($count > 0): ?>
                        <table class="archive-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name/Description</th>
                                    <th>Type</th>
                                    <th>Deleted Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><strong>#<?php echo $item['id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>
                                            <span class="type-badge type-<?php echo strtolower($item['type']); ?>">
                                                <?php echo $item['type']; ?>
                                            </span>
                                        </td>
                                        <td class="timestamp">
                                            <?php echo $item['deleted_at'] ? date('M j, Y H:i', strtotime($item['deleted_at'])) : 'Unknown'; ?>
                                        </td>
                                        <td>
                                            <a href="?restore=<?php echo $item['id']; ?>&table=<?php echo $item['table_name']; ?>"
                                                class="restore-btn"
                                                onclick="return confirm('Are you sure you want to restore this <?php echo strtolower($item['type']); ?>? It will be moved back to the active records.')">
                                                🔄 Restore
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No archived <?php echo strtolower($label); ?> found.</p>
                            <p style="font-size: 14px; margin-top: 5px;">All <?php echo strtolower($label); ?> are currently
                                active.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <?php if ($total_archived === 0): ?>
                <div
                    style="text-align: center; padding: 60px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p style="font-size: 20px; color: #7f8c8d; margin-bottom: 10px;">📭 Archive is Empty</p>
                    <p style="color: #95a5a6;">No items have been archived yet. Deleted items will appear here.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include("includes/footer.php"); ?>
</body>

</html>