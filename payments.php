<?php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/models/Payment.php';
require_once __DIR__ . '/models/House.php';
require_once __DIR__ . '/includes/house_options.php';
require_once __DIR__ . '/includes/house_validation.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: Login/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Models
$paymentModel = new PaymentModel($db);
$houseModel = new House($db);

$success = "";
$error = "";

// Edit state
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit_payment = null;

if ($edit_id > 0) {
    try {
        $edit_payment = $paymentModel->getById($edit_id);
    } catch (PDOException $exception) {
        $error = "Error loading payment: " . $exception->getMessage();
    }
}

// Handle Add Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $house_id = $_POST['house_id'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $due_month = $_POST['due_month'];
    $payment_type = $_POST['payment_type'];
    $status = $_POST['status'];
    $description = trim($_POST['description']);

    // Input validation
    if (empty($house_id) || empty($amount) || empty($payment_date) || empty($due_month) || empty($payment_type) || empty($status)) {
        $error = "Please fill in all required fields!";
    } elseif (!isHouseOccupied($db, (int) $house_id)) {
        $error = "Cannot record payment for an empty/vacant house. Assign a house owner first.";
    } else {
        try {
            $query = "INSERT INTO payments (house_id, amount, payment_date, due_month, payment_type, status, description, created_at) 
                      VALUES (:house_id, :amount, :payment_date, :due_month, :payment_type, :status, :description, NOW())";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":house_id", $house_id);
            $stmt->bindParam(":amount", $amount);
            $stmt->bindParam(":payment_date", $payment_date);
            $stmt->bindParam(":due_month", $due_month);
            $stmt->bindParam(":payment_type", $payment_type);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":description", $description);
            
            if ($stmt->execute()) {
                $success = "Payment added successfully!";
                addAuditLog($db, $_SESSION['user_id'], 'ADD_PAYMENT', "Added payment for house ID: $house_id - Amount: $amount");
            }
        } catch(PDOException $exception) {
            $error = "Error adding payment: " . $exception->getMessage();
        }
    }
}

// Handle Update Payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_payment'])) {
    $id = (int)$_POST['id'];
    $house_id = $_POST['house_id'];
    $amount = $_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $due_month = $_POST['due_month'];
    $payment_type = $_POST['payment_type'];
    $status = $_POST['status'];
    $description = trim($_POST['description']);

    if (empty($id) || empty($house_id) || empty($amount) || empty($payment_date) || empty($due_month) || empty($payment_type) || empty($status)) {
        $error = "Please fill in all required fields!";
    } elseif (!isHouseOccupied($db, (int) $house_id)) {
        $error = "Cannot assign payment to an empty/vacant house. Assign a house owner first.";
    } else {
        try {
            $updated = $paymentModel->update($id, $house_id, $amount, $payment_date, $due_month, $payment_type, $status, $description);
            if ($updated) {
                $success = "Payment updated successfully!";
                addAuditLog($db, $_SESSION['user_id'], 'UPDATE_PAYMENT', "Updated payment ID: $id");
                header("Location: payments.php");
                exit();
            } else {
                $error = "No changes made or payment not found.";
            }
        } catch(PDOException $exception) {
            $error = "Error updating payment: " . $exception->getMessage();
        }
    }
}

// Handle Soft Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    try {
        $query = "UPDATE payments SET is_deleted = 1, deleted_at = NOW() WHERE id = :id AND is_deleted = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            $success = "Payment deleted successfully!";
            addAuditLog($db, $_SESSION['user_id'], 'SOFT_DELETE_PAYMENT', "Soft deleted payment ID: $id");
        } else {
            $error = "Payment not found or already deleted!";
        }
    } catch(PDOException $exception) {
        $error = "Error deleting payment: " . $exception->getMessage();
    }
}

// Get all active payments
$payments = [];
try {
    $query = "SELECT p.*, h.house_number, h.owner_name 
              FROM payments p 
              LEFT JOIN houses h ON p.house_id = h.id 
              WHERE p.is_deleted = 0 
              ORDER BY p.payment_date DESC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $exception) {
    $error = "Error loading payments: " . $exception->getMessage();
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

// Generate due months (last 12 months)
$months = [];
for ($i = 0; $i < 12; $i++) {
    $months[] = date('F Y', strtotime("-$i months"));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Management - Subdivision Management</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .payment-form { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            margin-bottom: 30px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .payment-form h3 {
            margin-top: 0;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .payment-form input, .payment-form select, .payment-form textarea { 
            padding: 12px; 
            margin: 8px 5px; 
            border: 2px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px;
        }
        .payment-form input:focus, .payment-form select:focus, .payment-form textarea:focus {
            border-color: #3498db;
            outline: none;
        }
        .payment-form button { 
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
        .payment-form button:hover { 
            background: #219150;
        }
        .payment-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .payment-table th, .payment-table td { 
            border: 1px solid #ddd; 
            padding: 12px; 
            text-align: left; 
        }
        .payment-table th { 
            background-color: #2c3e50; 
            color: white;
            font-weight: bold;
        }
        .payment-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .payment-table tr:hover {
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
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .amount-paid {
            color: #27ae60;
            font-weight: bold;
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
    <?php include("includes/header.php"); ?>
            <div class="page-header">
                <h1>💰 Payments Management</h1>
                <div class="total-count">Total Payments: <strong><?php echo count($payments); ?></strong></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Add/Edit Payment Form -->
            <div class="payment-form">
                <h3><?php echo $edit_payment ? '✏️ Edit Payment' : '➕ Add New Payment'; ?></h3>
                <form method="POST">
                    <?php if ($edit_payment): ?>
                        <input type="hidden" name="id" value="<?php echo (int)$edit_payment['id']; ?>">
                    <?php endif; ?>
                    <div class="form-row">
                        <div class="form-group">
                            <label>House *</label>
                            <select name="house_id" required style="width: 100%;">
                                <option value="">Select House</option>
                                <?php
                                $selectedHouseId = $_POST['house_id'] ?? ($edit_payment['house_id'] ?? '');
                                renderHouseOptions($houseGroups, $selectedHouseId);
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Amount (₱) *</label>
                            <input type="number" name="amount" placeholder="0.00" step="0.01" required style="width: 100%;"
                                   value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ($edit_payment['amount'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Date *</label>
                            <input type="date" name="payment_date" required style="width: 100%;"
                                   value="<?php echo isset($_POST['payment_date']) ? htmlspecialchars($_POST['payment_date']) : ($edit_payment ? htmlspecialchars(date('Y-m-d', strtotime($edit_payment['payment_date']))) : date('Y-m-d')); ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Due Month *</label>
                            <select name="due_month" required style="width: 100%;">
                                <option value="">Select Due Month</option>
                                <?php foreach ($months as $month): ?>
                                    <option value="<?php echo $month; ?>" <?php echo (isset($_POST['due_month']) && $_POST['due_month'] == $month) || ($edit_payment && $edit_payment['due_month'] == $month) ? 'selected' : ''; ?>>
                                        <?php echo $month; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Payment Type *</label>
                            <select name="payment_type" required style="width: 100%;">
                                <option value="">Payment Type</option>
                                <option value="Monthly Due" <?php echo (isset($_POST['payment_type']) && $_POST['payment_type'] == 'Monthly Due') || ($edit_payment && $edit_payment['payment_type'] == 'Monthly Due') ? 'selected' : ''; ?>>Monthly Due</option>
                                <option value="Special Fee" <?php echo (isset($_POST['payment_type']) && $_POST['payment_type'] == 'Special Fee') || ($edit_payment && $edit_payment['payment_type'] == 'Special Fee') ? 'selected' : ''; ?>>Special Fee</option>
                                <option value="Penalty" <?php echo (isset($_POST['payment_type']) && $_POST['payment_type'] == 'Penalty') || ($edit_payment && $edit_payment['payment_type'] == 'Penalty') ? 'selected' : ''; ?>>Penalty</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" required style="width: 100%;">
                                <option value="">Status</option>
                                <option value="Paid" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Paid') || ($edit_payment && $edit_payment['status'] == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="Pending" <?php echo (isset($_POST['status']) && $_POST['status'] == 'Pending') || ($edit_payment && $edit_payment['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label>Description</label>
                            <textarea name="description" placeholder="Payment description (optional)" rows="2" style="width: 100%;"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ($edit_payment['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                    
                    <?php if ($edit_payment): ?>
                        <button type="submit" name="update_payment">Update Payment</button>
                        <a href="payments.php" style="margin-left:10px;">Cancel</a>
                    <?php else: ?>
                        <button type="submit" name="add_payment">Add Payment</button>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Payments Table -->
            <h3>📋 Payment Records</h3>
            <?php if (!empty($payments)): ?>
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>House Number</th>
                            <th>Owner</th>
                            <th>Amount</th>
                            <th>Payment Date</th>
                            <th>Due Month</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $index => $payment): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo htmlspecialchars($payment['house_number']); ?></td>
                                <td><?php echo htmlspecialchars($payment['owner_name']); ?></td>
                                <td class="amount-paid">₱<?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></td>
                                <td><?php echo htmlspecialchars($payment['due_month']); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($payment['status']); ?>">
                                        <?php echo htmlspecialchars($payment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($payment['description'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $payment['id']; ?>" style="margin-right:8px;">✏️ Edit</a>
                                    <a href="?delete=<?php echo $payment['id']; ?>" class="delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete this payment record? This action cannot be undone.')">
                                       🗑️ Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p style="font-size: 18px; color: #7f8c8d;">No payment records found. Add your first payment using the form above.</p>
                </div>
            <?php endif; ?>
    <?php include("includes/footer.php"); ?>
</body>
</html>
