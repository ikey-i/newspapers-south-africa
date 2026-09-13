<?php
/** @var array<string,int> $stats */
/** @var bool $mailNotSending */
?>
<?php if ($mailNotSending): ?>
<p class="notice notice--warn">
    <strong>No email is actually being sent.</strong> <code>mail.method</code> in
    <code>config.php</code> is still <code>'log'</code> — verification, password-reset and
    approval emails are being written to <code>var/mail/</code> on the server instead of
    delivered. Set it to <code>'smtp'</code> or <code>'mail'</code> (see the README's
    <em>Email</em> section) to fix this.
</p>
<?php endif ?>
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
    <a class="stat" href="<?= e(url('admin/articles?status=published')) ?>">
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
