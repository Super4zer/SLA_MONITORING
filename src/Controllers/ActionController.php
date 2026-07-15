<?php

namespace App\Controllers;

use App\Models\SlaMonitoringModel;

class ActionController
{
    private SlaMonitoringModel $slaModel;

    public function __construct()
    {
        $this->slaModel = new SlaMonitoringModel();
    }

    // --- TAMBAHKAN FUNGSI INI UNTUK LOGIN DUMMY ---
    public function login(): void
    {
        header('Content-Type: application/json');
        
        // Membaca data JSON dari fetch API
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Mulai sesi jika belum ada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Simulasi sukses login tanpa cek database
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'developer';

        echo json_encode([
            'success' => true, 
            'redirect' => '/dashboard'
        ]);
        exit;
    }

    // --- FUNGSI EXISTING TETAP SAMA, TAPI UBAH RETURN MENJADI ECHO JSON ---
    public function resolve(string $id): void
    {
        header('Content-Type: application/json');
        $id = (int)$id;
        
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            return;
        }

        $success = $this->slaModel->resolveByExplanation($id);

        if ($success) {
            echo json_encode(['status' => 'success', 'message' => 'Complaint resolved by explanation']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to resolve complaint']);
        }
    }

    public function escalate(string $id): void
    {
        header('Content-Type: application/json');
        $id = (int)$id;
        
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
            return;
        }

        $rawPayload = file_get_contents('php://input');
        $data = json_decode($rawPayload, true) ?: $_POST;

        $clientName = $data['client_name'] ?? 'Unknown';
        $complaintText = $data['complaint'] ?? 'No text provided';

        $simulatedLogKlikdsiId = rand(1000, 9999);
        $success = $this->slaModel->escalateComplaint($id, $simulatedLogKlikdsiId);

        if ($success) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Complaint escalated successfully',
                'log_klikdsi_id' => $simulatedLogKlikdsiId
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to escalate complaint']);
        }
    }
}