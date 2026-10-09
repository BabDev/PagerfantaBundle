<?php declare(strict_types=1);

namespace BabDev\PagerfantaBundle\Cursor;

use Pagerfanta\Cursor\Cursor;
use Pagerfanta\Cursor\CursorEncoderInterface;
use Pagerfanta\Exception\InvalidArgumentException;
use Pagerfanta\Exception\InvalidCursorException;

/**
 * Cursor encoder which signs the encoded cursors of another encoder, preventing clients from tampering with them.
 *
 * The signature is a HMAC-SHA256 hash of the encoded cursor, appended to it after a ".". The signature is verified
 * before the cursor is decoded, so a tampered cursor never reaches the decorated encoder.
 */
final readonly class SignedCursorEncoder implements CursorEncoderInterface
{
    private const string SEPARATOR = '.';

    /**
     * Separates the signatures of cursors from other signatures made with the same secret.
     */
    private const string CONTEXT = 'babdev_pagerfanta.cursor';

    /**
     * @throws InvalidArgumentException if the secret is empty
     */
    public function __construct(
        private CursorEncoderInterface $encoder,
        #[\SensitiveParameter]
        private string $secret,
    ) {
        if ('' === $secret) {
            throw new InvalidArgumentException('The secret for signing cursors must not be empty.');
        }
    }

    public function encode(Cursor $cursor): string
    {
        $payload = $this->encoder->encode($cursor);

        return $payload.self::SEPARATOR.$this->sign($payload);
    }

    public function decode(string $encoded): Cursor
    {
        // The signature never contains the separator, so the payload may contain it
        $position = strrpos($encoded, self::SEPARATOR);

        if (false === $position) {
            throw new InvalidCursorException('The cursor is not signed.');
        }

        $payload = substr($encoded, 0, $position);
        $signature = substr($encoded, $position + 1);

        if (!hash_equals($this->sign($payload), $signature)) {
            throw new InvalidCursorException('The cursor signature is not valid.');
        }

        return $this->encoder->decode($payload);
    }

    private function sign(string $payload): string
    {
        return rtrim(strtr(base64_encode(hash_hmac('sha256', self::CONTEXT.'|'.$payload, $this->secret, true)), '+/', '-_'), '=');
    }
}
