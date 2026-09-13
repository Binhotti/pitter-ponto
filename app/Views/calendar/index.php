<?php
$monthName = date('m/Y', strtotime($month . '-01'));
$prev = (new DateTimeImmutable($month . '-01'))->modify('-1 month')->format('Y-m');
$next = (new DateTimeImmutable($month . '-01'))->modify('+1 month')->format('Y-m');
?>
<div class="page-heading">
    <div><h1>Calendário</h1><p>Visão mensal da sua jornada e dos dias com hora extra.</p></div>
    <div class="month-nav">
        <a href="<?= url('calendar') . '&month=' . e($prev) ?>">←</a>
        <strong><?= e($monthName) ?></strong>
        <a href="<?= url('calendar') . '&month=' . e($next) ?>">→</a>
    </div>
</div>

<section class="panel full-calendar">
    <div class="full-weekdays"><span>Seg</span><span>Ter</span><span>Qua</span><span>Qui</span><span>Sex</span><span>Sáb</span><span>Dom</span></div>
    <div class="full-days">
        <?php for ($i = 1; $i < $firstWeekday; $i++): ?><div class="calendar-cell muted-cell"></div><?php endfor; ?>
        <?php foreach ($calendar as $day):
            $worked = $day['summary']['worked'];
            $hasExtra = ($day['summary']['overtime50'] + $day['summary']['overtime100']) > 0;
            $class = $worked > 0 ? 'worked-day' : '';
            if ($hasExtra) $class .= ' extra-day';
        ?>
            <div class="calendar-cell <?= e(trim($class)) ?>">
                <strong><?= (int)$day['day'] ?></strong>
                <?php if ($worked > 0): ?>
                    <small><?= intdiv($worked, 60) ?>h <?= str_pad((string)($worked % 60), 2, '0', STR_PAD_LEFT) ?>min</small>
                    <i></i>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
