<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Validated file uploads: images (logos, hero images, edition covers) and the
 * full-edition PDFs. Everything lands under /uploads, which has PHP execution
 * disabled by its own .htaccess.
 */
final class Upload
{
    private const IMAGE_MAX_BYTES = 3 * 1024 * 1024;  // 3 MB
    private const PDF_MAX_BYTES   = 25 * 1024 * 1024;  // 25 MB

    /** MIME type => file extension. */
    private const IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    /** Sub-directories of /uploads we are allowed to write to / delete from. */
    private const DIRS = ['logos', 'heroes', 'covers', 'media', 'pdfs'];

    public static function dir(string $kind): string
    {
        if (!in_array($kind, self::DIRS, true)) {
            throw new \InvalidArgumentException("Unknown upload kind: {$kind}");
        }
        return BASE_PATH . '/uploads/' . $kind;
    }

    /**
     * Validate and store an uploaded image.
     *
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int}|null $file
     * @param string $kind  logos|heroes|covers|media
     * @param string $prefix  filename stem (usually a slug)
     * @return array{0: string|null, 1: string|null}  [relativePath, error]
     */
    public static function image(?array $file, string $kind, string $prefix): array
    {
        [$tmp, $err] = self::incoming($file, self::IMAGE_MAX_BYTES, 'The image must be 3 MB or smaller.');
        if ($err !== null || $tmp === null) {
            return [null, $err];
        }

        $info = @getimagesize($tmp);
        $mime = is_array($info) ? ($info['mime'] ?? '') : '';
        if (!isset(self::IMAGE_TYPES[$mime])) {
            return [null, 'The image must be a JPG, PNG, WebP or GIF.'];
        }

        return self::store($tmp, $kind, $prefix, self::IMAGE_TYPES[$mime]);
    }

    /**
     * Validate and store an uploaded PDF (magic-byte checked).
     *
     * @param array{name?:string,type?:string,tmp_name?:string,error?:int,size?:int}|null $file
     * @return array{0: string|null, 1: string|null, 2: int}  [relativePath, error, bytes]
     */
    public static function pdf(?array $file, string $prefix): array
    {
        [$tmp, $err] = self::incoming($file, self::PDF_MAX_BYTES, 'The PDF must be 25 MB or smaller.');
        if ($err !== null || $tmp === null) {
            return [null, $err, 0];
        }

        $head = (string) @file_get_contents($tmp, false, null, 0, 5);
        if (!str_starts_with($head, '%PDF-')) {
            return [null, 'That file is not a valid PDF.', 0];
        }

        $bytes = (int) @filesize($tmp);
        [$path, $storeErr] = self::store($tmp, 'pdfs', $prefix, 'pdf');
        return [$path, $storeErr, $storeErr === null ? $bytes : 0];
    }

    /**
     * Delete a previously stored upload (relative path). No-op for empty or
     * foreign paths.
     */
    public static function delete(?string $relativePath): void
    {
        $relativePath = trim((string) $relativePath);
        if ($relativePath === '' || !str_starts_with($relativePath, 'uploads/')) {
            return;
        }
        $kind = explode('/', $relativePath)[1] ?? '';
        if (!in_array($kind, self::DIRS, true)) {
            return;
        }

        // All current callers only ever pass a path this class generated
        // itself (read back from the database), so there is nothing to
        // traverse with today — this is defense-in-depth in case that ever
        // changes: refuse to unlink anything outside the matching upload dir.
        $dir = realpath(self::dir($kind));
        $full = realpath(BASE_PATH . '/' . $relativePath);
        if ($dir === false || $full === false || !str_starts_with($full, $dir . DIRECTORY_SEPARATOR)) {
            return;
        }

        @unlink($full);
    }

    // -----------------------------------------------------------------------

    /**
     * @param array{error?:int,tmp_name?:string,size?:int}|null $file
     * @return array{0: string|null, 1: string|null}  [tmpPath, error]  (both null = nothing uploaded)
     */
    private static function incoming(?array $file, int $maxBytes, string $tooBigMessage): array
    {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }
        if (($file['error'] ?? 1) !== UPLOAD_ERR_OK) {
            return [null, 'The file failed to upload. Please try again.'];
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return [null, 'The upload could not be read.'];
        }
        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            return [null, $tooBigMessage];
        }
        return [$tmp, null];
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private static function store(string $tmp, string $kind, string $prefix, string $ext): array
    {
        $stem = preg_replace('/[^a-z0-9-]+/i', '-', trim($prefix, '-')) ?: 'file';
        $name = mb_substr($stem, 0, 60) . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dir = self::dir($kind);

        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            return [null, 'The server could not save the file.'];
        }
        if (!@move_uploaded_file($tmp, $dir . '/' . $name)) {
            return [null, 'The server could not save the file.'];
        }
        return ['uploads/' . $kind . '/' . $name, null];
    }
}
