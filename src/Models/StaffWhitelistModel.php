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

    // Mengambil semua data staff CS
    public function getAllStaff(): array
    {
        $stmt = $this->db->query("SELECT * FROM cs_staff_whitelist ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mencari staff CS berdasarkan nama atau nomor HP
    public function searchStaff(string $keyword): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM cs_staff_whitelist 
            WHERE staff_name LIKE :keyword 
               OR phone_number LIKE :keyword
            ORDER BY id DESC
        ");
        $stmt->execute(['keyword' => '%' . $keyword . '%']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Cek apakah nomor HP sudah terdaftar (untuk validasi duplikat)
    public function phoneExists(string $phoneNumber, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM cs_staff_whitelist WHERE phone_number = :phone_number";
        $params = ['phone_number' => $phoneNumber];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    // Menyimpan data staff CS baru
    public function createStaff(string $phoneNumber, string $staffName): bool
    {
        $stmt = $this->db->prepare("INSERT INTO cs_staff_whitelist (phone_number, staff_name, is_active) VALUES (?, ?, 1)");
        return $stmt->execute([$phoneNumber, $staffName]);
    }

    public function updateStaff(int $id, string $phoneNumber, string $staffName): bool
    {
        $stmt = $this->db->prepare("UPDATE cs_staff_whitelist SET phone_number = ?, staff_name = ? WHERE id = ?");
        return $stmt->execute([$phoneNumber, $staffName, $id]);
    }

    // Mengaktifkan / menonaktifkan staff tanpa menghapus datanya
    public function toggleActive(int $id, bool $isActive): bool
    {
        $stmt = $this->db->prepare("UPDATE cs_staff_whitelist SET is_active = ? WHERE id = ?");
        return $stmt->execute([$isActive ? 1 : 0, $id]);
    }

    public function deleteStaff(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM cs_staff_whitelist WHERE id = ?");
        return $stmt->execute([$id]);
    }
}