<?php $site = config('app.name'); ?>
<section class="prose">
    <h1>Set up <?= e($site) ?></h1>
    <p>The site is installed but not configured yet. Finish these steps:</p>
    <ol>
        <li>Copy <code>config.sample.php</code> to <code>config.php</code> and fill in the database credentials and <code>app.url</code>.</li>
        <li>Create the database, then apply the schema: <code>php db/migrate.php</code></li>
        <li>Create an admin login: <code>php db/migrate.php create-admin &lt;user&gt; &lt;password&gt;</code></li>
        <li>(Optional) Load demo content: <code>php db/seed.php</code></li>
    </ol>
    <p>Then reload this page. The admin area is at <a href="<?= e(url('admin')) ?>">/admin</a>.</p>
</section>
