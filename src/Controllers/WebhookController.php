<?php

namespace App\Controllers;

use App\Models\SlaMonitoringModel;
use App\Models\StaffWhitelistModel;
use App\Models\Enums\SlaStatus;

class WebhookController
{
    private SlaMonitoringModel $slaModel;
    private StaffWhitelistModel $staffModel;

    public function __construct()
    {
        $this->slaModel = new SlaMonitoringModel();
        $this->staffModel = new StaffWhitelistModel();
    }

    public function handle(): array
    {
        // TODO(security): Validate Wablas webhook signature/token here
        // e.g. check $_SERVER['HTTP_X_WABLAS_SIGNATURE'] or similar.
        
        $rawPayload = file_get_contents('php://input');
        
        // Save raw payload to log for debugging
        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logDir . '/webhook.log', "[" . date('Y-m-d H:i:s') . "] " . $rawPayload . PHP_EOL, FILE_APPEND);

        $data = json_decode($rawPayload, true);

        if (!$data) {
            // Also check $_POST if wablas sends as form-data instead of JSON
            $data = $_POST;
        }

        if (empty($data)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Empty payload'];
        }

        // Extract necessary fields based on typical Wablas webhook structure
        // Adjust these field names based on actual Wablas documentation
        $isGroup = $data['isGroup'] ?? false;
        $groupId = $data['groupId'] ?? null;
        $senderPhone = $data['phone'] ?? null;
        $messageContent = $data['message'] ?? '';

        // Normalize phone number (remove + if any)
        if ($senderPhone) {
            $senderPhone = ltrim($senderPhone, '+');
        }

        if (!$isGroup || !$groupId || !$senderPhone) {
            // We only care about group messages for this SLA monitoring
            return ['status' => 'ignored', 'message' => 'Not a group message or missing required fields'];
        }

        $timeNow = date('Y-m-d H:i:s');

        // Check if sender is a staff member
        $isStaff = $this->staffModel->isStaff($senderPhone);

        if ($isStaff) {
            // It's a reply from CS Staff
            $lastComplaint = $this->slaModel->getLatestUnrespondedComplaint($groupId);

            if ($lastComplaint) {
                $timeReceived = $lastComplaint['time_received'];
                $slaSeconds = strtotime($timeNow) - strtotime($timeReceived);
                
                $statusSla = $slaSeconds <= 180 ? SlaStatus::HIJAU : SlaStatus::MERAH;

                $this->slaModel->updateResponse(
                    $lastComplaint['id_monitoring'],
                    $senderPhone,
                    $timeNow,
                    $slaSeconds,
                    $statusSla
                );

                return ['status' => 'success', 'message' => 'SLA updated'];
            }

            return ['status' => 'ignored', 'message' => 'No pending complaint found for this group'];
            
        } else {
            // It's a message from a client
            $this->slaModel->insertComplaint(
                $groupId,
                $senderPhone,
                $messageContent,
                $timeNow
            );

            return ['status' => 'success', 'message' => 'Complaint logged'];
        }
    }
}
