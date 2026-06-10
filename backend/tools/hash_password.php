<?php
/**
 * Small CLI helper — prints a bcrypt hash of the password you pass in.
 *
 * Usage:
 *   php tools/hash_password.php Admin@1234
 *
 * Paste the output into seed.sql or use it when manually inserting a user.
 */

declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php tools/hash_password.php <password>\n");
    exit(1);
}

echo password_hash($argv[1], PASSWORD_BCRYPT) . PHP_EOL;
