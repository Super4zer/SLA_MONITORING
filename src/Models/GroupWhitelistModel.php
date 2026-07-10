<?php

namespace App\Models;

use App\Config\Database;
use PDO;

class GroupWhitelistModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function isWhitelistedGroup(string $groupId): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM ts_group_whitelist 
            WHERE group_id = :group_id 
              AND is_active = 1
        ");
        $stmt->execute(['group_id' => $groupId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
