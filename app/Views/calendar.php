<?php
$title = 'Kalender Tugas';
$monthStart = new DateTimeImmutable($month . '-01');
$gridStart = $monthStart->modify('monday this week');
$dayCount = (int) $monthStart->format('t');
$gridDays = (int) ceil(((int) $monthStart->format('N') - 1 + $dayCount) / 7) * 7;
$tasksByDay = [];
foreach ($tasks as $task) {
    if ($task['due_at'] !== null && substr($task['due_at'], 0, 7) === $month) {
        $tasksByDay[(int) date('j', strtotime($task['due_at']))][] = $task;
    }
}
?>
<section class="page-heading heading-row"><div><p class="eyebrow">JADWAL PENANGANAN</p><h1>Kalender Tugas</h1><p class="muted">Tenggat tugas ditentukan admin ketika menetapkan petugas.</p></div><form method="get" class="month-picker"><input type="hidden" name="page" value="calendar"><label class="sr-only" for="month">Bulan kalender</label><input id="month" type="month" name="month" value="<?= e($month) ?>"><button class="button button-outline" type="submit">Lihat bulan</button></form></section>
<section class="panel calendar-panel"><div class="calendar-grid calendar-weekdays"><?php foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $weekday): ?><strong><?= e($weekday) ?></strong><?php endforeach; ?></div>
    <div class="calendar-grid"><?php for ($offset = 0; $offset < $gridDays; $offset++): $day = $gridStart->modify('+' . $offset . ' days'); $inMonth = $day->format('Y-m') === $month; ?><div class="calendar-day <?= $inMonth ? '' : 'outside-month' ?>"><span class="calendar-date"><?= $day->format('j') ?></span><?php if ($inMonth): foreach ($tasksByDay[(int) $day->format('j')] ?? [] as $task): ?><a class="calendar-task" href="index.php?page=detail&id=<?= (int) $task['id'] ?>"><strong>#<?= (int) $task['id'] ?></strong> <?= e($task['location']) ?><small><?= e(date('H:i', strtotime($task['due_at']))) ?> · <?= e($task['status']) ?></small></a><?php endforeach; endif; ?></div><?php endfor; ?></div>
</section>
