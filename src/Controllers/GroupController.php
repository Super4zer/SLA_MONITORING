<?php
namespace App\Controllers;

use App\Models\GroupWhitelistModel;

class GroupController
{
    private GroupWhitelistModel $groupModel;

    public function __construct()
    {
        $this->groupModel = new GroupWhitelistModel();
    }

    // API GET: Ambil data
    public function getGroups(): void
    {
        header('Content-Type: application/json');
        $data = $this->groupModel->getAllGroups();
        
        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
        exit;
    }

   public function storeGroup(): void
    {
        header('Content-Type: application/json');
        
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $groupId = $input['group_id'] ?? '';
            $groupName = $input['group_name'] ?? '';

            if (empty($groupId) || empty($groupName)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID Grub dan Nama Grub wajib diisi']);
                return;
            }

            $success = $this->groupModel->createGroup($groupId, $groupName);

            if ($success) {
                echo json_encode(['status' => 'success', 'message' => 'Grub berhasil ditambahkan']);
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

public function updateGroup(): void
{
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $groupId = $input['group_id'] ?? '';
    $groupName = $input['group_name'] ?? '';

    if ($id <= 0 || empty($groupId) || empty($groupName)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Parameter tidak valid untuk update']);
        exit;
    }

    $success = $this->groupModel->updateGroup($id, $groupId, $groupName);
    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Data grup sukses diperbarui']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengubah data ke database']);
    }
    exit;
}

public function deleteGroup(): void
{
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);
    
    $id = isset($input['id']) ? (int)$input['id'] : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID tidak ditemukan']);
        exit;
    }

    $success = $this->groupModel->deleteGroup($id);
    if ($success) {
        echo json_encode(['status' => 'success', 'message' => 'Data grup berhasil dihapus']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus data dari database']);
    }
    exit;
}
}