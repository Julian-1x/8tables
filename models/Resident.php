<?php
require_once __DIR__ . '/BaseModel.php';

class Resident extends BaseModel {
    public function getById($id) {
        $query = "SELECT * FROM residents WHERE id = :id AND is_deleted = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $phone, $houseId, $relationship) {
        $query = "INSERT INTO residents (name, phone, house_id, relationship, created_at) VALUES (:name, :phone, :house_id, :relationship, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":house_id", $houseId);
        $stmt->bindParam(":relationship", $relationship);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    public function update($id, $name, $phone, $houseId, $relationship) {
        $query = "UPDATE residents SET name = :name, phone = :phone, house_id = :house_id, relationship = :relationship, updated_at = NOW() WHERE id = :id AND is_deleted = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":phone", $phone);
        $stmt->bindParam(":house_id", $houseId);
        $stmt->bindParam(":relationship", $relationship);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function softDelete($id) {
        $query = "UPDATE residents SET is_deleted = 1, deleted_at = NOW() WHERE id = :id AND is_deleted = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function listActiveWithHouse() {
        $query = "SELECT r.*, h.house_number, h.owner_name
                  FROM residents r
                  LEFT JOIN houses h ON r.house_id = h.id
                  WHERE r.is_deleted = 0
                  ORDER BY r.name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

