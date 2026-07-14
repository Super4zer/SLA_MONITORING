<?php

use App\Controllers\WebhookController;
use App\Controllers\DashboardController;
use App\Controllers\ActionController;

/** @var \App\Routing\Router $router */

// 1. Frontend: Halaman Login (/)
$router->get('/', function () {
    $html = __DIR__ . '/../public/views/login.php';
    if (file_exists($html)) {
        header('Content-Type: text/html');
        echo file_get_contents($html);
    } else {
        http_response_code(404);
        echo "Login page not found.";
    }
});

// 2. Frontend: Halaman Dashboard (/dashboard)
$router->get('/dashboard', function () {
    $html = __DIR__ . '/../public/views/dashboard.php';
    if (file_exists($html)) {
        header('Content-Type: text/html');
        require_once $html; 
    } else {
        http_response_code(404);
        echo "Dashboard frontend not found.";
    }
});

// 3. Frontend: Halaman Manajemen Grub (/grub)
$router->get('/grub', function () {
    $html = __DIR__ . '/../public/views/grub.php';
    if (file_exists($html)) {
        header('Content-Type: text/html');
        require_once $html; 
    } else {
        http_response_code(404);
        echo "Grub management page not found.";
    }
});
$router->get('/laporan', function () {
    $html = __DIR__ . '/../public/views/laporan.php';
    if (file_exists($html)) {
        header('Content-Type: text/html');
        require_once $html; 
    } else {
        http_response_code(404);
        echo "Grub management page not found.";
    }
});

$router->get('/laporan', function () {
    $html = __DIR__ . '/../public/views/laporan.php';
    if (file_exists($html)) {
        header('Content-Type: text/html');
        require_once $html; 
    } else {
        http_response_code(404);
        echo "Grub management page not found.";
    }
});

// 4. API: Proses Login (WAJIB DITAMBAHKAN AGAR LOGIN BERFUNGSI)
$router->post('/api/login', [ActionController::class, 'login']);

// Webhook
$router->post('/webhook/wablas', [WebhookController::class, 'handle']);

// Dashboard APIs
$router->get('/api/monitoring/waiting', [DashboardController::class, 'getWaiting']);
$router->get('/api/monitoring/overdue', [DashboardController::class, 'getOverdue']);
$router->get('/api/monitoring/completed', [DashboardController::class, 'getCompleted']);

// Action Buttons
$router->post('/api/monitoring/{id}/resolve', [ActionController::class, 'resolve']);
$router->post('/api/monitoring/{id}/escalate', [ActionController::class, 'escalate']);