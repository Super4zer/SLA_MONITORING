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

    // Mengambil semua data grup
    public function getAllGroups(): array
    {
        $stmt = $this->db->query("SELECT * FROM ts_group_whitelist ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Menyimpan data grup baru
    public function createGroup(string $groupId, string $groupName): bool
    {
        $stmt = $this->db->prepare("INSERT INTO ts_group_whitelist (group_id, group_name, is_active) VALUES (?, ?, 1)");
        return $stmt->execute([$groupId, $groupName]);
    }

    public function updateGroup(int $id, string $groupId, string $groupName): bool
    {
        $stmt = $this->db->prepare("UPDATE ts_group_whitelist SET group_id = ?, group_name = ? WHERE id = ?");
        return $stmt->execute([$groupId, $groupName, $id]);
    }

    public function deleteGroup(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM ts_group_whitelist WHERE id = ?");
        return $stmt->execute([$id]);
    }
}