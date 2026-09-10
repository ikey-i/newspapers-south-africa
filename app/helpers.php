<?php

/**
 * Procedural helper functions available everywhere.
 */

declare(strict_types=1);

if (!function_exists('config')) {
    /**
     * Read a configuration value using dot notation, e.g. config('db.host').
     */
    function config(string $key, mixed $default = null): mixed
    {
        $value = $GLOBALS['config'] ?? [];
        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }
        return $value;
    }
}

if (!function_exists('e')) {
    /**
     * Escape a value for safe output in HTML.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('slugify')) {
    /**
     * Convert arbitrary text into a URL-safe slug.
     */
    function slugify(string $text): string
    {
        $text = trim($text);

        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        $text = trim($text, '-');

        return $text !== '' ? $text : 'n-a';
    }
}

if (!function_exists('unique_slug')) {
    /**
     * A slug derived from $text that does not already exist.
     *
     * @param callable(string):bool $exists  Returns true if the slug is taken.
     */
    function unique_slug(string $text, callable $exists): string
    {
        $base = slugify($text);
        if (!$exists($base)) {
            return $base;
        }
        for ($n = 2; $n < 1000; $n++) {
            $candidate = $base . '-' . $n;
            if (!$exists($candidate)) {
                return $candidate;
            }
        }
        return $base . '-' . bin2hex(random_bytes(3));
    }
}

if (!function_exists('base_url')) {
    /**
     * Build an absolute URL from the configured site URL.
     */
    function base_url(string $path = ''): string
    {
        $root = rtrim((string) config('app.url', ''), '/');
        $path = ltrim($path, '/');
        return $path === '' ? $root . '/' : $root . '/' . $path;
    }
}

if (!function_exists('base_path')) {
    /**
     * Directory the app is served from, e.g. "" at the domain root or
     * "/radio" in a subdirectory. Derived from the current request.
     */
    function base_path(): string
    {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        return $dir === '/' ? '' : rtrim($dir, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Root-relative URL for an internal link or asset. Works regardless of the
     * host/port the site is served on (unlike the absolute base_url()).
     */
    function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return base_path() . '/' . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * URL for a file in /assets, with a cache-busting version stamp.
     */
    function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $full = BASE_PATH . '/' . $path;
        $version = is_file($full) ? (string) filemtime($full) : '1';
        return url($path) . '?v=' . $version;
    }
}

if (!function_exists('redirect')) {
    /**
     * Send a redirect response and stop execution.
     */
    function redirect(string $path, int $status = 302): never
    {
        if (preg_match('#^https?://#', $path) === 1) {
            $location = $path;
        } elseif (str_starts_with($path, '/')) {
            // Already a root-relative path (e.g. from url()); send as-is so the
            // redirect stays on whatever host/port served the request.
            $location = $path;
        } else {
            $location = url($path);
        }
        header('Location: ' . $location, true, $status);
        exit;
    }
}

if (!function_exists('view')) {
    /**
     * Render a view file from app/Views and return the output as a string.
     */
    function view(string $__name, array $__data = []): string
    {
        $__file = APP_PATH . '/Views/' . str_replace('..', '', $__name) . '.php';
        if (!is_file($__file)) {
            throw new RuntimeException("View not found: {$__name}");
        }
        // EXTR_OVERWRITE (the default) so view data always wins; the local
        // names are underscore-prefixed to stay out of the way.
        extract($__data);
        ob_start();
        require $__file;
        return (string) ob_get_clean();
    }
}

if (!function_exists('render')) {
    /**
     * Render a view wrapped in a layout and echo the full HTML page.
     *
     * The view's output is passed to the layout as $content. Any keys in
     * $data whose names start with "layout_" are also exposed to the layout
     * (e.g. layout_title, layout_description).
     */
    function render(string $name, array $data = [], string $layout = 'layout'): void
    {
        $content = view($name, $data);

        $layoutData = ['content' => $content];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'layout_')) {
                $layoutData[substr($key, 7)] = $value;
            }
        }

        echo view($layout, $layoutData);
    }
}

if (!function_exists('admin_view')) {
    /**
     * Render an admin view inside the admin layout and echo the page.
     * The view may set $heading (page title) via the data array.
     */
    function admin_view(string $name, array $data = []): void
    {
        header('X-Robots-Tag: noindex, nofollow');

        $content = view($name, $data);

        echo view('admin/layout', [
            'content' => $content,
            'heading' => (string) ($data['heading'] ?? 'Admin'),
            'title'   => ((string) ($data['heading'] ?? 'Admin')) . ' — ' . config('app.name') . ' admin',
            'user'    => \App\Support\Auth::user(),
            'success' => flash_pull('admin_success'),
        ]);
    }
}

if (!function_exists('publish_view')) {
    /**
     * Render a publisher-dashboard view inside the publisher layout and echo it.
     */
    function publish_view(string $name, array $data = []): void
    {
        header('X-Robots-Tag: noindex, nofollow');

        $content = view($name, $data);

        echo view('publish/layout', [
            'content' => $content,
            'heading' => (string) ($data['heading'] ?? 'Publisher'),
            'title'   => ((string) ($data['heading'] ?? 'Publisher')) . ' — ' . config('app.name'),
            'user'    => $data['user'] ?? \App\Support\PublisherAuth::user(),
            'success' => flash_pull('publish_success'),
            'notice'  => flash_pull('publish_notice'),
            'head'    => (string) ($data['head'] ?? ''),
            'scripts' => (string) ($data['scripts'] ?? ''),
        ]);
    }
}

if (!function_exists('render_error_page')) {
    /**
     * Render a friendly error page for the given HTTP status code.
     */
    function render_error_page(int $code, string $message = ''): void
    {
        http_response_code($code);
        $titles = [
            404 => 'Page not found',
            500 => 'Something went wrong',
        ];
        $title = $titles[$code] ?? 'Error';
        try {
            echo view('layout', [
                'title'   => $title,
                'content' => view('error', [
                    'code'    => $code,
                    'title'   => $title,
                    'message' => $message,
                ]),
            ]);
        } catch (\Throwable) {
            header('Content-Type: text/plain; charset=utf-8');
            echo "{$code} {$title}";
        }
    }
}

if (!function_exists('masthead_initials')) {
    /**
     * One or two letters to stand in for a missing newspaper logo.
     */
    function masthead_initials(string $name): string
    {
        $name = trim($name);
        $words = array_values(array_filter(
            preg_split('/\s+/', $name) ?: [],
            // Drop generic words so "Lowveld Herald" and "Lowveld News" differ.
            static fn (string $w): bool => !in_array(strtoupper($w), ['THE', 'NEWS', 'HERALD', 'TIMES', 'POST', 'BULLETIN', 'GAZETTE', 'PRESS', 'DAILY', 'WEEKLY'], true)
        ));

        if (count($words) >= 2 && $words[0] !== '' && $words[1] !== '') {
            $s = mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1);
        } elseif (count($words) === 1) {
            $s = mb_substr($words[0], 0, 2);
        } else {
            $s = mb_substr($name, 0, 2);
        }
        return function_exists('mb_strtoupper') ? mb_strtoupper($s) : strtoupper($s);
    }
}

if (!function_exists('brand_hue')) {
    /**
     * A stable hue (0–359) derived from a name, used to colour a placeholder
     * masthead tile.
     */
    function brand_hue(string $seed): int
    {
        $seed = strtolower(trim($seed));
        return (int) (hexdec(substr(md5($seed), 0, 6)) % 360);
    }
}

if (!function_exists('brand_tile_style')) {
    /**
     * Inline `style` value for a placeholder masthead tile: a gradient keyed to
     * the name so every newspaper gets a distinct, stable colour.
     */
    function brand_tile_style(string $seed): string
    {
        $h = brand_hue($seed);
        $h2 = ($h + 42) % 360;
        return sprintf(
            'background-image:linear-gradient(145deg,hsl(%ddeg 78%% 60%%),hsl(%ddeg 72%% 45%%));',
            $h,
            $h2
        );
    }
}

if (!function_exists('str_excerpt')) {
    /**
     * Trim text to a maximum length on a word boundary, adding an ellipsis.
     */
    function str_excerpt(string $text, int $maxLength = 160): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');

        $hasMb = function_exists('mb_strlen');
        $length = $hasMb ? mb_strlen($text) : strlen($text);
        if ($length <= $maxLength) {
            return $text;
        }

        $truncated = $hasMb ? mb_substr($text, 0, $maxLength) : substr($text, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');
        if ($lastSpace !== false) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        return rtrim($truncated) . '…';
    }
}

if (!function_exists('start_session')) {
    /**
     * Start a hardened session if one is not already running.
     */
    function start_session(): void
    {
        if (PHP_SAPI === 'cli' || session_status() === PHP_SESSION_ACTIVE || headers_sent()) {
            // On the CLI (tests, migrations) there is no session; callers that
            // read $_SESSION still work against whatever the test set up.
            if (PHP_SAPI === 'cli' && !isset($_SESSION)) {
                $_SESSION = [];
            }
            return;
        }

        $https = (($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off')
            || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
            || str_starts_with((string) config('app.url'), 'https://');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => (base_path() === '' ? '/' : base_path()),
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => $https,
        ]);
        session_name('rsa_session');
        @session_start();
    }
}

if (!function_exists('flash')) {
    /**
     * Store a one-shot value to be read on the next request.
     */
    function flash(string $key, mixed $value): void
    {
        start_session();
        $_SESSION['_flash'][$key] = $value;
    }
}

if (!function_exists('flash_pull')) {
    /**
     * Read and remove a flashed value.
     */
    function flash_pull(string $key, mixed $default = null): mixed
    {
        start_session();
        if (!isset($_SESSION['_flash'][$key])) {
            return $default;
        }
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        return $value;
    }
}

if (!function_exists('client_ip')) {
    /**
     * Best-effort client IP. Trusts proxy headers only when app.trust_proxy
     * is enabled in config (cPanel behind a load balancer / Cloudflare).
     */
    function client_ip(): string
    {
        if (config('app.trust_proxy', false)) {
            $forwarded = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            $first = trim(explode(',', (string) $forwarded)[0]);
            if ($first !== '' && filter_var($first, FILTER_VALIDATE_IP) !== false) {
                return $first;
            }
        }
        return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    }
}

if (!function_exists('client_ip_hash')) {
    /**
     * A stable, non-reversible identifier for the current client, for
     * rate-limiting and abuse tracking without storing raw IPs.
     */
    function client_ip_hash(): string
    {
        return hash('sha256', client_ip() . '|' . (string) config('app.url'));
    }
}

if (!function_exists('ad_slot')) {
    /**
     * Render a manual AdSense unit for the named placeholder (or nothing when
     * ads are disabled). Names: leaderboard, infeed, article, sidebar.
     */
    function ad_slot(string $name): string
    {
        return \App\Support\Ads::slot($name);
    }
}

if (!function_exists('abort')) {
    /**
     * Render an error page for the given status code and stop.
     */
    function abort(int $code, string $message = ''): never
    {
        render_error_page($code, $message);
        exit;
    }
}

if (!function_exists('old')) {
    /**
     * Previously submitted value for a form field, for re-rendering after a
     * validation error. Reads from a $GLOBALS['old'] array set by the controller.
     */
    function old(string $key, string $default = ''): string
    {
        $value = $GLOBALS['old'][$key] ?? $default;
        return is_scalar($value) ? (string) $value : $default;
    }
}
