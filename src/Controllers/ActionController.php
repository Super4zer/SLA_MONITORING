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

    public function resolve(string $id): array
    {
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Invalid ID'];
        }

        $success = $this->slaModel->resolveByExplanation($id);

        if ($success) {
            return ['status' => 'success', 'message' => 'Complaint resolved by explanation'];
        }

        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to resolve complaint'];
    }

    public function escalate(string $id): array
    {
        $id = (int)$id;
        if ($id <= 0) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Invalid ID'];
        }

        // Get JSON body payload
        $rawPayload = file_get_contents('php://input');
        $data = json_decode($rawPayload, true) ?: $_POST;

        $clientName = $data['client_name'] ?? 'Unknown';
        $complaintText = $data['complaint'] ?? 'No text provided';

        // TODO: Call actual log.klikdsi API to escalate and get log ID.
        // For now, we simulate a response ID from the external API.
        $simulatedLogKlikdsiId = rand(1000, 9999);

        $success = $this->slaModel->escalateComplaint($id, $simulatedLogKlikdsiId);

        if ($success) {
            return [
                'status' => 'success', 
                'message' => 'Complaint escalated successfully',
                'log_klikdsi_id' => $simulatedLogKlikdsiId
            ];
        }

        http_response_code(500);
        return ['status' => 'error', 'message' => 'Failed to escalate complaint'];
    }
}
