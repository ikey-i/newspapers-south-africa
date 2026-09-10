<?php
/** @var string $title */
/** @var string $contactEmail */
/** @var string $updated */
$site = config('app.name');
?>
<article class="prose">
    <h1><?= e($title) ?></h1>
    <p class="prose__meta">Last updated <?= e($updated) ?></p>

    <p>
        By using <?= e($site) ?> you agree to these terms.
        <strong>Adjust this text to suit your site before relying on it.</strong>
    </p>

    <h2>The directory</h2>
    <p>
        <?= e($site) ?> is an independent directory and publishing platform for South
        African newspapers. Newspaper names, mastheads and trademarks belong to their
        respective owners.
    </p>

    <h2>Publisher accounts and content</h2>
    <p>
        If you publish through <?= e($site) ?>, you confirm that you are entitled to
        publish the material you upload, that it is lawful, and that it does not
        infringe anyone's rights. You keep ownership of your content and grant us
        the licence needed to host and display it. We may edit, unpublish or remove
        content, or suspend an account, at our discretion — for example where content
        is unlawful, misleading or breaches these terms.
    </p>

    <h2>Readers</h2>
    <p>
        Content is published by the individual newsrooms, not by <?= e($site) ?>. We
        are not responsible for the accuracy of articles or the completeness of any
        archive.
    </p>

    <h2>Acceptable use</h2>
    <p>
        Do not use this site to break the law, infringe rights, or disrupt the
        service (including automated scraping or abuse of the forms).
    </p>

    <h2>No warranty</h2>
    <p>
        The site is provided "as is" without warranties of any kind. We are not
        liable for any loss arising from its use, from third-party content, or from ads.
    </p>

    <h2>Contact</h2>
    <p>
        <?php if ($contactEmail !== ''): ?>
            <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
        <?php else: ?>
            Add a contact email in the site settings.
        <?php endif ?>
    </p>
</article>
