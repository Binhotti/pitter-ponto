<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\AdjustmentController;
use App\Controllers\AbsenceController;
use App\Controllers\DashboardController;
use App\Controllers\PageController;
use App\Controllers\ReportsController;
use App\Controllers\SearchController;
use App\Controllers\TimeEntryController;

return [
    'GET' => [
        '' => [DashboardController::class, 'index'],
        'dashboard' => [DashboardController::class, 'index'],
        'login' => [AuthController::class, 'showLogin'],
        'register' => [AuthController::class, 'showRegister'],
        'history' => [PageController::class, 'history'],
        'calendar' => [PageController::class, 'calendar'],
        'reports' => [ReportsController::class, 'index'],
        'search' => [SearchController::class, 'index'],
        'absences' => [AbsenceController::class, 'index'],
        'profile' => [PageController::class, 'profile'],
        'employees' => [PageController::class, 'employees'],
        'settings' => [PageController::class, 'settings'],
        'logout' => [AuthController::class, 'logout'],
    ],
    'POST' => [
        'login' => [AuthController::class, 'login'],
        'register' => [AuthController::class, 'register'],
        'clock' => [TimeEntryController::class, 'store'],
        'profile' => [PageController::class, 'updateProfile'],
        'settings' => [PageController::class, 'updateSettings'],
        'adjustment-update' => [AdjustmentController::class, 'update'],
        'adjustment-create' => [AdjustmentController::class, 'create'],
        'absence-create' => [AbsenceController::class, 'create'],
        'absence-update' => [AbsenceController::class, 'update'],
        'absence-delete' => [AbsenceController::class, 'delete'],
    ],
];
