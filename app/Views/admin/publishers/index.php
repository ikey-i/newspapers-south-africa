<?php
/** @var list<array<string,mixed>> $publishers */
/** @var \App\Support\Paginator $paginator */
/** @var string $q */
/** @var int $total */
/** @var string $csrfField */
?>
<form class="admin-filter" method="get" action="<?= e(url('admin/publishers')) ?>">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Name, email or newspaper…">
    <button class="btn" type="submit">Search</button>
</form>

<p class="admin-count"><?= number_format($total) ?> account<?= $total === 1 ? '' : 's' ?></p>

<?php if ($publishers === []): ?>
    <p class="empty">No publisher accounts yet.</p>
<?php else: ?>
<div class="table-wrap">
<table class="admin-table">
    <thead><tr><th>Name</th><th>Email</th><th>Newspaper</th><th>State</th><th>Last sign-in</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($publishers as $p): ?>
        <tr>
            <td><?= e($p['name']) ?><span class="admin-table__slug"><?= e($p['role']) ?></span></td>
            <td><?= e($p['email']) ?></td>
            <td>
                <a href="<?= e(url('admin/newspapers/' . $p['newspaper_id'] . '/edit')) ?>"><?= e($p['newspaper_name']) ?></a>
                <span class="admin-table__slug"><?= e($p['newspaper_status']) ?></span>
            </td>
            <td>
                <?php if ((int) $p['is_active'] !== 1): ?><span class="pill pill--bad">disabled</span>
                <?php elseif (empty($p['email_verified_at'])): ?><span class="pill pill--warn">unverified</span>
                <?php else: ?><span class="pill pill--ok">active</span><?php endif ?>
            </td>
            <td><?= e($p['last_login_at'] ? date('j M Y', strtotime((string) $p['last_login_at'])) : 'never') ?></td>
            <td class="admin-table__actions">
                <form class="inline-form" method="post" action="<?= e(url('admin/publishers/' . $p['id'] . '/toggle')) ?>">
                    <?= $csrfField ?><button class="linkbtn" type="submit"><?= (int) $p['is_active'] === 1 ? 'Disable' : 'Enable' ?></button>
                </form>
                <?php if (empty($p['email_verified_at'])): ?>
                <form class="inline-form" method="post" action="<?= e(url('admin/publishers/' . $p['id'] . '/resend-verify')) ?>">
                    <?= $csrfField ?><button class="linkbtn" type="submit">Resend verify</button>
                </form>
                <?php endif ?>
                <form class="inline-form" method="post" action="<?= e(url('admin/publishers/' . $p['id'] . '/send-reset')) ?>">
                    <?= $csrfField ?><button class="linkbtn" type="submit">Send reset</button>
                </form>
            </td>
        </tr>
    <?php endforeach ?>
    </tbody>
</table>
</div>
<?= view('partials/pagination', ['paginator' => $paginator]) ?>
<?php endif ?>
