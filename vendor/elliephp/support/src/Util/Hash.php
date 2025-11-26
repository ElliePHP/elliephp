<?php

namespace ElliePHP\Components\Support\Util;

use Random\RandomException;

final class Hash
{

    /**
     * Hash a value using bcrypt.
     *
     * @param string $value The value to hash.
     * @param array $options Hashing options.
     *
     * @return string The hashed value.
     */
    public static function create(string $value, array $options = []): string
    {
        $cost = $options['rounds'] ?? 12;

        return password_hash($value, PASSWORD_BCRYPT, [
            'cost' => $cost,
        ]);
    }

    /**
     * Verify a value against a hash.
     *
     * @param string $value The plain value.
     * @param string $hash The hashed value.
     *
     * @return bool True if matches, false otherwise.
     */
    public static function check(string $value, string $hash): bool
    {
        return password_verify($value, $hash);
    }

    /**
     * Check if a hash needs to be rehashed.
     *
     * @param string $hash The hash to check.
     * @param array $options Hashing options.
     *
     * @return bool True if it needs rehash, false otherwise.
     */
    public static function needsRehash(string $hash, array $options = []): bool
    {
        $cost = $options['rounds'] ?? 12;

        return password_needs_rehash($hash, PASSWORD_BCRYPT, [
            'cost' => $cost,
        ]);
    }

    /**
     * Get information about a hash.
     *
     * @param string $hash The hash.
     *
     * @return array Hash information.
     */
    public static function info(string $hash): array
    {
        return password_get_info($hash);
    }

    /**
     * Generate a hash using the specified algorithm.
     *
     * @param string $value The value to hash.
     * @param string $algorithm The hash algorithm.
     * @param bool $binary Return raw binary data.
     *
     * @return string The hash.
     */
    public static function hash(string $value, string $algorithm = 'sha256', bool $binary = false): string
    {
        return hash($algorithm, $value, $binary);
    }

    /**
     * Generate an HMAC hash.
     *
     * @param string $value The value to hash.
     * @param string $key The secret key.
     * @param string $algorithm The hash algorithm.
     * @param bool $binary Return raw binary data.
     *
     * @return string The HMAC hash.
     */
    public static function hmac(string $value, string $key, string $algorithm = 'sha256', bool $binary = false): string
    {
        return hash_hmac($algorithm, $value, $key, $binary);
    }

    /**
     * Generate a secure random hash.
     *
     * @param int $length The length of the hash.
     *
     * @return string The random hash.
     * @throws RandomException
     */
    public static function random(int $length = 32): string
    {
        $bytes = (int)ceil($length / 2);
        return bin2hex(random_bytes($bytes));
    }


    /**
     * Generate an MD5 hash.
     *
     * @param string $value The value to hash.
     *
     * @return string The MD5 hash.
     */
    public static function md5(string $value): string
    {
        return md5($value);
    }

    /**
     * Generate a SHA1 hash.
     *
     * @param string $value The value to hash.
     *
     * @return string The SHA1 hash.
     */
    public static function sha1(string $value): string
    {
        return sha1($value);
    }


    /**
     * Generate a XXH3 hash.
     *
     * @param string $value The value to hash.
     *
     * @return string The XXH3 hash.
     */
    public static function xxh3(string $value): string
    {
        return hash('xxh3', $value);
    }

    /**
     * Generate a SHA256 hash.
     *
     * @param string $value The value to hash.
     *
     * @return string The SHA256 hash.
     */
    public static function sha256(string $value): string
    {
        return hash('sha256', $value);
    }

    /**
     * Generate a SHA512 hash.
     *
     * @param string $value The value to hash.
     *
     * @return string The SHA512 hash.
     */
    public static function sha512(string $value): string
    {
        return hash('sha512', $value);
    }

    /**
     * Generate a hash of a file.
     *
     * @param string $path The file path.
     * @param string $algorithm The hash algorithm.
     * @param bool $binary Return raw binary data.
     *
     * @return string|false The file hash or false on failure.
     */
    public static function file(string $path, string $algorithm = 'sha256', bool $binary = false): string|false
    {
        return hash_file($algorithm, $path, $binary);
    }

    /**
     * Compare two strings in constant time.
     *
     * @param string $known The known string.
     * @param string $user The user-supplied string.
     *
     * @return bool True if equal, false otherwise.
     */
    public static function equals(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }

    /**
     * Generate a UUID v4.
     *
     * @return string The UUID.
     * @throws RandomException
     */
    public static function uuid(): string
    {
        $data = random_bytes(16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Generate a ULID (Universally Unique Lexicographically Sortable Identifier).
     *
     * @return string The ULID.
     * @throws RandomException
     */
    public static function ulid(): string
    {
        $time = (int)(microtime(true) * 1000);
        $timeChars = '';

        $encoding = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

        for ($i = 9; $i >= 0; $i--) {
            $mod = $time % 32;
            $timeChars = $encoding[$mod] . $timeChars;
            $time = ($time - $mod) / 32;
        }

        $randomChars = '';
        $randomBytes = random_bytes(10);

        for ($i = 0; $i < 16; $i++) {
            $randomChars .= $encoding[ord($randomBytes[$i % 10]) % 32];
        }

        return $timeChars . $randomChars;
    }

    /**
     * Generate a nanoid.
     *
     * @param int $size The size of the ID.
     * @param string|null $alphabet Custom alphabet.
     *
     * @return string The nanoid.
     * @throws RandomException
     */
    public static function nanoid(int $size = 21, ?string $alphabet = null): string
    {
        $alphabet = $alphabet ?? '_-0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $alphabetLength = strlen($alphabet);
        $id = '';

        $bytes = random_bytes($size);

        for ($i = 0; $i < $size; $i++) {
            $id .= $alphabet[ord($bytes[$i]) % $alphabetLength];
        }

        return $id;
    }

    /**
     * Generate a CRC32 hash.
     *
     * @param string $value The value to hash.
     *
     * @return string The CRC32 hash.
     */
    public static function crc32(string $value): string
    {
        return hash('crc32', $value);
    }

    /**
     * Generate a base64 encoded hash.
     *
     * @param string $value The value to hash.
     * @param string $algorithm The hash algorithm.
     *
     * @return string The base64 encoded hash.
     */
    public static function base64(string $value, string $algorithm = 'sha256'): string
    {
        return base64_encode(hash($algorithm, $value, true));
    }

    /**
     * Generate a URL-safe base64 encoded hash.
     *
     * @param string $value The value to hash.
     * @param string $algorithm The hash algorithm.
     *
     * @return string The URL-safe base64 encoded hash.
     */
    public static function base64Url(string $value, string $algorithm = 'sha256'): string
    {
        return rtrim(strtr(self::base64($value, $algorithm), '+/', '-_'), '=');
    }

    /**
     * Generate a hash using Argon2i.
     *
     * @param string $value The value to hash.
     * @param array $options Hashing options.
     *
     * @return string The hashed value.
     */
    public static function argon2i(string $value, array $options = []): string
    {
        return password_hash($value, PASSWORD_ARGON2I, $options);
    }

    /**
     * Generate a hash using Argon2id.
     *
     * @param string $value The value to hash.
     * @param array $options Hashing options.
     *
     * @return string The hashed value.
     */
    public static function argon2id(string $value, array $options = []): string
    {
        return password_hash($value, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Generate a checksum for data integrity.
     *
     * @param string $value The value.
     * @param string $algorithm The hash algorithm.
     *
     * @return string The checksum.
     */
    public static function checksum(string $value, string $algorithm = 'sha256'): string
    {
        return hash($algorithm, $value);
    }

    /**
     * Verify a checksum.
     *
     * @param string $value The value.
     * @param string $checksum The checksum to verify against.
     * @param string $algorithm The hash algorithm.
     *
     * @return bool True if valid, false otherwise.
     */
    public static function verifyChecksum(string $value, string $checksum, string $algorithm = 'sha256'): bool
    {
        return hash_equals($checksum, self::checksum($value, $algorithm));
    }

    /**
     * Get available hash algorithms.
     *
     * @return array List of available algorithms.
     */
    public static function algorithms(): array
    {
        return hash_algos();
    }

    /**
     * Generate a short hash (for URLs, etc.)
     *
     * @param string $value The value to hash.
     * @param int $length The desired length.
     *
     * @return string The short hash.
     */
    public static function short(string $value, int $length = 8): string
    {
        return substr(hash('sha256', $value), 0, $length);
    }

    /**
     * Generate a hash with salt.
     *
     * @param string $value The value to hash.
     * @param string $salt The salt.
     * @param string $algorithm The hash algorithm.
     *
     * @return string The salted hash.
     */
    public static function salted(string $value, string $salt, string $algorithm = 'sha256'): string
    {
        return hash($algorithm, $salt . $value);
    }

}
