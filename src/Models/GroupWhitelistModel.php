<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class GroupWhitelistModel
{
    private PDO $db;

    public function __construct()
    {
        // Pastikan Anda sudah memiliki class koneksi Database di App\Config\Database
        $this->db = Database::getConnection(); 
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
    // Simpan fungsi ini di dalam class GroupWhitelistModel

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