<?php
/** @var string $title */
?>
<article class="prose prose--narrow">
    <h1><?= e($title) ?></h1>
    <p class="lead">
        <?= e(config('app.name')) ?> is a free home for South Africa's community and
        local newspapers. Create a publisher account and you can run your online
        edition, publish stories, and share a full-edition PDF with an archive of
        back issues.
    </p>

    <h2>What you get</h2>
    <ul>
        <li><strong>Your own newspaper page</strong> with a masthead, your latest stories and your details.</li>
        <li><strong>A story editor</strong> — write and publish articles with images, filed under familiar sections.</li>
        <li><strong>A PDF section</strong> — upload the full print edition each week.</li>
        <li><strong>An archive</strong> of every past edition, kept online for readers.</li>
    </ul>

    <h2>How it works</h2>
    <ol>
        <li>Register your newsroom and verify your email address.</li>
        <li>Our team reviews the listing (usually within a working day).</li>
        <li>Once approved, sign in and start publishing.</li>
    </ol>

    <p class="cta-row">
        <a class="btn btn--primary" href="<?= e(url('publish/register')) ?>">Register your newspaper</a>
        <a class="btn" href="<?= e(url('publish/login')) ?>">I already have an account</a>
    </p>

    <p class="prose__meta">
        Questions? Email us at
        <?php $c = \App\Models\Setting::get('contact_email'); ?>
        <?php if ($c !== ''): ?><a href="mailto:<?= e($c) ?>"><?= e($c) ?></a><?php else: ?>the address on our contact page<?php endif ?>.
    </p>
</article>
