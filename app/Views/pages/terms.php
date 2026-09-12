<?php
/** @var string $title */
/** @var string $updated */
/** @var string $contactEmail */
/** @var string $operatorName */
/** @var string $operatorAddress */
/** @var string $infoOfficerEmail */
/** @var bool $needsReview */
$site = config('app.name');
$operator = $operatorName !== '' ? $operatorName : $site;
$complaintsEmail = $contactEmail !== '' ? $contactEmail : $infoOfficerEmail;
?>
<article class="prose">
    <h1><?= e($title) ?></h1>
    <p class="prose__meta">Last updated <?= e($updated) ?></p>

    <?php if ($needsReview): ?>
        <p class="notice notice--warn">
            <strong>Action needed before launch:</strong> the operator name and address below are
            still placeholders — set them in <code>config('legal')</code>.
        </p>
    <?php endif ?>

    <p>
        These terms govern your use of <?= e($site) ?> (the "Site"), operated by
        <strong><?= e($operator) ?></strong><?php if ($operatorAddress !== ''): ?>, <?= e($operatorAddress) ?><?php endif ?>
        ("we", "us"). By reading the Site or registering a newsroom account, you agree to these
        terms. If you don't agree, please don't use the Site.
    </p>

    <h2>What the Site is</h2>
    <p>
        <?= e($site) ?> is a directory and self-publishing platform for South African community
        and independent newspapers. Newsrooms register their own account, publish their own
        stories and PDF editions, and manage their own listing. <?= e($site) ?> provides the
        platform; it does not write, commission or edit newsroom content, and is not itself a
        newspaper or a publisher of the stories that appear on it.
    </p>

    <h2>Reader use</h2>
    <p>You may browse, read, search and download PDF editions for personal, non-commercial use.
        You may not:</p>
    <ul>
        <li>scrape, systematically copy or republish content from the Site without the
            originating newsroom's permission;</li>
        <li>attempt to bypass rate limits, CAPTCHAs, or other abuse-prevention measures;</li>
        <li>use the Site to distribute malware, spam, or unlawful material; or</li>
        <li>attempt to gain unauthorised access to any account or system on the Site.</li>
    </ul>

    <h2>Newsroom (publisher) accounts</h2>
    <p>If you register a newsroom account:</p>
    <ul>
        <li>You are responsible for keeping your login credentials confidential and for
            everything published or done through your account.</li>
        <li>You confirm that you (or your newsroom) are entitled to publish everything you upload
            — text, images and PDFs — and that it does not infringe anyone's copyright, trademark,
            privacy or other rights.</li>
        <li>You confirm that your content is lawful: not defamatory, not hate speech, not
            harassment, and not otherwise unlawful under South African law.</li>
        <li>You keep ownership of your content. By publishing it on the Site, you grant us a
            non-exclusive, royalty-free licence to host, store, display and distribute it as part
            of operating the Site (including in listings, search results and the archive) — for as
            long as it stays published or, for editions, in the archive.</li>
        <li>A registration must be approved by an administrator before it goes live; approval is
            at our discretion and is not a guarantee of ongoing hosting.</li>
    </ul>

    <h2 id="content-and-complaints">Content moderation and complaints</h2>
    <p>
        Content on the Site is created and published by individual newsrooms, not by us — we do
        not pre-screen every story. That said, we may, at our discretion and without notice:
    </p>
    <ul>
        <li>remove or unpublish any article, edition, image or listing;</li>
        <li>suspend or terminate a newsroom account; or</li>
        <li>refuse or revoke approval of a registration —</li>
    </ul>
    <p>
        for any reason, including a good-faith belief that content is unlawful, infringing,
        defamatory, or breaches these terms.
    </p>
    <p>
        <strong>If something published on the Site is defamatory, infringes your copyright or
        trademark, or is otherwise unlawful,</strong> tell us and we will investigate promptly.
        Include the URL of the content, why it's a problem, and your contact details.
        <?php if ($complaintsEmail !== ''): ?>
            Email <a href="mailto:<?= e($complaintsEmail) ?>"><?= e($complaintsEmail) ?></a>.
        <?php else: ?>
            (Set a contact email in <code>/admin/settings</code> so readers have somewhere to send this.)
        <?php endif ?>
        We are not responsible for content we are not aware of, but we will act on reports we
        receive.
    </p>

    <h2>Intellectual property</h2>
    <p>
        Newspaper names, logos and mastheads shown on the Site belong to their respective
        newsrooms and are shown for identification purposes only; <?= e($site) ?> claims no
        ownership over them. The Site's own design, code and branding belong to
        <?= e($operator) ?>.
    </p>

    <h2>Advertising</h2>
    <p>
        We may display Google AdSense advertising to help fund the Site. Ads are served by Google
        and its partners under their own terms; we do not control which specific ads are shown.
    </p>

    <h2>No warranty</h2>
    <p>
        The Site and everything published on it are provided "as is", without warranties of any
        kind. We do not warrant that newsroom content is accurate, complete or up to date, that the
        Site will be uninterrupted or error-free, or that defects will be corrected.
    </p>

    <h2>Limitation of liability</h2>
    <p>
        To the fullest extent permitted by South African law, <?= e($operator) ?> is not liable for
        any indirect, incidental or consequential loss arising from your use of the Site, from
        content published by a newsroom, or from any linked third-party service (including
        advertising). Nothing in these terms excludes liability that cannot lawfully be excluded.
    </p>

    <h2>Changes and termination</h2>
    <p>
        We may update these terms from time to time; continuing to use the Site after a change
        means you accept the new terms. We may suspend or discontinue the Site, or any account, at
        any time.
    </p>

    <h2>Governing law</h2>
    <p>These terms are governed by the laws of the Republic of South Africa, and any dispute is
        subject to the jurisdiction of the South African courts.</p>

    <h2>Contact</h2>
    <p>
        <?php if ($contactEmail !== ''): ?>
            <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>
        <?php else: ?>
            Add a contact email in <code>/admin/settings</code>.
        <?php endif ?>
    </p>
</article>
