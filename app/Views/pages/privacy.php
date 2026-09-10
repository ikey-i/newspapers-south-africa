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
        This page explains how <?= e($site) ?> ("we", "us") handles information when
        you use this website. <strong>Review this text and adjust it to reflect what
        your site actually does before you rely on it.</strong>
    </p>

    <h2>What we collect</h2>
    <ul>
        <li><strong>Publisher accounts.</strong> If you register a newsroom, we store
            your name, email address, a hashed password and the details of your
            publication so we can run your account.</li>
        <li><strong>Content you publish.</strong> Articles, images and PDFs you upload
            are stored so we can show them to readers.</li>
        <li><strong>Basic technical data.</strong> Our server keeps standard request
            logs. We store a one-way hash of your IP address to prevent spam and
            abuse of the public forms.</li>
        <li><strong>Cookies.</strong> We use cookies for sign-in and, where enabled,
            for analytics and advertising (see below).</li>
    </ul>

    <h2>Advertising</h2>
    <p>
        We may display ads served by Google AdSense. Google and its partners use
        cookies to serve ads based on your prior visits to this and other websites.
        You can manage ad personalisation at
        <a href="https://adssettings.google.com" rel="nofollow noopener" target="_blank">Google Ad Settings</a>,
        and read Google's practices at
        <a href="https://policies.google.com/technologies/ads" rel="nofollow noopener" target="_blank">policies.google.com/technologies/ads</a>.
    </p>

    <h2>How we use it</h2>
    <p>
        To run the directory and the publishing tools, to respond to enquiries, to
        keep the site secure, and to show ads that help fund it. We do not sell your
        personal information.
    </p>

    <h2>Your choices</h2>
    <p>
        You can block or delete cookies in your browser settings. You can ask us to
        access or delete information you have sent us by emailing
        <?php if ($contactEmail !== ''): ?>
            <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>.
        <?php else: ?>
            the address on our contact page.
        <?php endif ?>
    </p>

    <h2>Contact</h2>
    <p>
        Questions about this policy:
        <?php if ($contactEmail !== ''): ?>
            <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>.
        <?php else: ?>
            add a contact email in the site settings.
        <?php endif ?>
    </p>
</article>
