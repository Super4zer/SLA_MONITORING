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
            SELECT id FROM ts_group_whitelist 
            WHERE group_id = :group_id AND is_active = 1
            LIMIT 1
        ");
        $stmt->execute(['group_id' => $groupId]);

        return (bool) $stmt->fetch();
    }
}
