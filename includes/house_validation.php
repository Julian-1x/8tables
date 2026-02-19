<?php

function isHouseOccupied(PDO $db, int $houseId): bool
{
    $query = "SELECT COUNT(*) 
              FROM houses
              WHERE id = :id
                AND is_deleted = 0
                AND (status = 'Occupied' OR TRIM(COALESCE(owner_name, '')) <> '')";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $houseId]);
    return (int) $stmt->fetchColumn() > 0;
}

