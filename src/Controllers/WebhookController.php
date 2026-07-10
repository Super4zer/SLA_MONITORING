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
        // TODO(security): Validasi keamanan Webhook Wablas.
        // Wablas belum tentu menggunakan header "Signature". Terkadang 
        // validasi cukup dilakukan dengan memberikan URL Webhook yang memiliki 
        // token rahasia (contoh: /webhook/wablas?token=SECRET_ABC) 
        // atau jika Wablas mengirim `secret` di payload, validasi di sini.
        
        $rawPayload = file_get_contents('php://input');
        
        // Save raw payload to log for debugging
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

        // -------------------------------------------------------
        // Payload Wablas (dari log nyata) memiliki struktur:
        // {
        //   "id": "...",
        //   "pushName": "Nama Pengirim",
        //   "isGroup": true/false,
        //   "group": {
        //     "sender": "6281234567890-1234567890@g.us",   <-- group ID
        //     "subject": "Nama Grup",
        //     ...
        //   },
        //   "message": "isi pesan",
        //   "phone": "6281234567890",                      <-- nomor pengirim
        //   "isFromMe": false,
        //   ...
        // }
        // -------------------------------------------------------

        // Abaikan payload status device (connected/disconnected), bukan pesan chat
        if (isset($data['status']) && isset($data['deviceId']) && !isset($data['message'])) {
            return ['status' => 'ignored', 'message' => 'Device status event, not a chat message'];
        }

        // Ambil field-field yang kita butuhkan sesuai format nyata Wablas
        $isGroup  = (bool)($data['isGroup'] ?? false);
        $groupObj = $data['group'] ?? [];
        $groupId  = $groupObj['sender'] ?? null;   // group ID ada di group.sender
        $senderPhone  = $data['phone'] ?? null;    // nomor pengirim ada di phone
        $messageContent = $data['message'] ?? '';

        // Normalize phone number (hapus + jika ada)
        if ($senderPhone) {
            $senderPhone = ltrim($senderPhone, '+');
        }

        // Abaikan jika bukan pesan dari grup, atau field penting kosong
        if (!$isGroup || !$groupId || !$senderPhone) {
            return ['status' => 'ignored', 'message' => 'Not a group message or missing required fields. isGroup=' . ($isGroup ? 'true' : 'false') . ', groupId=' . ($groupId ?? 'null')];
        }

        // Lapis Kedua: Cek apakah grup ini ada di whitelist
        if (!$this->groupModel->isWhitelistedGroup($groupId)) {
            return ['status' => 'ignored', 'message' => 'Group not whitelisted: ' . $groupId];
        }

        $timeNow = date('Y-m-d H:i:s');

        // Cek apakah pengirim adalah staff CS
        $isStaff = $this->staffModel->isStaff($senderPhone);

        if ($isStaff) {
            // Balasan dari CS Staff — update semua komplain yang pending di grup ini
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
                        $statusSla
                    );
                }

                return ['status' => 'success', 'message' => count($pendingComplaints) . ' SLA record(s) updated'];
            }

            return ['status' => 'ignored', 'message' => 'No pending complaint in this group'];
            
        } else {
            // Pesan dari klien — simpan sebagai komplain baru
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
