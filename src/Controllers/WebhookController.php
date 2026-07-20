<?php

namespace App\Controllers;

use App\Models\SlaMonitoringModel;
use App\Models\StaffWhitelistModel;
use App\Models\GroupWhitelistModel;
use App\Models\Enums\SlaStatus;

class WebhookController
{
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
        // 1. Ambil raw input
        $rawPayload = file_get_contents('php://input');

        // Log untuk debugging
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

        // 2. Filter status perangkat
        if (isset($data['status']) && isset($data['deviceId']) && !isset($data['message'])) {
            return ['status' => 'ignored', 'message' => 'Device status event, not a chat message'];
        }

        // 3. Normalisasi Data (Perbaikan Mapping)
        $isGroup = (bool) ($data['isGroup'] ?? false);

        // ID Grup di payload Anda berada di $data['group']['sender']
        $groupId = $data['group']['group_id'] ?? null;
        $senderPhone = $data['group']['sender'] ?? null;
        $messageContent = $data['message'] ?? '';

        // Normalisasi nomor telepon
        if ($senderPhone) {
            $senderPhone = ltrim($senderPhone, '+');
        }

        // 4. Validasi Dasar
        if (!$isGroup || !$groupId || !$senderPhone) {
            return [
                'status' => 'ignored',
                'message' => 'Not a group message or missing required fields. isGroup=' . ($isGroup ? 'true' : 'false') . ', groupId=' . ($groupId ?? 'null')
            ];
        }

        // 5. Cek Whitelist Grup
        if (!$this->groupModel->isWhitelistedGroup($groupId)) {
            return ['status' => 'ignored', 'message' => 'Group not whitelisted: ' . $groupId];
        }

        $timeNow = date('Y-m-d H:i:s');
        $isStaff = $this->staffModel->isStaff($senderPhone);

        // 6. Logika Bisnis (Staff vs Klien)
        if ($isStaff) {
            // Staff membalas: Update SLA
            $pendingComplaints = $this->slaModel->getAllUnrespondedComplaints($groupId);

            if (!empty($pendingComplaints)) {
                foreach ($pendingComplaints as $complaint) {
                    $timeReceived = $complaint['time_received'];
                    $slaSeconds = strtotime($timeNow) - strtotime($timeReceived);

                    // SLA 180 detik
                    $statusSla = $slaSeconds <= 180 ? SlaStatus::HIJAU : SlaStatus::MERAH;

                    $this->slaModel->updateResponse(
                        $complaint['id_monitoring'],
                        $senderPhone,
                        $timeNow,
                        $slaSeconds,
                        $statusSla
                    );
                }
                return ['status' => 'success', 'message' => count($pendingComplaints) . ' SLA record(s) updated'];
            }
            return ['status' => 'ignored', 'message' => 'No pending complaint in this group'];

        } else {
            // Klien bertanya: Insert baru
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