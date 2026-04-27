<?php

namespace Undercloud\Qode;

class Qode
{
    private static function matrix(array $matrix): array
    {
        return array_map(function ($l) {
            return str_split($l);
        }, $matrix);
    }

    public static function encode(string $text, int $level = QR_ECLEVEL_L): array
    {
        $enc = QREncode::factory($level);

        return self::matrix($enc->encode($text));
    }

    public static function encodeRaw(string $text, int $level = QR_ECLEVEL_L): array
    {
        $enc = QREncode::factory($level);

        return self::matrix($enc->encodeRAW($text));
    }

    public static function image(string $text, int $level = QR_ECLEVEL_L, bool $isRaw = false): QRImage
    {
        return new QRImage(
            (
                $isRaw
                ? self::encode($text, $level)
                : self::encodeRaw($text, $level)
            ),
            $level
        );
    }
}
