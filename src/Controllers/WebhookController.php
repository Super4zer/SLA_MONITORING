<?php

namespace App\Controllers;

use App\Models\SlaMonitoringModel;
use App\Models\StaffWhitelistModel;
use App\Models\GroupWhitelistModel;
use App\Models\Enums\SlaStatus;

class WebhookController
{
    // Klien wajib mengawali pesan dengan command ini supaya tercatat sebagai komplain.
    // Chat basa-basi biasa akan diabaikan, tidak numpuk di database.
    private const COMPLAINT_TRIGGER = '#komplain';

    private SlaMonitoringModel $slaModel;
    private StaffWhitelistModel $staffModel;
    private GroupWhitelistModel $groupModel;

    public function __construct()
    {
        $this->slaModel = new SlaMonitoringModel();
        $this->staffModel = new StaffWhitelistModel();
        $this->groupModel = new GroupWhitelistModel();
    }

    public function handle(): array
    {
        $rawPayload = file_get_contents('php://input');

        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        file_put_contents($logDir . '/webhook.log', "[" . date('Y-m-d H:i:s') . "] " . $rawPayload . PHP_EOL, FILE_APPEND);

        $data = json_decode($rawPayload, true);

        if (!$data) {
            $data = $_POST;
        }

        if (empty($data)) {
            http_response_code(400);
            return ['status' => 'error', 'message' => 'Empty payload'];
        }

        if (isset($data['status']) && isset($data['deviceId']) && !isset($data['message'])) {
            return ['status' => 'ignored', 'message' => 'Device status event, not a chat message'];
        }

        $isGroup = (bool) ($data['isGroup'] ?? false);
        $groupId = $data['group']['group_id'] ?? null;
        $senderPhone = $data['group']['sender'] ?? null;
        $messageContent = $data['message'] ?? '';

        if ($senderPhone) {
            $senderPhone = ltrim($senderPhone, '+');
        }

        if (!$isGroup || !$groupId || !$senderPhone) {
            return [
                'status' => 'ignored',
                'message' => 'Not a group message or missing required fields. isGroup=' . ($isGroup ? 'true' : 'false') . ', groupId=' . ($groupId ?? 'null')
            ];
        }

        if (!$this->groupModel->isWhitelistedGroup($groupId)) {
            return ['status' => 'ignored', 'message' => 'Group not whitelisted: ' . $groupId];
        }

        $timeNow = date('Y-m-d H:i:s');
        $isStaff = $this->staffModel->isStaff($senderPhone);

        if ($isStaff) {
            $pendingComplaints = $this->slaModel->getAllUnrespondedComplaints($groupId);

            if (!empty($pendingComplaints)) {
                foreach ($pendingComplaints as $complaint) {
                    $timeReceived = $complaint['time_received'];
                    $slaSeconds = strtotime($timeNow) - strtotime($timeReceived);
                    $statusSla = $slaSeconds <= 180 ? SlaStatus::HIJAU : SlaStatus::MERAH;

                    $this->slaModel->updateResponse(
                        $complaint['id_monitoring'],
                        $senderPhone,
                        $timeNow,
                        $slaSeconds,
                        $statusSla,
                        $messageContent
                    );

                    if ($slaSeconds > 180) {
                        $this->slaModel->resolveByExplanation($complaint['id_monitoring']);
                    }
                }

                return ['status' => 'success', 'message' => count($pendingComplaints) . ' SLA record(s) updated'];
            }

            return ['status' => 'ignored', 'message' => 'No pending complaint in this group'];
        }

        // Pesan dari klien — HANYA simpan sebagai komplain kalau diawali command tertentu.
        $trimmedMessage = trim($messageContent);

        if (stripos($trimmedMessage, self::COMPLAINT_TRIGGER) !== 0) {
            return ['status' => 'ignored', 'message' => 'Pesan tidak diawali command "' . self::COMPLAINT_TRIGGER . '", diabaikan.'];
        }

        $cleanMessage = trim(substr($trimmedMessage, strlen(self::COMPLAINT_TRIGGER)));
        if ($cleanMessage === '') {
            $cleanMessage = $trimmedMessage;
        }

        $this->slaModel->insertComplaint(
            $groupId,
            $senderPhone,
            $cleanMessage,
            $timeNow
        );

        return ['status' => 'success', 'message' => 'Complaint logged'];
    }
}
