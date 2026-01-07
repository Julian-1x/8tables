<?php
class BaseModel {
    /** @var PDO */
    protected $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }
}
?>

