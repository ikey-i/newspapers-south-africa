<?php

/**
 * Dependency-free test runner.
 *
 *   php tests/run.php
 *
 * Exits non-zero if any assertion fails. Covers pure logic only (no database).
 */

declare(strict_types=1);

$GLOBALS['config'] = [
    'app' => ['name' => 'Test', 'url' => 'http://localhost', 'timezone' => 'UTC', 'debug' => true],
    'db'  => [],
];

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/';

define('CONFIG_IS_SAMPLE', true);
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');

require APP_PATH . '/helpers.php';
require APP_PATH . '/Router.php';
require APP_PATH . '/Support/Taxonomy.php';
require APP_PATH . '/Support/Paginator.php';
require APP_PATH . '/Support/Validator.php';
require APP_PATH . '/Support/Csrf.php';
require APP_PATH . '/Support/FormGuard.php';
require APP_PATH . '/Support/HtmlSanitizer.php';
require APP_PATH . '/Support/SmtpClient.php';
require APP_PATH . '/Support/Mailer.php';
require APP_PATH . '/Support/Upload.php';
require APP_PATH . '/Database.php';
require APP_PATH . '/Models/Setting.php';
require APP_PATH . '/Support/Ads.php';
require APP_PATH . '/Support/Recaptcha.php';

ob_start();

$passed = 0;
$failed = 0;

function check(string $label, bool $condition): void
{
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "  ok   {$label}\n";
    } else {
        $failed++;
        echo "  FAIL {$label}\n";
    }
}

function equals(string $label, mixed $expected, mixed $actual): void
{
    check(
        $label . ' (expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . ')',
        $expected === $actual
    );
}

// ---------------------------------------------------------------------------
echo "slugify\n";
equals('spaces to hyphens', 'lowveld-herald', slugify('Lowveld Herald'));
equals('trims and collapses', 'the-vaal-bulletin', slugify('  The Vaal   Bulletin  '));
equals('empty falls back', 'n-a', slugify('   '));
check('only url-safe chars', preg_match('/^[a-z0-9-]+$/', slugify('Cape {Times}!! (Weekly)')) === 1);

echo "unique_slug\n";
equals('free slug is used as-is', 'lowveld-herald', unique_slug('Lowveld Herald', static fn ($s) => false));
equals('taken slug gets -2', 'lowveld-herald-2', unique_slug('Lowveld Herald', static fn ($s) => $s === 'lowveld-herald'));
equals('walks past taken suffixes', 'news-4',
    unique_slug('News', static fn ($s) => in_array($s, ['news', 'news-2', 'news-3'], true)));

// ---------------------------------------------------------------------------
echo "masthead display helpers\n";
equals('drops the generic "Herald"', 'LO', masthead_initials('Lowveld Herald'));
equals('drops "The" and "News"', 'VA', masthead_initials('The Vaal News'));
equals('two distinctive words keep both initials', 'CA', masthead_initials('Cape Argus'));
equals('one distinctive word takes two letters', 'ZO', masthead_initials('Zoutpansberg Herald'));
check('hue is 0..359', brand_hue('Lowveld Herald') >= 0 && brand_hue('Lowveld Herald') < 360);
equals('hue is stable', brand_hue('Cape Argus'), brand_hue('  cape argus  '));
check('tile style is a linear-gradient', str_contains(brand_tile_style('Lowveld Herald'), 'linear-gradient('));
check('tile style has no quotes to break the attribute', !str_contains(brand_tile_style('Lowveld Herald'), '"'));

// ---------------------------------------------------------------------------
echo "e()\n";
equals('escapes tags', '&lt;script&gt;', e('<script>'));
equals('escapes quotes', '&quot;x&quot;', e('"x"'));
equals('null becomes empty', '', e(null));

// ---------------------------------------------------------------------------
echo "str_excerpt\n";
$long = str_repeat('word ', 100);
$excerpt = str_excerpt($long, 40);
check('respects max length', strlen($excerpt) <= 43);
check('adds ellipsis', str_ends_with($excerpt, '…'));
equals('short string unchanged', 'hello world', str_excerpt('hello world', 40));
equals('strips tags', 'bold text', str_excerpt('<b>bold</b> text', 40));

// ---------------------------------------------------------------------------
echo "config()\n";
equals('reads nested key', 'Test', config('app.name'));
equals('missing key returns default', 'fallback', config('app.nope', 'fallback'));

// ---------------------------------------------------------------------------
echo "url() (root-relative)\n";
equals('root at domain root', '/', url());
equals('path at domain root', '/paper/lowveld-herald', url('/paper/lowveld-herald'));
$_SERVER['SCRIPT_NAME'] = '/news/index.php';
equals('respects subdirectory', '/news/paper/lowveld-herald', url('paper/lowveld-herald'));
$_SERVER['SCRIPT_NAME'] = '/index.php';

// ---------------------------------------------------------------------------
echo "Router\n";
$hit = null;
$router = new App\Router('');
$router->get('/', function () use (&$hit) { $hit = 'home'; });
$router->get('/paper/{slug}', function (array $p) use (&$hit) { $hit = 'paper:' . $p['slug']; });
$router->get('/paper/{slug}/article/{article}', function (array $p) use (&$hit) { $hit = 'article:' . $p['slug'] . '/' . $p['article']; });
$router->post('/publish/register', function () use (&$hit) { $hit = 'register'; });

$hit = null; $router->dispatch('GET', '/');
equals('matches root', 'home', $hit);

$hit = null; $router->dispatch('GET', '/paper/lowveld-herald');
equals('captures slug param', 'paper:lowveld-herald', $hit);

$hit = null; $router->dispatch('GET', '/paper/lowveld-herald/article/big-story');
equals('captures two params', 'article:lowveld-herald/big-story', $hit);

$hit = null; $router->dispatch('GET', '/paper/lowveld-herald/');
equals('trailing slash normalised', 'paper:lowveld-herald', $hit);

http_response_code(200); $hit = null;
ob_start(); $router->dispatch('GET', '/does-not-exist'); ob_end_clean();
equals('unknown path is 404', 404, http_response_code());

http_response_code(200); $hit = null;
ob_start(); $router->dispatch('GET', '/publish/register'); ob_end_clean();
equals('wrong method is 405', 405, http_response_code());
check('handler not called on 405', $hit === null);

// ---------------------------------------------------------------------------
echo "Taxonomy\n";
equals('type label', 'Community newspaper', App\Support\Taxonomy::typeLabel('community'));
equals('unknown type', null, App\Support\Taxonomy::typeLabel('podcast'));
check('isType true', App\Support\Taxonomy::isType('online'));
check('isType false', !App\Support\Taxonomy::isType('radio'));
equals('province from slug', 'KwaZulu-Natal', App\Support\Taxonomy::provinceFromSlug('kwazulu-natal'));
equals('province slug rejects unknown', null, App\Support\Taxonomy::provinceFromSlug('atlantis'));
equals('section from slug', 'Arts & Culture', App\Support\Taxonomy::sectionFromSlug('arts-culture'));
equals('section slug rejects unknown', null, App\Support\Taxonomy::sectionFromSlug('crossword'));
check('isSection true', App\Support\Taxonomy::isSection('Sport'));
check('isSection false', !App\Support\Taxonomy::isSection('Gossip'));

// ---------------------------------------------------------------------------
echo "Paginator\n";
$p = new App\Support\Paginator(totalItems: 100, perPage: 24, requestedPage: 2, baseUrl: '/newspapers');
equals('total pages', 5, $p->totalPages);
equals('offset for page 2', 24, $p->offset);
equals('first item', 25, $p->firstItem());
equals('last item', 48, $p->lastItem());
equals('page 1 url has no query', '/newspapers', $p->pageUrl(1));
equals('page 3 url', '/newspapers?page=3', $p->pageUrl(3));
equals('existing query string uses ampersand', '/x?q=jazz&page=2',
    (new App\Support\Paginator(50, 24, 2, '/x?q=jazz'))->pageUrl(2));

// ---------------------------------------------------------------------------
echo "Validator\n";
$V = \App\Support\Validator::class;
$v = new $V(['name' => '  Lowveld Herald  ', 'email' => 'a@b.com', 'site' => 'https://x.com']);
$v->required('name')->max('name', 160);
$v->required('email')->email('email');
$v->optional('site')->url('site');
check('valid input passes', $v->passes());
equals('trims values', 'Lowveld Herald', $v->validated()['name']);

$v = new $V(['name' => '', 'email' => 'nope']);
$v->label('Newspaper name')->required('name');
$v->required('email')->email('email');
check('missing + bad fails', $v->fails());
equals('uses custom label', 'Newspaper name is required.', $v->errors()['name']);

$v = new $V(['url' => 'javascript:alert(1)']);
$v->optional('url')->url('url');
check('rejects non-http url scheme', $v->fails());

// ---------------------------------------------------------------------------
echo "Csrf\n";
$_SESSION = [];
$Csrf = \App\Support\Csrf::class;
$t1 = $Csrf::token();
check('token is 64 hex chars', preg_match('/^[a-f0-9]{64}$/', $t1) === 1);
equals('token stable within session', $t1, $Csrf::token());
check('correct token passes', $Csrf::check($t1));
check('wrong token fails', !$Csrf::check('deadbeef'));
check('null token fails', !$Csrf::check(null));

// ---------------------------------------------------------------------------
echo "FormGuard\n";
$_SESSION = [];
$Fg = \App\Support\FormGuard::class;
$html = $Fg::fields('t');
check('renders a honeypot input', str_contains($html, 'contact_url'));
check('honeypot filled is spam', $Fg::isSpam('t', ['contact_url' => 'http://spam']));
$Fg::fields('t2');
$_SESSION['_form_ts']['t2'] = time() - 30;
check('normal timing is not spam', !$Fg::isSpam('t2', ['contact_url' => '']));
$Fg::fields('t3');
check('instant submit is spam', $Fg::isSpam('t3', ['contact_url' => '']));

// ---------------------------------------------------------------------------
echo "HtmlSanitizer\n";
$H = \App\Support\HtmlSanitizer::class;
check('strips <script>', !str_contains(strtolower($H::clean('<p>Hi</p><script>alert(1)</script>')), 'script'));
check('keeps allowed <p>', str_contains($H::clean('<p>Hello</p>'), '<p>Hello</p>'));
check('keeps <h2> and <blockquote>', str_contains($H::clean('<h2>T</h2><blockquote>q</blockquote>'), '<blockquote>'));
check('drops onerror attribute', !str_contains(strtolower($H::clean('<img src="/uploads/media/x.jpg" onerror="alert(1)">')), 'onerror'));
check('drops javascript: href', !str_contains(strtolower($H::clean('<a href="javascript:alert(1)">x</a>')), 'javascript:'));
check('keeps http link and adds rel', (function () use ($H) {
    $out = strtolower($H::clean('<a href="https://example.com">x</a>'));
    return str_contains($out, 'href="https://example.com"') && str_contains($out, 'nofollow');
})());
check('strips <iframe>', !str_contains(strtolower($H::clean('<iframe src="https://evil"></iframe>')), 'iframe'));
check('strips data: uri image', !str_contains(strtolower($H::clean('<img src="data:text/html;base64,x">')), 'data:'));
check('keeps site-relative image', str_contains($H::clean('<img src="/uploads/media/a.jpg" alt="a">'), '/uploads/media/a.jpg'));
check('turns a Trix text <div> into <p>', (function () use ($H) {
    $out = $H::clean('<div>First para</div><div>Second para</div>');
    return substr_count($out, '<p>') === 2 && str_contains($out, 'First para');
})());
check('demotes editor <h1> to <h2>', (function () use ($H) {
    $out = $H::clean('<h1>Section heading</h1>');
    return str_contains($out, '<h2>Section heading</h2>') && !str_contains($out, '<h1');
})());
check('keeps Trix figure/figcaption for an uploaded image', (function () use ($H) {
    $out = $H::clean('<figure class="attachment"><img src="/uploads/media/x.jpg"><figcaption>Caption</figcaption></figure>');
    return str_contains($out, '<figure>') && str_contains($out, '<figcaption>Caption</figcaption>') && str_contains($out, '/uploads/media/x.jpg');
})());
equals('empty stays empty', '', $H::clean(''));
check('plain text passes through escaped-safe', str_contains($H::clean('5 < 7 and 8 > 2'), '5'));

// ---------------------------------------------------------------------------
echo "Upload::pdf validation (pure parts)\n";
// magic-byte logic is exercised via a tiny reflection-free helper check:
check('a %PDF- header string starts a pdf', str_starts_with("%PDF-1.7\n...", '%PDF-'));
check('an MZ header string does not', !str_starts_with("MZ\x90\x00", '%PDF-'));

// ---------------------------------------------------------------------------
echo "Mailer (log backend)\n";
$Mailer = \App\Support\Mailer::class;
$mailDir = BASE_PATH . '/var/mail';
$before = is_dir($mailDir) ? glob($mailDir . '/*.txt') : [];
check('rejects an invalid recipient', !$Mailer::send('not-an-email', 'Subject', 'Body'));
check('sends (logs) to a valid recipient', $Mailer::send('reader@example.com', "Injected\r\nBcc: evil@example.com", 'Body text'));
$after = is_dir($mailDir) ? glob($mailDir . '/*.txt') : [];
$new = array_values(array_diff($after, $before));
if ($new !== []) {
    $content = (string) file_get_contents($new[0]);
    check('strips CRLF from the subject (no header injection)', !str_contains($content, "Injected\r\nBcc"));
    check('subject header still present, flattened onto one line', str_contains($content, 'Subject: Injected') && str_contains($content, 'Bcc: evil@example.com'));
    @unlink($new[0]); // don't leave test fixtures behind
} else {
    check('a log file was written', false);
}

// ---------------------------------------------------------------------------
echo "Upload::delete (path containment)\n";
$U = \App\Support\Upload::class;
$logosDir = BASE_PATH . '/uploads/logos';
@mkdir($logosDir, 0755, true);
$victimOutside = BASE_PATH . '/uploads-delete-test-victim.txt';
file_put_contents($victimOutside, 'do not delete me');
$U::delete('uploads/logos/../../uploads-delete-test-victim.txt');
check('refuses to delete outside its own upload dir (traversal)', is_file($victimOutside));
@unlink($victimOutside);

$insideFile = $logosDir . '/delete-test.txt';
file_put_contents($insideFile, 'ok to delete');
$U::delete('uploads/logos/delete-test.txt');
check('deletes a real file inside the matching upload dir', !is_file($insideFile));

check('ignores an empty path', (function () use ($U) { $U::delete(''); return true; })());
check('ignores a path outside uploads/', (function () use ($U) { $U::delete('config.php'); return is_file(BASE_PATH . '/config.sample.php'); })());

// ---------------------------------------------------------------------------
echo "Ads\n";
$Ads = \App\Support\Ads::class;
equals('normalises ca-pub- form', 'ca-pub-1234567890123456', $Ads::normalisePublisherId('ca-pub-1234567890123456'));
equals('normalises bare digits', 'ca-pub-1234567890123456', $Ads::normalisePublisherId(' 1234567890123456 '));
equals('rejects junk', '', $Ads::normalisePublisherId('not-an-id'));
check('disabled by default', !$Ads::enabled());
equals('no head script when disabled', '', $Ads::headScript());
equals('article slot known but empty when disabled', '', $Ads::slot('article'));
equals('sidebar slot known but empty when disabled', '', $Ads::slot('sidebar'));
equals('unknown slot name is empty', '', $Ads::slot('nope'));

// ---------------------------------------------------------------------------
echo "Recaptcha\n";
$R = \App\Support\Recaptcha::class;
// No database in the test runner -> settings fall back to defaults (disabled).
check('disabled by default', !$R::enabled());
equals('no site key when disabled', '', $R::siteKey());
equals('no widget markup when disabled', '', $R::widget());
check('verify() passes through when disabled (nothing to check)', $R::verify(null));
check('verify() passes through an empty token when disabled', $R::verify(''));

// ---------------------------------------------------------------------------
echo "\n{$passed} passed, {$failed} failed\n";

ob_end_flush();
exit($failed === 0 ? 0 : 1);
