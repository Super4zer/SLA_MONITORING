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
        // TODO(security): Validasi keamanan Webhook Wablas.
        // Wablas belum tentu menggunakan header "Signature". Terkadang
        // validasi cukup dilakukan dengan memberikan URL Webhook yang memiliki
        // token rahasia (contoh: /webhook/wablas?token=SECRET_ABC)
        // atau jika Wablas mengirim `secret` di payload, validasi di sini.

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

        // -------------------------------------------------------
        // Payload Wablas (dari log nyata) memiliki struktur:
        // {
        //   "id": "...",
        //   "pushName": "Nama Pengirim",
        //   "isGroup": true/false,
        //   "group": {
        //     "group_id": "...",   <-- ID grup asli
        //     "sender": "6281234567890",   <-- nomor pengirim
        //     "subject": "Nama Grup",
        //     ...
        //   },
        //   "message": "isi pesan",
        //   "phone": "...",  <-- sama dengan group_id, BUKAN nomor pengirim
        //   "isFromMe": false,
        //   ...
        // }
        // -------------------------------------------------------

        // Abaikan payload status device (connected/disconnected), bukan pesan chat
        if (isset($data['status']) && isset($data['deviceId']) && !isset($data['message'])) {
            return ['status' => 'ignored', 'message' => 'Device status event, not a chat message'];
        }

        // 3. Normalisasi Data (Perbaikan Mapping)
        $isGroup = (bool) ($data['isGroup'] ?? false);

        // ID Grup di payload Anda berada di $data['group']['sender']
        $groupId = $data['group']['group_id'] ?? null;
        $senderPhone = $data['group']['sender'] ?? null;
        $messageContent = $data['message'] ?? '';

        // Normalize phone number (hapus + jika ada)
        if ($senderPhone) {
            $senderPhone = ltrim($senderPhone, '+');
        }

        // Abaikan jika bukan pesan dari grup, atau field penting kosong
        if (!$isGroup || !$groupId || !$senderPhone) {
            return [
                'status' => 'ignored',
                'message' => 'Not a group message or missing required fields. isGroup=' . ($isGroup ? 'true' : 'false') . ', groupId=' . ($groupId ?? 'null')
            ];
        }

        // Lapis Kedua: Cek apakah grup ini ada di whitelist
        if (!$this->groupModel->isWhitelistedGroup($groupId)) {
            return ['status' => 'ignored', 'message' => 'Group not whitelisted: ' . $groupId];
        }

        $timeNow = date('Y-m-d H:i:s');

        // Cek apakah pengirim adalah staff CS
        $isStaff = $this->staffModel->isStaff($senderPhone);

        if ($isStaff) {
            // Balasan dari CS Staff — update semua komplain yang pending di grup ini.
            // Tidak perlu cek command di sini, balasan CS selalu diproses apa adanya.
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
                        $statusSla,
                        $messageContent
                    );

                    // Kalau responnya telat, langsung tandai "resolved by explanation" juga,
                    // supaya otomatis pindah ke kartu Terselesaikan tanpa perlu tombol manual.
                    if ($slaSeconds > 180) {
                        $this->slaModel->resolveByExplanation($complaint['id_monitoring']);
                    }
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

        // Pesan dari klien — HANYA simpan sebagai komplain kalau diawali command tertentu.
        // Chat basa-basi biasa (bukan komplain) akan diabaikan, tidak numpuk di database.
        $trimmedMessage = trim($messageContent);

        if (stripos($trimmedMessage, self::COMPLAINT_TRIGGER) !== 0) {
            return ['status' => 'ignored', 'message' => 'Pesan tidak diawali command "' . self::COMPLAINT_TRIGGER . '", diabaikan.'];
        }

        // Buang command dari isi pesan yang disimpan, biar tampilannya rapi di dashboard
        $cleanMessage = trim(substr($trimmedMessage, strlen(self::COMPLAINT_TRIGGER)));
        if ($cleanMessage === '') {
            $cleanMessage = $trimmedMessage; // fallback kalau setelah command kosong
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
