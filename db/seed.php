<?php

/**
 * Demo content for local development and previews.
 *
 *   php db/seed.php            add the demo data (skips anything already there)
 *   php db/seed.php --fresh    delete all demo data first, then add it
 *
 * Everything here is FICTIONAL. Real newspapers are added by the admin or by
 * newsrooms registering themselves — do not seed real mastheads.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

use App\Database;

if (CONFIG_IS_SAMPLE) {
    fwrite(STDERR, "No config.php found. Copy config.sample.php to config.php first.\n");
    exit(1);
}
if (!Database::isReady()) {
    fwrite(STDERR, "Run `php db/migrate.php` first.\n");
    exit(1);
}

$fresh = in_array('--fresh', $argv, true);
$pdo = Database::connection();
$demoPassword = 'demo-password-123';

/** A small but valid single-page PDF with a title and subtitle. */
function make_pdf(string $title, string $subtitle): string
{
    $esc = static fn (string $s): string => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $s);
    $stream = "BT /F1 26 Tf 60 760 Td (" . $esc($title) . ") Tj "
            . "/F1 13 Tf 0 -34 Td (" . $esc($subtitle) . ") Tj "
            . "/F1 11 Tf 0 -60 Td (This is a placeholder PDF for the demo dataset.) Tj ET";

    $objects = [
        "<< /Type /Catalog /Pages 2 0 R >>",
        "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
        "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
        "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>",
        "<< /Length " . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $i => $body) {
        $offsets[$i] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n" . $body . "\nendobj\n";
    }
    $xrefPos = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    foreach ($offsets as $off) {
        $pdf .= sprintf("%010d 00000 n \n", $off);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefPos . "\n%%EOF";
    return $pdf;
}

function store_pdf(string $bytes, string $stem): array
{
    $dir = BASE_PATH . '/uploads/pdfs';
    @mkdir($dir, 0755, true);
    $name = $stem . '-' . bin2hex(random_bytes(3)) . '.pdf';
    file_put_contents($dir . '/' . $name, $bytes);
    return ['uploads/pdfs/' . $name, strlen($bytes)];
}

// ---------------------------------------------------------------------------
// The dataset (fictional)
// ---------------------------------------------------------------------------
$papers = [
    [
        'slug' => 'lowveld-community-herald', 'name' => 'Lowveld Community Herald',
        'type' => 'community', 'province' => 'Mpumalanga', 'city' => 'Mbombela',
        'tagline' => 'Your weekly voice for the Lowveld',
        'about' => 'A free community newspaper covering Mbombela and the surrounding farming towns since 2009. Run by a small volunteer newsroom.',
        'languages' => 'English, isiSwati', 'established_year' => 2009, 'is_featured' => 1,
        'owner' => ['name' => 'Thandi Mahlangu', 'email' => 'editor@lowveld.demo'],
        'articles' => [
            ['News', 'Council approves new taxi rank for Mbombela CBD', 'Construction is due to start next month after a two-year delay over land.'],
            ['Community', 'Volunteers plant 400 trees along the Crocodile River', 'The weekend drive was organised by three local schools and a farmers association.'],
            ['Sport', 'Nelspruit FC clinch promotion with late winner', 'A stoppage-time goal sent the home crowd streaming onto the pitch.'],
            ['Business', 'Farm stall co-operative opens second outlet', 'Twelve small growers now share a permanent spot on the R40.'],
            ['Opinion', 'We need to talk about potholes on the R37', 'The road to the mines is costing residents a fortune in repairs.'],
        ],
        'editions' => [
            ['Week of 1 September 2026', '2026-09-01', 'Sixteen pages: council budget, matric countdown, and the farmers market guide.'],
            ['Week of 8 September 2026', '2026-09-08', 'Twenty pages this week including the schools sport wrap.'],
        ],
    ],
    [
        'slug' => 'karoo-koerier', 'name' => 'Karoo Koerier',
        'type' => 'independent', 'province' => 'Northern Cape', 'city' => 'De Aar',
        'tagline' => 'Nuus uit die hart van die Karoo',
        'about' => 'An independent bilingual weekly serving De Aar, Hanover and Colesberg. Family-owned for three generations.',
        'languages' => 'Afrikaans, English', 'established_year' => 1974, 'is_featured' => 1,
        'owner' => ['name' => 'Pieter van Wyk', 'email' => 'nuus@karookoerier.demo'],
        'articles' => [
            ['News', 'Railway line upgrade brings 200 temporary jobs to De Aar', 'Work on the Cape main line reaches the junction town in October.'],
            ['Agriculture', 'Wool prices lift after two hard seasons', 'Local farmers cautiously optimistic ahead of the spring shear.'],
            ['Arts & Culture', 'Karoo Nasionale Fees returns after a year off', 'Organisers expect the biggest programme yet on the showgrounds.'],
            ['Education', 'De Aar high school debating team heads to nationals', 'The team of five won the regional final in Kimberley.'],
        ],
        'editions' => [
            ['Uitgawe 34 / 2026', '2026-08-25', 'Die spoorlyn-storie, plus die volledige sportblad.'],
        ],
    ],
    [
        'slug' => 'zululand-observer-weekly', 'name' => 'Zululand Observer Weekly',
        'type' => 'regional', 'province' => 'KwaZulu-Natal', 'city' => 'Richards Bay',
        'tagline' => 'Covering Zululand from the coast to the hills',
        'about' => 'A regional weekly reporting on Richards Bay, Empangeni and the wider uMhlathuze area — municipal affairs, industry, and community life.',
        'languages' => 'English, isiZulu', 'established_year' => 1994,
        'owner' => ['name' => 'Nomvula Khumalo', 'email' => 'newsdesk@zululand.demo'],
        'articles' => [
            ['News', 'Port expansion hearings draw a packed hall in Richards Bay', 'Residents raised concerns about dust, traffic and jobs.'],
            ['Environment', 'Turtle nesting season off to a strong start on the north coast', 'Volunteers have logged 30 nests in the first three weeks.'],
            ['Politics', 'uMhlathuze tables a balanced budget, cuts capital spend', 'The municipality says grants are down and collections are flat.'],
            ['Lifestyle', 'A weekend guide to the Enseleni nature reserve', 'Bird hides, short trails, and the best time to spot hippo.'],
        ],
        'editions' => [
            ['3 September 2026', '2026-09-03', 'Port hearings special report and the property pull-out.'],
        ],
    ],
    [
        'slug' => 'diamond-fields-free-press', 'name' => 'Diamond Fields Free Press',
        'type' => 'independent', 'province' => 'Northern Cape', 'city' => 'Kimberley',
        'tagline' => 'Independent news for Kimberley and the diamond route',
        'about' => 'A reader-funded independent title focused on accountability journalism in the Sol Plaatje municipality.',
        'languages' => 'English, Setswana, Afrikaans', 'established_year' => 2018,
        'owner' => ['name' => 'Lebo Molefe', 'email' => 'tips@diamondfields.demo'],
        'articles' => [
            ['News', 'Water outages hit the Kimberley CBD for a fourth week', 'The municipality blames a failed pump station and ageing pipes.'],
            ['Investigations', 'Tracing a R14-million tender that built half a road', 'Documents show the contractor was paid in full in 2024.'],
            ['Community', 'The Galeshewe soup kitchen that feeds 600 a day', 'Run from a converted shipping container by two retired teachers.'],
        ],
        'editions' => [],
    ],
    [
        'slug' => 'garden-route-online', 'name' => 'Garden Route Online',
        'type' => 'online', 'province' => 'Western Cape', 'city' => 'George',
        'tagline' => 'Daily updates from Mossel Bay to Storms River',
        'about' => 'An online-only publication covering the Garden Route — tourism, municipal news, weather and the outdoors. No print edition.',
        'languages' => 'English, Afrikaans', 'established_year' => 2021,
        'owner' => ['name' => 'Sarah Adonis', 'email' => 'desk@gardenroute.demo'],
        'articles' => [
            ['News', 'N2 reopens at Bloukrans after rockfall clearing', 'A single lane was closed for most of Tuesday.'],
            ['Tourism', 'Whale-watching season starts early off Plettenberg Bay', 'Operators report the first southern rights of the year.'],
            ['Weather', 'Cut-off low to bring heavy rain to the southern Cape', 'Disaster management urges caution on low-water bridges.'],
            ['Sport', 'George cyclists take three podiums at the Karoo classic', 'The junior women’s race went down to a sprint finish.'],
        ],
        'editions' => [],
    ],
];

// ---------------------------------------------------------------------------
if ($fresh) {
    echo "Deleting demo data…\n";
    foreach ($papers as $p) {
        $row = Database::first('SELECT id FROM newspapers WHERE slug = ?', [$p['slug']]);
        if ($row) {
            // FK cascades handle articles / editions / publishers.
            $pdo->prepare('DELETE FROM newspapers WHERE id = ?')->execute([(int) $row['id']]);
        }
    }
}

$made = ['papers' => 0, 'articles' => 0, 'editions' => 0, 'publishers' => 0];
$logins = [];

foreach ($papers as $p) {
    if (Database::first('SELECT id FROM newspapers WHERE slug = ?', [$p['slug']])) {
        echo "skip {$p['slug']} (already present)\n";
        continue;
    }

    $pdo->prepare(
        "INSERT INTO newspapers (slug, name, type, tagline, about, province, city, languages, established_year, is_featured, status, reviewed_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())"
    )->execute([
        $p['slug'], $p['name'], $p['type'], $p['tagline'], $p['about'],
        $p['province'], $p['city'], $p['languages'], $p['established_year'], $p['is_featured'] ?? 0,
    ]);
    $newspaperId = (int) $pdo->lastInsertId();
    $made['papers']++;

    $pdo->prepare(
        "INSERT INTO publishers (newspaper_id, name, email, password_hash, role, email_verified_at)
         VALUES (?, ?, ?, ?, 'owner', NOW())"
    )->execute([
        $newspaperId, $p['owner']['name'], $p['owner']['email'],
        password_hash($demoPassword, PASSWORD_DEFAULT),
    ]);
    $made['publishers']++;
    $logins[] = $p['owner']['email'];

    $daysAgo = 2;
    foreach ($p['articles'] as [$section, $title, $standfirst]) {
        $slug = slugify($title);
        $body = '<p>' . e($standfirst) . '</p>'
              . '<p>' . e($p['city']) . ' — Reporters for the ' . e($p['name'])
              . ' spent the week following this story as it developed. What follows is a summary of what is known so far.</p>'
              . '<h2>Background</h2>'
              . '<p>The situation has been building for some time. Residents and officials disagree on the details, but most accept that a decision is now overdue.</p>'
              . '<blockquote>&ldquo;We have been raising this for two years,&rdquo; one resident said. &ldquo;It is good to finally see movement.&rdquo;</blockquote>'
              . '<p>The newspaper will follow up in next week&rsquo;s edition.</p>';

        $pdo->prepare(
            "INSERT INTO articles (newspaper_id, slug, title, standfirst, body_html, section, author_name, status, published_at, views)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'published', (NOW() - INTERVAL ? DAY), ?)"
        )->execute([
            $newspaperId, $slug, $title, $standfirst, $body, $section,
            'Staff Reporter', $daysAgo, random_int(20, 900),
        ]);
        $made['articles']++;
        $daysAgo += random_int(2, 6);
    }

    foreach ($p['editions'] as [$title, $date, $desc]) {
        [$pdfPath, $pdfSize] = store_pdf(make_pdf($p['name'], $title), slugify($p['slug'] . '-' . $title));
        $pdo->prepare(
            "INSERT INTO editions (newspaper_id, title, edition_date, description, pdf_path, pdf_size, is_published)
             VALUES (?, ?, ?, ?, ?, ?, 1)"
        )->execute([$newspaperId, $title, $date, $desc, $pdfPath, $pdfSize]);
        $made['editions']++;
    }

    echo "added {$p['slug']}\n";
}

echo "\nDone: {$made['papers']} newspapers, {$made['articles']} articles, "
   . "{$made['editions']} editions, {$made['publishers']} publisher accounts.\n";

if ($logins !== []) {
    echo "\nDemo publisher logins (all use the password \"{$demoPassword}\"):\n";
    foreach ($logins as $email) {
        echo "  {$email}\n";
    }
    echo "\nSign in at " . rtrim((string) config('app.url'), '/') . "/publish/login\n";
}
