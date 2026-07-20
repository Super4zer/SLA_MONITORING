<?php
namespace App\Controllers;

use App\Models\StaffWhitelistModel;

class StaffController
{
    private StaffWhitelistModel $staffModel;

    public function __construct()
    {
        $this->staffModel = new StaffWhitelistModel();
    }

    // API GET: Ambil semua data staff, atau cari berdasarkan keyword (?q=...)
    public function getStaff(): void
    {
        header('Content-Type: application/json');

        $keyword = trim($_GET['q'] ?? '');
        $data = $keyword !== ''
            ? $this->staffModel->searchStaff($keyword)
            : $this->staffModel->getAllStaff();

        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
        exit;
    }

    public function storeStaff(): void
    {
        header('Content-Type: application/json');

        try {
            $input = json_decode(file_get_contents('php://input'), true);

            $phoneNumber = trim($input['phone_number'] ?? '');
            $staffName = trim($input['staff_name'] ?? '');

            // Normalisasi nomor: hanya digit, buang +/spasi/strip
            $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

            if (empty($phoneNumber) || empty($staffName)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Nomor HP dan Nama Staff wajib diisi']);
                return;
            }

            if ($this->staffModel->phoneExists($phoneNumber)) {
                http_response_code(409);
                echo json_encode(['status' => 'error', 'message' => 'Nomor HP ini sudah terdaftar sebagai staff CS']);
                return;
            }

            $success = $this->staffModel->createStaff($phoneNumber, $staffName);

            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Staff CS berhasil ditambahkan']);
            } else {
                http_response_code(500);
                echo json_encode(['status' => 'error', 'message' => 'Gagal mengeksekusi query database']);
            }

        } catch (\Throwable $th) {
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'SYSTEM ERROR: ' . $th->getMessage(),
                'file' => basename($th->getFile()) . ' (Baris ' . $th->getLine() . ')'
            ]);
        }
        exit;
    }

    public function updateStaff(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $phoneNumber = preg_replace('/\D/', '', trim($input['phone_number'] ?? ''));
        $staffName = trim($input['staff_name'] ?? '');

        if ($id <= 0 || empty($phoneNumber) || empty($staffName)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid untuk update']);
            exit;
        }

        if ($this->staffModel->phoneExists($phoneNumber, $id)) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Nomor HP ini sudah dipakai staff CS lain']);
            exit;
        }

        $success = $this->staffModel->updateStaff($id, $phoneNumber, $staffName);
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Data staff CS sukses diperbarui']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah data ke database']);
        }
        exit;
    }

    // Toggle aktif / nonaktif staff (tanpa hapus permanen)
    public function toggleStaff(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        $id = isset($input['id']) ? (int)$input['id'] : 0;
        $isActive = !empty($input['is_active']);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan']);
            exit;
        }

        $success = $this->staffModel->toggleActive($id, $isActive);
        if ($success) {
            echo json_encode([
                'status' => 'success',
                'message' => $isActive ? 'Staff CS diaktifkan kembali' : 'Staff CS dinonaktifkan'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah status staff']);
        }
        exit;
    }

    public function deleteStaff(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);

        $id = isset($input['id']) ? (int)$input['id'] : 0;

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan']);
            exit;
        }

        $success = $this->staffModel->deleteStaff($id);
        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Data staff CS berhasil dihapus']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data dari database']);
        }
        exit;
    }
}