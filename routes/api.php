<?php

use App\Controllers\WebhookController;
use App\Controllers\DashboardController;
use App\Controllers\ActionController;

/** @var \App\Routing\Router $router */

// Webhook
$router->post('/webhook/wablas', [WebhookController::class, 'handle']);

// Dashboard APIs
$router->get('/api/monitoring/waiting', [DashboardController::class, 'getWaiting']);
$router->get('/api/monitoring/overdue', [DashboardController::class, 'getOverdue']);
$router->get('/api/monitoring/completed', [DashboardController::class, 'getCompleted']);

// Action Buttons
$router->post('/api/monitoring/{id}/resolve', [ActionController::class, 'resolve']);
$router->post('/api/monitoring/{id}/escalate', [ActionController::class, 'escalate']);
