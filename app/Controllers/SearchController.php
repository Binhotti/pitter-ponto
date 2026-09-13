<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\DayOff;
use App\Models\Holiday;
use App\Models\TimeEntry;
use App\Models\User;
use DateTimeImmutable;

class SearchController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();

        $user = (new User())->find((int)authUser()['id']);
        $query = trim((string)($_GET['q'] ?? ''));

        $shortcuts = $this->shortcuts($query);
        $entries = [];
        $dayOffs = [];
        $holidays = [];
        $dateResult = null;

        if ($query !== '') {
            $date = $this->parseDate($query);

            if ($date !== null) {
                $dateResult = [
                    'date' => $date,
                    'url' => url('history')
                        . '&from=' . $date
                        . '&to=' . $date,
                ];
            }

            $entries = (new TimeEntry())->searchForUser(
                (int)$user['id'],
                $query,
                15
            );

            $dayOffs = (new DayOff())->searchForUser(
                (int)$user['id'],
                $query,
                15
            );

            $holidays = (new Holiday())->searchByName($query, 15);
        }

        $this->view('search/index', [
            'title' => 'Busca',
            'active' => '',
            'user' => $user,
            'query' => $query,
            'shortcuts' => $shortcuts,
            'entries' => $entries,
            'dayOffs' => $dayOffs,
            'holidays' => $holidays,
            'dateResult' => $dateResult,
        ]);
    }

    private function shortcuts(string $query): array
    {
        $items = [
            ['label' => 'Dashboard', 'keywords' => 'dashboard início inicio home', 'icon' => 'house', 'url' => url('dashboard')],
            ['label' => 'Meu Ponto', 'keywords' => 'meu ponto bater ponto entrada saída saida almoço almoco', 'icon' => 'clock-3', 'url' => url('dashboard') . '#meu-ponto'],
            ['label' => 'Histórico', 'keywords' => 'histórico historico registros pontos', 'icon' => 'notebook-tabs', 'url' => url('history')],
            ['label' => 'Calendário', 'keywords' => 'calendário calendario mês mes dias', 'icon' => 'calendar-days', 'url' => url('calendar')],
            ['label' => 'Ausências e Justificativas', 'keywords' => 'ausência ausencia justificativa atestado folga férias ferias', 'icon' => 'calendar-off', 'url' => url('absences')],
            ['label' => 'Relatórios', 'keywords' => 'relatório relatorio relatórios relatorios horas extras dinheiro salário salario banco', 'icon' => 'chart-no-axes-column-increasing', 'url' => url('reports')],
            ['label' => 'Perfil', 'keywords' => 'perfil foto salário salario email nome', 'icon' => 'user-round', 'url' => url('profile')],
            ['label' => 'Configurações', 'keywords' => 'configuração configuracao configurações configuracoes tema notificação notificacao jornada', 'icon' => 'settings', 'url' => url('settings')],
        ];

        if ($query === '') {
            return $items;
        }

        $normalized = mb_strtolower($query);

        return array_values(array_filter(
            $items,
            fn(array $item): bool =>
                str_contains(mb_strtolower($item['label']), $normalized)
                || str_contains(mb_strtolower($item['keywords']), $normalized)
        ));
    }

    private function parseDate(string $query): ?string
    {
        foreach (['!d/m/Y', '!Y-m-d', '!d-m-Y'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $query);

            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
