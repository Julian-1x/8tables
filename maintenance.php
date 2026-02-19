<?php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/includes/house_options.php';
require_once __DIR__ . '/includes/house_validation.php';

if (!isset($_SESSION['username']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: Login/login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success = "";
$error = "";

/* ------------ Helper: Audit Log Integration ------------- */
function addAuditSafe($db, $userId, $action, $desc) {
    if (!function_exists('addAuditLog')) {
        // fallback logging if audit_log helper isn't included in scope
        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, description, ip_address, created_at)
                              VALUES (:uid,:action,:desc,:ip,NOW())");
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->execute([
            ":uid" => $userId,
            ":action" => $action,
            ":desc" => $desc,
            ":ip" => $ip
        ]);
    } else {
        addAuditLog($db, $userId, $action, $desc);
    }
}

/* ---------------- Handle New Maintenance Request ---------------- */
if (isset($_POST['submit_request'])) {
    try {
        $houseId = (int) ($_POST['house_id'] ?? 0);
        if ($houseId <= 0 || !isHouseOccupied($db, $houseId)) {
            throw new Exception("Cannot create maintenance request for an empty/vacant house.");
        }

        $query = "INSERT INTO maintenance_requests 
                    (house_id, resident_id, title, description, priority, status)
                  VALUES (:house_id, :resident_id, :title, :description, :priority, :status)";
        $stmt = $db->prepare($query);

        $stmt->execute([
            ":house_id" => $houseId,
            ":resident_id" => !empty($_POST['resident_id']) ? $_POST['resident_id'] : null,
            ":title" => $_POST['title'],
            ":description" => $_POST['description'],
            ":priority" => $_POST['priority'],
            ":status" => "Pending"
        ]);

        $success = "Maintenance request created successfully.";
        addAuditSafe($db, $_SESSION['user_id'], "CREATE_MAINTENANCE", "Created maintenance request: ".$_POST['title']);
    } catch (Exception $e) {
        $error = "Failed to create request: " . $e->getMessage();
    }
}

/* ---------------- Soft Delete (Archive) ---------------- */
if (isset($_GET['delete'])) {
    try {
        $query = "UPDATE maintenance_requests 
                  SET is_deleted = 1, deleted_at = NOW()
                  WHERE id = :id AND is_deleted = 0";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $_GET['delete']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $success = "Maintenance request archived.";
            addAuditSafe($db, $_SESSION['user_id'], "ARCHIVE_MAINTENANCE", "Archived maintenance ID ".$_GET['delete']);
        } else {
            $error = "Request already archived or not found.";
        }
    } catch (Exception $e) {
        $error = "Archive error: " . $e->getMessage();
    }
}

/* ---------------- Fetch Active Requests ---------------- */
$query = "SELECT m.*, 
          h.house_number,
          r.name AS resident_name
          FROM maintenance_requests m
          LEFT JOIN houses h ON m.house_id = h.id
          LEFT JOIN residents r ON m.resident_id = r.id
          WHERE m.is_deleted = 0
          ORDER BY m.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ---------------- Fetch Houses & Residents For Form ---------------- */
$houses = $db->query("SELECT id, house_number, owner_name
                      FROM houses
                      WHERE is_deleted = 0
                        AND (status = 'Occupied' OR TRIM(COALESCE(owner_name, '')) <> '')
                      ORDER BY house_number ASC")
             ->fetchAll(PDO::FETCH_ASSOC);
$houseGroups = groupHousesForDropdown($houses);

$residents = $db->query("SELECT id, name FROM residents WHERE is_deleted = 0 ORDER BY name ASC")
                ->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Requests - Subdivision MS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
<div class="container">
    <?php include("includes/header.php"); ?>

    <div class="main-content">
        <div class="page-header">
            <h1>🛠️ Maintenance Requests</h1>
        </div>

        <?php if ($success): ?>
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Create Request Form -->
        <form method="POST" style="padding:20px; margin-bottom:25px;">
            <h3>Create Maintenance Request</h3>

            <label>House</label>
            <select name="house_id" required>
                <option value="">Select house</option>
                <?php renderHouseOptions($houseGroups, $_POST['house_id'] ?? ''); ?>
            </select>

            <label>Resident (optional)</label>
            <select name="resident_id">
                <option value="">Unassigned</option>
                <?php foreach ($residents as $r): ?>
                    <option value="<?php echo $r['id']; ?>">
                        <?php echo htmlspecialchars($r['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Title</label>
            <input type="text" name="title" required>

            <label>Description</label>
            <textarea name="description" rows="4" required></textarea>

            <label>Priority</label>
            <select name="priority">
                <option>Low</option>
                <option>Medium</option>
                <option>High</option>
            </select>

            <button type="submit" name="submit_request">Submit Request</button>
        </form>

        <!-- Requests Table -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>House</th>
                    <th>Resident</th>
                    <th>Title</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($requests as $row): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo htmlspecialchars($row['house_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['resident_name'] ?? '—'); ?></td>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><span class="status-badge"><?php echo $row['priority']; ?></span></td>
                    <td><span class="status-badge"><?php echo $row['status']; ?></span></td>
                    <td><?php echo date("M j, Y H:i", strtotime($row['created_at'])); ?></td>

                    <td>
                        <a class="delete-btn"
                           href="?delete=<?php echo $row['id']; ?>"
                           onclick="return confirm('Archive this maintenance request? It can be restored in Archive Manager.')">
                           Archive
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
</div>

<?php include("includes/footer.php"); ?>
</body>
</html>
