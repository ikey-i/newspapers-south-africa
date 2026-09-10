<?php

declare(strict_types=1);

namespace App\Controllers\Publish;

use App\Models\Media;
use App\Support\Csrf;
use App\Support\PublisherAuth;
use App\Support\Upload;

/**
 * Inline-image uploads from the article editor. Returns JSON: {url} or {error}.
 */
final class MediaController
{
    public function store(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $user = PublisherAuth::user();
        if ($user === null || empty($user['email_verified_at']) || $user['newspaper_status'] !== 'active') {
            http_response_code(403);
            echo json_encode(['error' => 'Not allowed.']);
            return;
        }

        if (!Csrf::check($_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null))) {
            http_response_code(400);
            echo json_encode(['error' => 'Your session expired. Reload the page and try again.']);
            return;
        }

        [$path, $error] = Upload::image($_FILES['file'] ?? null, 'media', (string) $user['newspaper_slug']);
        if ($error !== null || $path === null) {
            http_response_code(422);
            echo json_encode(['error' => $error ?? 'No file received.']);
            return;
        }

        $bytes = (int) ($_FILES['file']['size'] ?? 0);
        Media::record((int) $user['newspaper_id'], $path, $bytes);

        echo json_encode(['url' => url($path)]);
    }
}
