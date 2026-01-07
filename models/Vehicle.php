<?php
require_once __DIR__ . '/BaseModel.php';

class VehicleModel extends BaseModel {
    public function getById($id) {
        $query = "SELECT * FROM vehicles WHERE id = :id AND is_deleted = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function create($plateNumber, $model, $color, $vehicleType, $houseId) {
        $query = "INSERT INTO vehicles (plate_number, model, color, vehicle_type, house_id, created_at)
                  VALUES (:plate_number, :model, :color, :vehicle_type, :house_id, NOW())";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":plate_number", $plateNumber);
        $stmt->bindParam(":model", $model);
        $stmt->bindParam(":color", $color);
        $stmt->bindParam(":vehicle_type", $vehicleType);
        $stmt->bindParam(":house_id", $houseId);
        $stmt->execute();
        return (int)$this->db->lastInsertId();
    }

    public function update($id, $plateNumber, $model, $color, $vehicleType, $houseId) {
        $query = "UPDATE vehicles SET plate_number = :plate_number, model = :model, color = :color, vehicle_type = :vehicle_type, house_id = :house_id, updated_at = NOW() WHERE id = :id AND is_deleted = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":plate_number", $plateNumber);
        $stmt->bindParam(":model", $model);
        $stmt->bindParam(":color", $color);
        $stmt->bindParam(":vehicle_type", $vehicleType);
        $stmt->bindParam(":house_id", $houseId);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function softDelete($id) {
        $query = "UPDATE vehicles SET is_deleted = 1, deleted_at = NOW() WHERE id = :id AND is_deleted = 0";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    public function listActiveWithHouse() {
        $query = "SELECT v.*, h.house_number, h.owner_name
                  FROM vehicles v
                  LEFT JOIN houses h ON v.house_id = h.id
                  WHERE v.is_deleted = 0
                  ORDER BY v.plate_number";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

