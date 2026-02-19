<?php
require_once __DIR__ . '/BaseModel.php';

class House extends BaseModel {
    public function listActiveForSelect() {
        $query = "SELECT id, house_number, owner_name FROM houses WHERE is_deleted = 0 ORDER BY house_number";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listOccupiedForSelect() {
        $query = "SELECT id, house_number, owner_name
                  FROM houses
                  WHERE is_deleted = 0
                    AND (status = 'Occupied' OR TRIM(COALESCE(owner_name, '')) <> '')
                  ORDER BY house_number";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

