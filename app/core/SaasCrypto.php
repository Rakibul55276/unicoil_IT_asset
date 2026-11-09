<?php

/**
 * Encrypts tenant database passwords before they are stored in the platform
 * database. The encryption key remains in the installation's private config.
 */
class SaasCrypto
{
    private const CIPHER = "aes-256-gcm";
    private const PREFIX = "v1:";

    public static function encrypt(string $plainText, string $secret): string
    {
        if ($plainText === "") {
            return "";
        }
        self::assertAvailable($secret);

        $iv = random_bytes(12);
        $tag = "";
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            hash("sha256", $secret, true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );
        if ($cipherText === false) {
            throw new RuntimeException("Unable to encrypt the tenant database password.");
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipherText);
    }

    public static function decrypt(string $encrypted, string $secret): string
    {
        if ($encrypted === "") {
            return "";
        }
        self::assertAvailable($secret);
        if (strpos($encrypted, self::PREFIX) !== 0) {
            throw new RuntimeException("The stored tenant password is not encrypted.");
        }

        $payload = base64_decode(substr($encrypted, strlen(self::PREFIX)), true);
        if ($payload === false || strlen($payload) < 29) {
            throw new RuntimeException("The stored tenant password is invalid.");
        }

        $iv = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $cipherText = substr($payload, 28);
        $plainText = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            hash("sha256", $secret, true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
        );
        if ($plainText === false) {
            throw new RuntimeException("Unable to decrypt the tenant database password.");
        }

        return $plainText;
    }

    private static function assertAvailable(string $secret): void
    {
        if (!function_exists("openssl_encrypt")) {
            throw new RuntimeException("OpenSSL is required for SaaS database credentials.");
        }
        if (strlen($secret) < 32) {
            throw new RuntimeException(
                "Configure saas_encryption_key with at least 32 characters.",
            );
        }
    }
}
