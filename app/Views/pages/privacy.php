<?php
/** @var string $title */
/** @var string $updated */
/** @var string $contactEmail */
/** @var string $operatorName */
/** @var string $operatorAddress */
/** @var string $infoOfficerName */
/** @var string $infoOfficerEmail */
/** @var bool $needsReview */
$site = config('app.name');
$operator = $operatorName !== '' ? $operatorName : $site;
?>
<article class="prose">
    <h1><?= e($title) ?></h1>
    <p class="prose__meta">Last updated <?= e($updated) ?></p>

    <?php if ($needsReview): ?>
        <p class="notice notice--warn">
            <strong>Action needed before launch:</strong> the operator name, address and
            Information Officer details below are still placeholders. Set them in
            <code>config('legal')</code> (see <code>config.sample.php</code>) — POPIA requires a
            named Information Officer, and this text says so.
        </p>
    <?php endif ?>

    <p>
        This policy explains what personal information <?= e($site) ?> ("the Site", "we", "us")
        collects, why, and what you can do about it. It applies to everyone who reads the Site
        and to every newsroom that publishes through it. <?= e($site) ?> is operated by
        <strong><?= e($operator) ?></strong><?php if ($operatorAddress !== ''): ?>, <?= e($operatorAddress) ?><?php endif ?>.
    </p>

    <p>
        We process personal information in line with South Africa's
        <strong>Protection of Personal Information Act (POPIA)</strong>. This page is a plain-language
        summary, not a substitute for legal advice — if you rely on it for a live site handling
        real people's data, have a South African attorney check it against your actual setup.
    </p>

    <h2>Information we collect</h2>
    <div class="table-plain-wrap">
    <table class="table-plain">
        <thead><tr><th>Who</th><th>What we collect</th><th>Why</th></tr></thead>
        <tbody>
            <tr>
                <td>Everyone browsing the Site</td>
                <td>Standard web server logs (IP address, browser, pages requested); a
                    one-way cryptographic hash of your IP address (not the IP itself) tied to
                    form submissions and login attempts</td>
                <td>Keep the Site running, diagnose problems, and stop spam and
                    brute-force abuse of the sign-up, sign-in and password-reset forms</td>
            </tr>
            <tr>
                <td>Newsroom publisher accounts</td>
                <td>Your name, email address, a securely hashed password (we never store or
                    see the password itself), and the newspaper details you enter (name, location,
                    contact details, articles, images and PDFs you upload)</td>
                <td>Create and run your account, let you publish, and let readers find and
                    contact your newspaper through the details you chose to make public</td>
            </tr>
            <tr>
                <td>Anyone completing a form (registration, sign-in, password reset)</td>
                <td>The reCAPTCHA cookie and check, if we have it turned on</td>
                <td>Confirm you're a person, not an automated script</td>
            </tr>
            <tr>
                <td>Everyone, via cookies</td>
                <td>A session cookie (keeps you signed in and protects forms from forgery); if
                    advertising is enabled, cookies set by Google AdSense</td>
                <td>Site functionality; advertising that helps fund the Site (see below)</td>
            </tr>
        </tbody>
    </table>
    </div>

    <p>
        We do <strong>not</strong> ask readers to create an account, and we do not collect payment
        information anywhere on the Site.
    </p>

    <h2>Cookies</h2>
    <ul>
        <li><strong>Essential.</strong> A session cookie identifies your browser session so you can
            stay signed in and so forms are protected against cross-site forgery. The Site does not
            work without it, so it is set regardless of consent, as most data protection laws
            (including POPIA) allow for cookies that are strictly necessary for the service you
            asked for.</li>
        <li><strong>reCAPTCHA (if enabled).</strong> Google sets cookies to tell people apart from
            bots on the registration form. See Google's own policy below.</li>
        <li><strong>Advertising (if enabled).</strong> If we turn on Google AdSense, Google and its
            advertising partners may set cookies to serve and measure ads, including ads based on
            your visits to this and other sites.</li>
    </ul>
    <p>You can block or delete cookies in your browser at any time; the Site will still work,
        though you may need to sign in again more often.</p>

    <h2>Third parties we share information with</h2>
    <ul>
        <li><strong>Our hosting provider</strong>, who stores the database and uploaded files on
            our behalf and can only use them to provide hosting to us.</li>
        <li><strong>Our email delivery service</strong> (whichever mailbox or provider is configured
            for the Site), used only to send you account-related email — verification links,
            password resets, and (for newsroom owners) approval notices.</li>
        <li><strong>Google</strong>, if AdSense and/or reCAPTCHA are enabled, under Google's own
            <a href="https://policies.google.com/privacy" rel="nofollow noopener" target="_blank">privacy policy</a>.
            You can manage ad personalisation at
            <a href="https://adssettings.google.com" rel="nofollow noopener" target="_blank">Google Ad Settings</a>
            and read how Google uses cookies for ads at
            <a href="https://policies.google.com/technologies/ads" rel="nofollow noopener" target="_blank">policies.google.com/technologies/ads</a>.</li>
    </ul>
    <p>
        We do not sell personal information, and we do not share it with anyone else for their own
        marketing purposes. Some of the services above (including Google) may process information
        on servers outside South Africa; where that happens, we rely on their own compliance
        frameworks for cross-border transfers.
    </p>

    <h2>How long we keep it</h2>
    <p>
        Account information is kept for as long as the account is active, plus a reasonable period
        afterwards in case you want to return or in case we need it to resolve a dispute. Server
        logs and abuse-prevention hashes are kept for a short rolling window and then deleted
        automatically. Published articles and PDF editions are kept as a public record of what a
        newsroom published, unless removed under our
        <a href="<?= e(url('terms')) ?>#content-and-complaints">complaints process</a>.
    </p>

    <h2>Keeping it secure</h2>
    <p>
        Passwords are never stored in plain text — only a one-way cryptographic hash. Every form
        that changes data is protected against cross-site request forgery, and uploaded files are
        checked and stored where they cannot be executed as code. No online service can guarantee
        perfect security, but we take reasonable technical steps to protect what we hold.
    </p>

    <h2>Your rights under POPIA</h2>
    <p>You can ask us, at any time and free of charge, to:</p>
    <ul>
        <li><strong>Access</strong> the personal information we hold about you;</li>
        <li><strong>Correct</strong> information that is inaccurate, outdated or incomplete;</li>
        <li><strong>Delete</strong> your account and associated personal information, subject to
            anything we're legally required to keep;</li>
        <li><strong>Object</strong> to a particular use of your information; and</li>
        <li><strong>Ask how</strong> an automated decision (if we ever made one about you) was
            reached.</li>
    </ul>
    <p>
        To exercise any of these, email our Information Officer (details below). If you are not
        satisfied with our response, you may complain to South Africa's
        <a href="https://inforegulator.org.za" rel="nofollow noopener" target="_blank">Information Regulator</a>.
    </p>

    <h2>Children</h2>
    <p>The Site is not directed at children, and newsroom accounts are not intended for anyone
        under 18. If you believe a child has given us personal information, contact us and we will
        delete it.</p>

    <h2>Changes to this policy</h2>
    <p>We may update this policy as the Site changes. Material changes will be reflected in the
        "Last updated" date above.</p>

    <h2>Contact us / Information Officer</h2>
    <p>
        Under POPIA, our Information Officer is
        <strong><?= e($infoOfficerName !== '' ? $infoOfficerName : $operator) ?></strong>.
        <?php if ($infoOfficerEmail !== ''): ?>
            Reach them at <a href="mailto:<?= e($infoOfficerEmail) ?>"><?= e($infoOfficerEmail) ?></a>.
        <?php else: ?>
            A contact email has not been set yet — add one in <code>config('legal')</code> or the
            site's contact-email setting.
        <?php endif ?>
    </p>
</article>
