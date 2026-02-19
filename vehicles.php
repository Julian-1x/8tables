<?php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/models/Vehicle.php';
require_once __DIR__ . '/models/House.php';
require_once __DIR__ . '/includes/house_options.php';
require_once __DIR__ . '/includes/house_validation.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: Login/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$vehicleModel = new VehicleModel($db);
$houseModel = new House($db);

$success = "";
$error = "";

// Edit state
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_vehicle = null;

if ($edit_id > 0) {
    try {
        $edit_vehicle = $vehicleModel->getById($edit_id);
    } catch (PDOException $exception) {
        $error = "Error loading vehicle: " . $exception->getMessage();
    }
}

// Handle Add Vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $plate_number = trim($_POST['plate_number']);
    $model = trim($_POST['model']);
    $color = trim($_POST['color']);
    $vehicle_type = $_POST['vehicle_type'];
    $house_id = $_POST['house_id'];

    // Input validation
    if (empty($plate_number) || empty($model) || empty($color) || empty($vehicle_type) || empty($house_id)) {
        $error = "Please fill in all required fields!";
    } elseif (!isHouseOccupied($db, (int) $house_id)) {
        $error = "Cannot add vehicle to an empty/vacant house. Assign a house owner first.";
    } else {
        try {
            $vehicleId = $vehicleModel->create($plate_number, $model, $color, $vehicle_type, $house_id);
            if ($vehicleId > 0) {
                $success = "Vehicle added successfully!";
                addAuditLog($db, $_SESSION['user_id'], 'ADD_VEHICLE', "Added vehicle: $plate_number");
            }
        } catch(PDOException $exception) {
            if ($exception->getCode() == 23000) {
                $error = "Plate number already exists!";
            } else {
                $error = "Error adding vehicle: " . $exception->getMessage();
            }
        }
    }
}

// Handle Update Vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vehicle'])) {
    $id = (int)$_POST['id'];
    $plate_number = trim($_POST['plate_number']);
    $model = trim($_POST['model']);
    $color = trim($_POST['color']);
    $vehicle_type = $_POST['vehicle_type'];
    $house_id = $_POST['house_id'];

    if (empty($id) || empty($plate_number) || empty($model) || empty($color) || empty($vehicle_type) || empty($house_id)) {
        $error = "Please fill in all required fields!";
    } elseif (!isHouseOccupied($db, (int) $house_id)) {
        $error = "Cannot assign vehicle to an empty/vacant house. Assign a house owner first.";
    } else {
        try {
            $updated = $vehicleModel->update($id, $plate_number, $model, $color, $vehicle_type, $house_id);
            if ($updated) {
                $success = "Vehicle updated successfully!";
                addAuditLog($db, $_SESSION['user_id'], 'UPDATE_VEHICLE', "Updated vehicle ID: $id");
                header("Location: vehicles.php");
                exit();
            } else {
                $error = "No changes made or vehicle not found.";
            }
        } catch(PDOException $exception) {
            $error = "Error updating vehicle: " . $exception->getMessage();
        }
    }
}

// Handle Soft Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $deleted = $vehicleModel->softDelete($id);
        if ($deleted) {
            $success = "Vehicle deleted successfully!";
            addAuditLog($db, $_SESSION['user_id'], 'SOFT_DELETE_VEHICLE', "Soft deleted vehicle ID: $id");
        } else {
            $error = "Vehicle not found or already deleted!";
        }
    } catch(PDOException $exception) {
        $error = "Error deleting vehicle: " . $exception->getMessage();
    }
}

// Get all active vehicles
$vehicles = [];
try {
    $vehicles = $vehicleModel->listActiveWithHouse();
} catch(PDOException $exception) {
    $error = "Error loading vehicles: " . $exception->getMessage();
}

// Get houses for dropdown
$houses = [];
$houseGroups = [];
try {
    $houses = $houseModel->listOccupiedForSelect();
    $houseGroups = groupHousesForDropdown($houses);
} catch(PDOException $exception) {
    $error = "Error loading houses: " . $exception->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles Management - Subdivision Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .vehicle-form { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .vehicle-form h3 {
            margin-top: 0;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .vehicle-form input, .vehicle-form select { 
            padding: 12px; 
            margin: 8px 5px; 
            border: 2px solid #ddd; 
            border-radius: 5px; 
            width: 200px;
            font-size: 16px;
        }
        .vehicle-form input:focus, .vehicle-form select:focus {
            border-color: #3498db;
            outline: none;
        }
        .vehicle-form button { 
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
        .vehicle-form button:hover { 
            background: #219150;
        }
        .vehicle-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .vehicle-table th, .vehicle-table td { 
            border: 1px solid #ddd; 
            padding: 15px; 
            text-align: left; 
        }
        .vehicle-table th { 
            background-color: #2c3e50; 
            color: white;
            font-weight: bold;
        }
        .vehicle-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .vehicle-table tr:hover {
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
        .vehicle-type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .type-car {
            background: #d4edda;
            color: #155724;
        }
        .type-motorcycle {
            background: #d1ecf1;
            color: #0c5460;
        }
        .type-truck {
            background: #fff3cd;
            color: #856404;
        }
        .type-suv {
            background: #e2e3e5;
            color: #383d41;
        }
        .type-van {
            background: #f8d7da;
            color: #721c24;
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
        .color-sample {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 3px;
            margin-right: 8px;
            vertical-align: middle;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <?php include("includes/header.php"); ?>
            <div class="page-header">
                <h1>🚗 Vehicles Management</h1>
                <div class="total-count">Total Vehicles: <strong><?php echo count($vehicles); ?></strong></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Add/Edit Vehicle Form -->
            <div class="vehicle-form">
                <h3><?php echo $edit_vehicle ? '✏️ Edit Vehicle' : '➕ Add New Vehicle'; ?></h3>
                <form method="POST">
                    <?php if ($edit_vehicle): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$edit_vehicle['id']; ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Plate Number *</label>
                            <input type="text" name="plate_number" placeholder="Plate Number" required style="width: 100%;"
                                   value="<?php echo isset($_POST['plate_number']) ? htmlspecialchars($_POST['plate_number']) : ($edit_vehicle['plate_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Vehicle Model *</label>
                            <input type="text" name="model" placeholder="Vehicle Model" required style="width: 100%;"
                                   value="<?php echo isset($_POST['model']) ? htmlspecialchars($_POST['model']) : ($edit_vehicle['model'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Color *</label>
                            <input type="text" name="color" placeholder="Color" required style="width: 100%;"
                                   value="<?php echo isset($_POST['color']) ? htmlspecialchars($_POST['color']) : ($edit_vehicle['color'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Vehicle Type *</label>
                            <select name="vehicle_type" required style="width: 100%;">
                                <option value="">Select Vehicle Type</option>
                                <option value="Car" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type'] == 'Car') || ($edit_vehicle && $edit_vehicle['vehicle_type'] == 'Car') ? 'selected' : ''; ?>>Car</option>
                                <option value="Motorcycle" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type'] == 'Motorcycle') || ($edit_vehicle && $edit_vehicle['vehicle_type'] == 'Motorcycle') ? 'selected' : ''; ?>>Motorcycle</option>
                                <option value="Truck" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type'] == 'Truck') || ($edit_vehicle && $edit_vehicle['vehicle_type'] == 'Truck') ? 'selected' : ''; ?>>Truck</option>
                                <option value="SUV" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type'] == 'SUV') || ($edit_vehicle && $edit_vehicle['vehicle_type'] == 'SUV') ? 'selected' : ''; ?>>SUV</option>
                                <option value="Van" <?php echo (isset($_POST['vehicle_type']) && $_POST['vehicle_type'] == 'Van') || ($edit_vehicle && $edit_vehicle['vehicle_type'] == 'Van') ? 'selected' : ''; ?>>Van</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>House *</label>
                            <select name="house_id" required style="width: 100%;">
                                <option value="">Select House</option>
                                <?php
                                $selectedHouseId = $_POST['house_id'] ?? ($edit_vehicle['house_id'] ?? '');
                                renderHouseOptions($houseGroups, $selectedHouseId);
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <?php if ($edit_vehicle): ?>
                        <button type="submit" name="update_vehicle">Update Vehicle</button>
                        <a href="vehicles.php" style="margin-left:10px;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_vehicle">Add Vehicle</button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Vehicles Table -->
            <h3>📋 Vehicle List</h3>
            <?php if (!empty($vehicles)): ?>
                <table class="vehicle-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Plate Number</th>
                            <th>Model</th>
                            <th>Color</th>
                            <th>Type</th>
                            <th>House Number</th>
                            <th>Owner Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vehicles as $index => $vehicle): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($vehicle['plate_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($vehicle['model']); ?></td>
                                <td>
                                    <span class="color-sample" style="background-color: <?php echo htmlspecialchars($vehicle['color']); ?>;"></span>
                                    <?php echo htmlspecialchars($vehicle['color']); ?>
                                </td>
                                <td>
                                    <span class="vehicle-type-badge type-<?php echo strtolower($vehicle['vehicle_type'] ?? 'car'); ?>">
                                        <?php echo htmlspecialchars($vehicle['vehicle_type'] ?? 'Car'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($vehicle['house_number']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['owner_name']); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $vehicle['id']; ?>" style="margin-right:8px;">✏️ Edit</a>
                                    <a href="?delete=<?php echo $vehicle['id']; ?>" class="delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete vehicle <?php echo htmlspecialchars($vehicle['plate_number']); ?>? This action cannot be undone.')">
                                       🗑️ Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p style="font-size: 18px; color: #7f8c8d;">No vehicles found. Add your first vehicle using the form above.</p>
                </div>
            <?php endif; ?>
    <?php include("includes/footer.php"); ?>
</body>
</html>
