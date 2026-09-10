<?php
/** @var list<array<string,mixed>> $newspapers */
/** @var \App\Support\Paginator $paginator */
/** @var array{q:string,status:string,province:string} $filters */
/** @var array<string,int> $counts */
/** @var int $total */
$token = \App\Support\Csrf::field();
$statusPill = [
    'pending'   => 'pill--warn',
    'active'    => 'pill--ok',
    'suspended' => 'pill--bad',
    'rejected'  => 'pill',
];
$tabs = ['' => 'All', 'pending' => 'Pending (' . $counts['pending'] . ')', 'active' => 'Active', 'suspended' => 'Suspended', 'rejected' => 'Rejected'];
?>
<div class="admin-toolbar">
    <div class="admin-tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <a href="<?= e(url('admin/newspapers' . ($key !== '' ? '?status=' . $key : ''))) ?>"
               <?= $filters['status'] === $key ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
        <?php endforeach ?>
    </div>
    <a class="btn btn--primary" href="<?= e(url('admin/newspapers/new')) ?>">Add a newspaper</a>
</div>

<form class="admin-filter" method="get" action="<?= e(url('admin/newspapers')) ?>">
    <?php if ($filters['status'] !== ''): ?><input type="hidden" name="status" value="<?= e($filters['status']) ?>"><?php endif ?>
    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="Name, town or slug…">
    <button class="btn" type="submit">Search</button>
</form>

<p class="admin-count"><?= number_format($total) ?> newspaper<?= $total === 1 ? '' : 's' ?></p>

<?php if ($newspapers === []): ?>
    <p class="empty">No newspapers match.</p>
<?php else: ?>
<div class="table-wrap">
<table class="admin-table">
    <thead><tr>
        <th>Newspaper</th><th>Type</th><th>Location</th><th>Status</th>
        <th>Publishers</th><th>Articles</th><th>Actions</th>
    </tr></thead>
    <tbody>
    <?php foreach ($newspapers as $n): ?>
        <tr>
            <td>
                <a href="<?= e(url('admin/newspapers/' . $n['id'] . '/edit')) ?>"><?= e($n['name']) ?></a>
                <span class="admin-table__slug"><?= e($n['slug']) ?><?= (int) $n['is_featured'] === 1 ? ' · featured' : '' ?></span>
            </td>
            <td><?= e(\App\Support\Taxonomy::typeLabel((string) $n['type']) ?? $n['type']) ?></td>
            <td><?= e(trim(implode(', ', array_filter([$n['city'], $n['province']]))) ?: '—') ?></td>
            <td><span class="pill <?= $statusPill[$n['status']] ?? 'pill' ?>"><?= e($n['status']) ?></span></td>
            <td><?= (int) $n['publisher_count'] ?></td>
            <td><?= (int) $n['article_count'] ?></td>
            <td class="admin-table__actions">
                <?php
                $acts = match ($n['status']) {
                    'pending'   => ['approve' => 'Approve', 'reject' => 'Reject'],
                    'active'    => ['suspend' => 'Suspend'],
                    'suspended' => ['restore' => 'Restore'],
                    'rejected'  => ['approve' => 'Approve'],
                    default     => [],
                };
                foreach ($acts as $action => $label): ?>
                    <form class="inline-form" method="post" action="<?= e(url('admin/newspapers/' . $n['id'] . '/' . $action)) ?>">
                        <?= $token ?>
                        <input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? url('admin/newspapers')) ?>">
                        <button class="linkbtn" type="submit"><?= e($label) ?></button>
                    </form>
                <?php endforeach ?>
                <a href="<?= e(url('paper/' . $n['slug'])) ?>" target="_blank" rel="noopener">View</a>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
</div>
<?= view('partials/pagination', ['paginator' => $paginator]) ?>
<?php endif ?>
