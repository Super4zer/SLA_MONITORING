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

    public function getWaiting(): array
    {
        $data = $this->slaModel->getWaiting(180);
        return [
            'status' => 'success',
            'data' => $data
        ];
    }

    public function getOverdue(): array
    {
        $data = $this->slaModel->getOverdue(180);
        return [
            'status' => 'success',
            'data' => $data
        ];
    }

    public function getCompleted(): array
    {
        $data = $this->slaModel->getCompleted(180);
        return [
            'status' => 'success',
            'data' => $data
        ];
    }
}
