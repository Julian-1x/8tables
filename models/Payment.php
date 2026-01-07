<?php
require_once __DIR__ . '/BaseModel.php';

class PaymentModel extends BaseModel {
    public function getById($id) {
        $query = "SELECT * FROM payments WHERE id = :id AND is_deleted = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function create($houseId, $amount, $paymentDate, $dueMonth, $paymentType, $status, $description) {
        $query = "INSERT INTO payments (house_id, amount, payment_date, due_month, payment_type, status, description, created_at)
                  VALUES (:house_id, :amount, :payment_date, :due_month, :payment_type, :status, :description, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":house_id", $houseId);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":payment_date", $paymentDate);
        $stmt->bindParam(":due_month", $dueMonth);
        $stmt->bindParam(":payment_type", $paymentType);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":description", $description);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    public function update($id, $houseId, $amount, $paymentDate, $dueMonth, $paymentType, $status, $description) {
        $query = "UPDATE payments SET house_id = :house_id, amount = :amount, payment_date = :payment_date, due_month = :due_month, payment_type = :payment_type, status = :status, description = :description, updated_at = NOW() WHERE id = :id AND is_deleted = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":house_id", $houseId);
        $stmt->bindParam(":amount", $amount);
        $stmt->bindParam(":payment_date", $paymentDate);
        $stmt->bindParam(":due_month", $dueMonth);
        $stmt->bindParam(":payment_type", $paymentType);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function softDelete($id) {
        $query = "UPDATE payments SET is_deleted = 1, deleted_at = NOW() WHERE id = :id AND is_deleted = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function listActiveWithHouse() {
        $query = "SELECT p.*, h.house_number, h.owner_name
                  FROM payments p
                  LEFT JOIN houses h ON p.house_id = h.id
                  WHERE p.is_deleted = 0
                  ORDER BY p.payment_date DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

