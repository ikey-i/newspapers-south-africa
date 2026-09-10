<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

final class Media
{
    public static function record(int $newspaperId, string $path, int $bytes): int
    {
        Database::execute(
            'INSERT INTO media (newspaper_id, path, bytes) VALUES (?, ?, ?)',
            [$newspaperId, $path, $bytes]
        );
        return (int) Database::connection()->lastInsertId();
    }
}
