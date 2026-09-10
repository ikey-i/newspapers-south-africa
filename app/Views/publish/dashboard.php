<?php
/** @var array<string,mixed> $user */
/** @var bool $verified */
/** @var bool $approved */
/** @var string $status */
/** @var array<string,int> $counts */
/** @var string $csrfField */
?>
<?php if (!$verified): ?>
    <div class="callout callout--warn">
        <h2>Confirm your email address</h2>
        <p>We sent a verification link to <strong><?= e($user['email']) ?></strong>. Click it to confirm your account.</p>
        <form method="post" action="<?= e(url('publish/verify/resend')) ?>">
            <?= $csrfField ?>
            <button class="btn" type="submit">Resend the email</button>
        </form>
    </div>
<?php elseif ($status === 'pending'): ?>
    <div class="callout callout--info">
        <h2>Your newspaper is awaiting approval</h2>
        <p>Thanks for verifying your email. Our team is reviewing
            <strong><?= e($user['newspaper_name']) ?></strong> — usually within a working day.
            You'll be able to publish as soon as it's approved.</p>
    </div>
<?php elseif ($status === 'suspended'): ?>
    <div class="callout callout--warn">
        <h2>This account is suspended</h2>
        <p>Contact the site administrators to restore access.</p>
    </div>
<?php elseif ($status === 'rejected'): ?>
    <div class="callout callout--warn">
        <h2>This listing was not approved</h2>
        <p>If you think this is a mistake, get in touch with the site administrators.</p>
    </div>
<?php endif ?>

<?php if ($verified && $approved): ?>
    <div class="stat-grid">
        <a class="stat" href="<?= e(url('publish/articles')) ?>">
            <span class="stat__value"><?= number_format($counts['published']) ?></span>
            <span class="stat__label">Published articles</span>
        </a>
        <a class="stat" href="<?= e(url('publish/articles?status=draft')) ?>">
            <span class="stat__value"><?= number_format(max(0, $counts['articles'] - $counts['published'])) ?></span>
            <span class="stat__label">Drafts</span>
        </a>
        <a class="stat" href="<?= e(url('publish/editions')) ?>">
            <span class="stat__value"><?= number_format($counts['editions']) ?></span>
            <span class="stat__label">Editions</span>
        </a>
    </div>

    <div class="admin-actions">
        <a class="btn btn--primary" href="<?= e(url('publish/articles/new')) ?>">Write a story</a>
        <a class="btn" href="<?= e(url('publish/editions/new')) ?>">Add an edition</a>
        <a class="btn" href="<?= e(url('publish/newspaper')) ?>">Edit newspaper details</a>
    </div>
<?php endif ?>

<p class="admin-note">
    Signed in as <?= e($user['name']) ?> (<?= e($user['email']) ?>).
</p>
