<?php
class Database {
    private $host = "localhost";
    private $db_name = "subdivision_db";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Don't display errors to users in production
            error_log("Database connection error: " . $exception->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
        return $this->conn;
    }
}

// Global function to add audit logs
function addAuditLog($db, $user_id, $action, $details) {
    try {
        $query = "INSERT INTO audit_logs (user_id, action, details, ip_address, created_at) 
                  VALUES (:user_id, :action, :details, :ip_address, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":details", $details);
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt->bindParam(":ip_address", $ip_address);
        return $stmt->execute();
    } catch(PDOException $exception) {
        error_log("Audit log error: " . $exception->getMessage());
        return false;
    }
}

// Optional: Database connection helper function
function getDBConnection() {
    $database = new Database();
    return $database->getConnection();
}
?>