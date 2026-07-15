<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class StaffWhitelistModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function isStaff(string $phoneNumber): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM cs_staff_whitelist 
            WHERE phone_number = :phone_number 
              AND is_active = 1
        ");
        $stmt->execute(['phone_number' => $phoneNumber]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
