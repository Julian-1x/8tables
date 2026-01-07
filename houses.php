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

// Handle Add House
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_house'])) {
    $house_number = trim($_POST['house_number']);
    $owner_name = trim($_POST['owner_name']);
    $status = $_POST['status'];

    // Input validation
    if (empty($house_number) || empty($owner_name) || empty($status)) {
        $error = "Please fill in all fields!";
    } else {
        try {
            $query = "INSERT INTO houses (house_number, owner_name, status, created_at) VALUES (:house_number, :owner_name, :status, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":house_number", $house_number);
            $stmt->bindParam(":owner_name", $owner_name);
            $stmt->bindParam(":status", $status);
            
            if ($stmt->execute()) {
                $success = "House added successfully!";
                addAuditLog($db, $_SESSION['user_id'], 'ADD_HOUSE', "Added house: $house_number - Owner: $owner_name");
            }
        } catch(PDOException $exception) {
            if ($exception->getCode() == 23000) {
                $error = "House number already exists!";
            } else {
                $error = "Error adding house: " . $exception->getMessage();
            }
        }
    }
}

// Handle Soft Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $query = "UPDATE houses SET is_deleted = 1, deleted_at = NOW() WHERE id = :id AND is_deleted = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            $success = "House deleted successfully!";
            addAuditLog($db, $_SESSION['user_id'], 'SOFT_DELETE_HOUSE', "Soft deleted house ID: $id");
        } else {
            $error = "House not found or already deleted!";
        }
    } catch(PDOException $exception) {
        $error = "Error deleting house: " . $exception->getMessage();
    }
}

// Get all active houses
$houses = [];
try {
    $query = "SELECT * FROM houses WHERE is_deleted = 0 ORDER BY house_number";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $houses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
    $error = "Error loading houses: " . $exception->getMessage();
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
        .house-form { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .house-form h3 {
            margin-top: 0;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .house-form input, .house-form select { 
            padding: 12px; 
            margin: 8px 5px; 
            border: 2px solid #ddd; 
            border-radius: 5px; 
            width: 200px;
            font-size: 16px;
        }
        .house-form input:focus, .house-form select:focus {
            border-color: #3498db;
            outline: none;
        }
        .house-form button { 
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
        .house-form button:hover { 
            background: #219150;
        }
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
            padding: 15px; 
            text-align: left; 
        }
        .house-table th { 
            background-color: #2c3e50; 
            color: white;
            font-weight: bold;
        }
        .house-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .house-table tr:hover {
            background-color: #f1f2f6;
        }
        .delete-btn { 
            background: #e74c3c; 
            color: white; 
            padding: 8px 15px; 
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
    </style>
</head>
<body>
    <div class="container">
        <?php include("includes/header.php"); ?>

        <div class="main-content">
            <div class="page-header">
                <h1>🏠 Houses Management</h1>
                <div class="total-count">Total Houses: <strong><?php echo count($houses); ?></strong></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Add House Form -->
            <div class="house-form">
                <h3>➕ Add New House</h3>
                <form method="POST">
                    <input type="text" name="house_number" placeholder="House Number" required 
                           value="<?php echo isset($_POST['house_number']) ? htmlspecialchars($_POST['house_number']) : ''; ?>">
                    <input type="text" name="owner_name" placeholder="Owner Name" required
                           value="<?php echo isset($_POST['owner_name']) ? htmlspecialchars($_POST['owner_name']) : ''; ?>">
                    <select name="status" required>
                        <option value="">Select Status</option>
                        <option value="Occupied" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Occupied') ? 'selected' : ''; ?>>Occupied</option>
                        <option value="Vacant" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Vacant') ? 'selected' : ''; ?>>Vacant</option>
                    </select>
                    <button type="submit" name="add_house">Add House</button>
                </form>
            </div>

            <!-- Houses Table -->
            <h3>📋 House List</h3>
            <?php if (!empty($houses)): ?>
                <table class="house-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>House Number</th>
                            <th>Owner Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($houses as $index => $house): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($house['house_number']); ?></td>
                                <td><?php echo htmlspecialchars($house['owner_name']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($house['status']); ?>">
                                        <?php echo htmlspecialchars($house['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?delete=<?php echo $house['id']; ?>" class="delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete house <?php echo htmlspecialchars($house['house_number']); ?>? This action cannot be undone.')">
                                       🗑️ Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p style="font-size: 18px; color: #7f8c8d;">No houses found. Add your first house using the form above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include("includes/footer.php"); ?>
</body>
</html>