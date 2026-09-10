<?php
/** @var array<string,int> $stats */
?>
<div class="stat-grid">
    <a class="stat<?= $stats['papers_pending'] > 0 ? ' stat--warn' : '' ?>" href="<?= e(url('admin/newspapers?status=pending')) ?>">
        <span class="stat__value"><?= number_format($stats['papers_pending']) ?></span>
        <span class="stat__label">Newspapers awaiting approval</span>
    </a>
    <a class="stat" href="<?= e(url('admin/newspapers?status=active')) ?>">
        <span class="stat__value"><?= number_format($stats['papers_active']) ?></span>
        <span class="stat__label">Active newspapers</span>
    </a>
    <a class="stat" href="<?= e(url('admin/newspapers')) ?>">
        <span class="stat__value"><?= number_format($stats['papers_total']) ?></span>
        <span class="stat__label">Newspapers total</span>
    </a>
    <a class="stat" href="<?= e(url('admin/publishers')) ?>">
        <span class="stat__value"><?= number_format($stats['publishers']) ?></span>
        <span class="stat__label">Publisher accounts</span>
    </a>
    <a class="stat" href="<?= e(url('admin/articles')) ?>">
        <span class="stat__value"><?= number_format($stats['articles']) ?></span>
        <span class="stat__label">Published articles</span>
    </a>
    <a class="stat" href="<?= e(url('admin/editions')) ?>">
        <span class="stat__value"><?= number_format($stats['editions']) ?></span>
        <span class="stat__label">Published editions</span>
    </a>
</div>

<div class="admin-actions">
    <a class="btn btn--primary" href="<?= e(url('admin/newspapers/new')) ?>">Add a newspaper</a>
    <a class="btn" href="<?= e(url('admin/newspapers')) ?>">Manage newspapers</a>
    <a class="btn" href="<?= e(url('admin/settings')) ?>">AdSense &amp; settings</a>
</div>

<?php if ($stats['papers_pending'] > 0): ?>
<p class="admin-note">
    <strong><?= number_format($stats['papers_pending']) ?></strong>
    newsroom<?= $stats['papers_pending'] === 1 ? '' : 's' ?> registered and
    <a href="<?= e(url('admin/newspapers?status=pending')) ?>">waiting for approval</a>.
</p>
<?php endif ?>
