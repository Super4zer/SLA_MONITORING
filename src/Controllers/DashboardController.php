<?php

namespace App\Controllers;

use App\Models\SlaMonitoringModel;

class DashboardController
{
    private SlaMonitoringModel $slaModel;

    public function __construct()
    {
        $this->slaModel = new SlaMonitoringModel();
    }

    public function getWaiting(): void
    {
        header('Content-Type: application/json');
        $data = $this->slaModel->getWaiting(180);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function getOverdue(): void
    {
        header('Content-Type: application/json');
        $data = $this->slaModel->getOverdue(180);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function getCompleted(): void
    {
        header('Content-Type: application/json');
        $data = $this->slaModel->getCompleted(180);
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    public function getOverdueResolved(): void
    {
        header('Content-Type: application/json');
        $data = $this->slaModel->getOverdueResolved();
        echo json_encode(['status' => 'success', 'data' => $data]);
    }

    // History "Terselesaikan" — dipakai di Dashboard & Laporan Kinerja.
    // Query param: date=YYYY-MM-DD | month=1-12&year=YYYY | search=kata | page=1
    public function getHistoryResolved(): void
    {
        header('Content-Type: application/json');
        $filters = [
            'date'   => $_GET['date'] ?? null,
            'month'  => $_GET['month'] ?? null,
            'year'   => $_GET['year'] ?? null,
            'search' => $_GET['search'] ?? null,
            'page'   => $_GET['page'] ?? 1,
        ];
        $result = $this->slaModel->getResolvedHistory($filters);
        echo json_encode(['status' => 'success'] + $result);
    }

    // Hapus data "Terselesaikan" yang lebih lama dari N bulan (1-12)
    public function deleteHistoryResolved(): void
    {
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $months = isset($input['months']) ? (int) $input['months'] : 0;

        if ($months < 1 || $months > 12) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Pilih rentang 1-12 bulan']);
            return;
        }

        $deleted = $this->slaModel->deleteResolvedOlderThanMonths($months);
        echo json_encode([
            'status' => 'success',
            'message' => "$deleted data terselesaikan (lebih dari $months bulan) berhasil dihapus",
            'deleted' => $deleted
        ]);
    }
}
