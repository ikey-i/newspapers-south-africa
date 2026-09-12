<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;

/**
 * Publisher (newsroom) accounts.
 */
final class Publisher
{
    /** @return array<string,mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        return Database::first(
            'SELECT * FROM publishers WHERE email = ?',
            [mb_strtolower(trim($email))]
        );
    }

    /** @return array<string,mixed>|null */
    public static function findByVerifyToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        return Database::first(
            'SELECT p.*, n.name AS newspaper_name, n.slug AS newspaper_slug, n.status AS newspaper_status
             FROM publishers p JOIN newspapers n ON n.id = p.newspaper_id
             WHERE p.verify_token = ?',
            [$token]
        );
    }

    public static function emailTaken(string $email): bool
    {
        return self::findByEmail($email) !== null;
    }

    /**
     * Create a pending newspaper and its owner account together.
     *
     * @param array{name:string,type:string,province:?string,city:?string,website:?string} $paper
     * @param array{name:string,email:string,password:string} $owner
     * @return array{0:int,1:int,2:string}  [newspaperId, publisherId, verifyToken]
     */
    public static function register(array $paper, array $owner): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $newspaperId = Newspaper::createPending($paper);

            $token = bin2hex(random_bytes(32));
            $pdo->prepare(
                'INSERT INTO publishers
                    (newspaper_id, name, email, password_hash, role, verify_token, verify_sent_at)
                 VALUES (?, ?, ?, ?, \'owner\', ?, NOW())'
            )->execute([
                $newspaperId,
                mb_substr(trim($owner['name']), 0, 120),
                mb_strtolower(trim($owner['email'])),
                password_hash($owner['password'], PASSWORD_DEFAULT),
                $token,
            ]);
            $publisherId = (int) $pdo->lastInsertId();

            $pdo->commit();
            return [$newspaperId, $publisherId, $token];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function markVerified(int $publisherId): void
    {
        Database::execute(
            'UPDATE publishers SET email_verified_at = NOW(), verify_token = NULL WHERE id = ?',
            [$publisherId]
        );
    }

    public static function refreshVerifyToken(int $publisherId): string
    {
        $token = bin2hex(random_bytes(32));
        Database::execute(
            'UPDATE publishers SET verify_token = ?, verify_sent_at = NOW() WHERE id = ?',
            [$token, $publisherId]
        );
        return $token;
    }

    public static function setPassword(int $publisherId, string $password): void
    {
        Database::execute(
            'UPDATE publishers SET password_hash = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $publisherId]
        );
    }
}
