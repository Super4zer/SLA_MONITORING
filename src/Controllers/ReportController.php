<?php

namespace App\Controllers;

use App\Models\SlaMonitoringModel;

class ReportController
{
    private SlaMonitoringModel $slaModel;

    public function __construct()
    {
        $this->slaModel = new SlaMonitoringModel();
    }

    public function summary(): array
    {
        return $this->slaModel->getSummary();
    }

    public function history(): array
    {
        $startDate = $_GET['start_date'] ?? null;
        $endDate   = $_GET['end_date'] ?? null;
        $groupId   = $_GET['group_id'] ?? null;
        $page      = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

        return $this->slaModel->getHistory($startDate, $endDate, $groupId, $page);
    }

    public function staffPerformance(): array
    {
        $startDate = $_GET['start_date'] ?? null;
        $endDate   = $_GET['end_date'] ?? null;

        $data = $this->slaModel->getStaffPerformance($startDate, $endDate);

        // Tambahkan persentase tepat waktu per staff, biar frontend tinggal pakai
        foreach ($data as &$staff) {
            $total = (int) $staff['total_respon'];
            $staff['persentase_tepat_waktu'] = $total > 0
                ? round(((int) $staff['tepat_waktu'] / $total) * 100, 1)
                : 0;
        }

        return $data;
    }
}
