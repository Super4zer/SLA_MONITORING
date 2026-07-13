<?php

namespace App\Models;

use App\Config\Database;
use App\Models\Enums\Platform;
use App\Models\Enums\SlaStatus;
use PDO;

class SlaMonitoringModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function insertComplaint(
        string $groupId,
        string $clientPhone,
        string $messageContent,
        string $timeReceived,
        Platform $platform = Platform::WA
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO ts_sla_monitoring 
            (platform, group_id, client_phone, message_content, time_received, status_sla)
            VALUES (:platform, :group_id, :client_phone, :message_content, :time_received, :status_sla)
        ");

        $stmt->execute([
            'platform'        => $platform->value,
            'group_id'        => $groupId,
            'client_phone'    => $clientPhone,
            'message_content' => $messageContent,
            'time_received'   => $timeReceived,
            'status_sla'      => SlaStatus::KUNING->value
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function getAllUnrespondedComplaints(string $groupId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ts_sla_monitoring 
            WHERE group_id = :group_id 
              AND time_responded IS NULL 
              AND is_resolved_by_explanation = 0
            ORDER BY time_received ASC 
        ");

        $stmt->execute(['group_id' => $groupId]);
        return $stmt->fetchAll();
    }

    public function updateResponse(
        int $idMonitoring,
        string $respondedBy,
        string $timeResponded,
        int $slaSeconds,
        SlaStatus $statusSla
    ): bool {
        $stmt = $this->db->prepare("
            UPDATE ts_sla_monitoring 
            SET responded_by = :responded_by,
                time_responded = :time_responded,
                sla_seconds = :sla_seconds,
                status_sla = :status_sla
            WHERE id_monitoring = :id
        ");

        return $stmt->execute([
            'responded_by'   => $respondedBy,
            'time_responded' => $timeResponded,
            'sla_seconds'    => $slaSeconds,
            'status_sla'     => $statusSla->value,
            'id'             => $idMonitoring
        ]);
    }

    public function getWaiting(int $maxSeconds = 180): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ts_sla_monitoring 
            WHERE time_responded IS NULL 
              AND is_resolved_by_explanation = 0
              AND TIMESTAMPDIFF(SECOND, time_received, NOW()) <= :max_seconds
            ORDER BY time_received ASC
        ");
        $stmt->execute(['max_seconds' => $maxSeconds]);
        return $stmt->fetchAll();
    }

    public function getOverdue(int $maxSeconds = 180): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ts_sla_monitoring 
            WHERE (time_responded IS NULL AND TIMESTAMPDIFF(SECOND, time_received, NOW()) > :max_seconds AND is_resolved_by_explanation = 0)
               OR (sla_seconds > :max_seconds_2 AND is_resolved_by_explanation = 0)
            ORDER BY time_received DESC
        ");
        $stmt->execute([
            'max_seconds'   => $maxSeconds,
            'max_seconds_2' => $maxSeconds
        ]);
        return $stmt->fetchAll();
    }

    public function getCompleted(int $maxSeconds = 180): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM ts_sla_monitoring 
            WHERE sla_seconds <= :max_seconds 
               OR is_resolved_by_explanation = 1
            ORDER BY time_received DESC
        ");
        $stmt->execute(['max_seconds' => $maxSeconds]);
        return $stmt->fetchAll();
    }

    public function resolveByExplanation(int $idMonitoring): bool
    {
        $stmt = $this->db->prepare("
            UPDATE ts_sla_monitoring 
            SET is_resolved_by_explanation = 1,
                status_sla = :status_sla
            WHERE id_monitoring = :id
        ");
        return $stmt->execute([
            'status_sla' => SlaStatus::HIJAU->value,
            'id' => $idMonitoring
        ]);
    }

    public function escalateComplaint(int $idMonitoring, int $logKlikdsiId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE ts_sla_monitoring 
            SET log_klikdsi_id = :log_id
            WHERE id_monitoring = :id
        ");
        return $stmt->execute([
            'log_id' => $logKlikdsiId,
            'id'     => $idMonitoring
        ]);
    }

    public function getSummary(): array
    {
        $stmt = $this->db->query("
        SELECT
            SUM(CASE WHEN time_responded IS NULL AND is_resolved_by_explanation = 0 
                     AND TIMESTAMPDIFF(SECOND, time_received, NOW()) <= 180 THEN 1 ELSE 0 END) AS waiting,
            SUM(CASE WHEN (time_responded IS NULL AND is_resolved_by_explanation = 0 
                     AND TIMESTAMPDIFF(SECOND, time_received, NOW()) > 180)
                     OR (sla_seconds > 180 AND is_resolved_by_explanation = 0) THEN 1 ELSE 0 END) AS overdue,
            SUM(CASE WHEN sla_seconds <= 180 OR is_resolved_by_explanation = 1 THEN 1 ELSE 0 END) AS completed
        FROM ts_sla_monitoring
    ");
        $row = $stmt->fetch();
        return [
            'waiting'   => (int) ($row['waiting'] ?? 0),
            'overdue'   => (int) ($row['overdue'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
        ];
    }

    public function getHistory(
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $groupId = null,
        int $page = 1,
        int $perPage = 20
    ): array {
        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "time_received >= :start_date";
            $params['start_date'] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $where[] = "time_received <= :end_date";
            $params['end_date'] = $endDate . ' 23:59:59';
        }
        if ($groupId) {
            $where[] = "group_id = :group_id";
            $params['group_id'] = $groupId;
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset = ($page - 1) * $perPage;

        // Ambil total data dulu, untuk info pagination di frontend
        $countStmt = $this->db->prepare("SELECT COUNT(*) as total FROM ts_sla_monitoring $whereSql");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetch()['total'];

        $stmt = $this->db->prepare("
        SELECT * FROM ts_sla_monitoring 
        $whereSql
        ORDER BY time_received DESC
        LIMIT :limit OFFSET :offset
    ");
        foreach ($params as $key => $val) {
            $stmt->bindValue(":$key", $val);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ],
        ];
    }

    public function getStaffPerformance(?string $startDate = null, ?string $endDate = null): array
    {
        $where = ["responded_by IS NOT NULL"];
        $params = [];

        if ($startDate) {
            $where[] = "time_received >= :start_date";
            $params['start_date'] = $startDate . ' 00:00:00';
        }
        if ($endDate) {
            $where[] = "time_received <= :end_date";
            $params['end_date'] = $endDate . ' 23:59:59';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        $stmt = $this->db->prepare("
        SELECT 
            responded_by,
            COUNT(*) AS total_respon,
            ROUND(AVG(sla_seconds)) AS rata_rata_sla_detik,
            SUM(CASE WHEN status_sla = 'HIJAU' THEN 1 ELSE 0 END) AS tepat_waktu,
            SUM(CASE WHEN status_sla = 'MERAH' THEN 1 ELSE 0 END) AS terlambat
        FROM ts_sla_monitoring
        $whereSql
        GROUP BY responded_by
        ORDER BY total_respon DESC
    ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
