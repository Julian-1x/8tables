<?php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/models/Resident.php';
require_once __DIR__ . '/models/House.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: Login/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$residentModel = new Resident($db);
$houseModel = new House($db);

$success = "";
$error = "";
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_resident = null;

if ($edit_id > 0) {
    try {
        $edit_resident = $residentModel->getById($edit_id);
    } catch (PDOException $exception) {
        $error = "Error loading resident: " . $exception->getMessage();
    }
}

// Handle Add Resident
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resident'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $house_id = $_POST['house_id'];
    $relationship = $_POST['relationship'];

    // Input validation
    if (empty($name) || empty($house_id) || empty($relationship)) {
        $error = "Please fill in all required fields!";
    } else {
        try {
            $residentId = $residentModel->create($name, $phone, $house_id, $relationship);
            if ($residentId > 0) {
                $success = "Resident added successfully!";
                addAuditLog($db, $_SESSION['user_id'], 'ADD_RESIDENT', "Added resident: $name");
            }
        } catch(PDOException $exception) {
            $error = "Error adding resident: " . $exception->getMessage();
        }
    }
}

// Handle Update Resident
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_resident'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $house_id = $_POST['house_id'];
    $relationship = $_POST['relationship'];

    if (empty($id) || empty($name) || empty($house_id) || empty($relationship)) {
        $error = "Please fill in all required fields!";
    } else {
        try {
            $updated = $residentModel->update($id, $name, $phone, $house_id, $relationship);
            if ($updated) {
                $success = "Resident updated successfully!";
                addAuditLog($db, $_SESSION['user_id'], 'UPDATE_RESIDENT', "Updated resident ID: $id");
                header("Location: residents.php");
                exit();
            } else {
                $error = "No changes made or resident not found.";
            }
        } catch(PDOException $exception) {
            $error = "Error updating resident: " . $exception->getMessage();
        }
    }
}

// Handle Soft Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $deleted = $residentModel->softDelete($id);
        if ($deleted) {
            $success = "Resident deleted successfully!";
            addAuditLog($db, $_SESSION['user_id'], 'SOFT_DELETE_RESIDENT', "Soft deleted resident ID: $id");
        } else {
            $error = "Resident not found or already deleted!";
        }
    } catch(PDOException $exception) {
        $error = "Error deleting resident: " . $exception->getMessage();
    }
}

// Get all active residents
$residents = [];
try {
    $residents = $residentModel->listActiveWithHouse();
} catch(PDOException $exception) {
    $error = "Error loading residents: " . $exception->getMessage();
}

// Get houses for dropdown
$houses = [];
try {
    $houses = $houseModel->listActiveForSelect();
} catch(PDOException $exception) {
    $error = "Error loading houses: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents Management - Subdivision Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .resident-form { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .resident-form h3 {
            margin-top: 0;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .resident-form input, .resident-form select { 
            padding: 12px; 
            margin: 8px 5px; 
            border: 2px solid #ddd; 
            border-radius: 5px; 
            width: 200px;
            font-size: 16px;
        }
        .resident-form input:focus, .resident-form select:focus {
            border-color: #3498db;
            outline: none;
        }
        .resident-form button { 
            background: #27ae60; 
            color: white; 
            padding: 12px 25px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .resident-form button:hover { 
            background: #219150;
        }
        .resident-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .resident-table th, .resident-table td { 
            border: 1px solid #ddd; 
            padding: 15px; 
            text-align: left; 
        }
        .resident-table th { 
            background-color: #2c3e50; 
            color: white;
            font-weight: bold;
        }
        .resident-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .resident-table tr:hover {
            background-color: #f1f2f6;
        }
        .delete-btn { 
            background: #e74c3c; 
            color: white; 
            padding: 6px 12px; 
            text-decoration: none; 
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .delete-btn:hover { 
            background: #c0392b;
        }
        .relationship-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .relationship-owner {
            background: #d4edda;
            color: #155724;
        }
        .relationship-family {
            background: #d1ecf1;
            color: #0c5460;
        }
        .relationship-tenant {
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
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include("includes/header.php"); ?>

        <div class="main-content">
            <div class="page-header">
                <h1>👥 Residents Management</h1>
                <div class="total-count">Total Residents: <strong><?php echo count($residents); ?></strong></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Add Resident Form -->
            <div class="resident-form">
                <h3><?php echo $edit_resident ? '✏️ Edit Resident' : '➕ Add New Resident'; ?></h3>
                <form method="POST">
                    <?php if ($edit_resident): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$edit_resident['id']; ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" placeholder="Full Name" required style="width: 100%;"
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ($edit_resident['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" placeholder="Phone Number" style="width: 100%;"
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ($edit_resident['phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>House *</label>
                            <select name="house_id" required style="width: 100%;">
                                <option value="">Select House</option>
                                <?php foreach ($houses as $house): ?>
                                    <option value="<?php echo $house['id']; ?>" <?php echo (isset($_POST['house_id']) && $_POST['house_id'] == $house['id']) || ($edit_resident && $edit_resident['house_id'] == $house['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($house['house_number']); ?> - <?php echo htmlspecialchars($house['owner_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Relationship *</label>
                            <select name="relationship" required style="width: 100%;">
                                <option value="">Select Relationship</option>
                                <option value="Owner" <?php echo (isset($_POST['relationship']) && $_POST['relationship'] == 'Owner') || ($edit_resident && $edit_resident['relationship'] == 'Owner') ? 'selected' : ''; ?>>Owner</option>
                                <option value="Family" <?php echo (isset($_POST['relationship']) && $_POST['relationship'] == 'Family') || ($edit_resident && $edit_resident['relationship'] == 'Family') ? 'selected' : ''; ?>>Family</option>
                                <option value="Tenant" <?php echo (isset($_POST['relationship']) && $_POST['relationship'] == 'Tenant') || ($edit_resident && $edit_resident['relationship'] == 'Tenant') ? 'selected' : ''; ?>>Tenant</option>
                            </select>
                        </div>
                    </div>
                    
                    <?php if ($edit_resident): ?>
                        <button type="submit" name="update_resident">Update Resident</button>
                        <a href="residents.php" style="margin-left:10px;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_resident">Add Resident</button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Residents Table -->
            <h3>📋 Resident List</h3>
            <?php if (!empty($residents)): ?>
                <table class="resident-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>House Number</th>
                            <th>House Owner</th>
                            <th>Relationship</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($residents as $index => $resident): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($resident['name']); ?></td>
                                <td><?php echo htmlspecialchars($resident['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($resident['house_number']); ?></td>
                                <td><?php echo htmlspecialchars($resident['owner_name']); ?></td>
                                <td>
                                    <span class="relationship-badge relationship-<?php echo strtolower($resident['relationship']); ?>">
                                        <?php echo htmlspecialchars($resident['relationship']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $resident['id']; ?>" style="margin-right:8px;">✏️ Edit</a>
                                    <a href="?delete=<?php echo $resident['id']; ?>" class="delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete resident <?php echo htmlspecialchars($resident['name']); ?>? This action cannot be undone.')">
                                       🗑️ Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p style="font-size: 18px; color: #7f8c8d;">No residents found. Add your first resident using the form above.</p>
                </div>
            <?php endif; ?>
    <?php include("includes/footer.php"); ?>
</body>
</html>