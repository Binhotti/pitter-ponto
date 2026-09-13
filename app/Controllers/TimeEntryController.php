<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\TimeEntry;

class TimeEntryController extends Controller
{
    private const TYPES = ['clock_in', 'lunch_start', 'lunch_end', 'clock_out'];

    public function store(): void
    {
        $this->requireAuth();

        $type = (string)($_POST['type'] ?? '');
        $timeEntry = new TimeEntry();
        $status = $timeEntry->currentStatus((int)authUser()['id']);

        if (!in_array($type, self::TYPES, true) || $status['next'] !== $type) {
            flash('error', 'Essa marcação não é válida para o estado atual do seu expediente.');
            redirect('dashboard');
        }

        $timeEntry->create((int)authUser()['id'], $type);
        flash('success', 'Ponto registrado às ' . date('H:i') . '.');

        redirect('dashboard');
    }
}
