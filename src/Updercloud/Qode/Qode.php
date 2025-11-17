<?php

namespace Undercloud\Qode;

class Qode
{
    private static function matrix(array $matrix)
    {
        return array_map(function ($l) {
            return str_split($l);
        }, $matrix);
    }

    public static function encode($text, $level = QR_ECLEVEL_L)
    {
        $enc = QRencode::factory($level);

        return self::matrix($enc->encode($text));
    }

    public static function encodeRaw($text, $level = QR_ECLEVEL_L)
    {
        $enc = QRencode::factory($level);

        return self::matrix($enc->encodeRAW($text));
    }

    public static function image($text, $level = QR_ECLEVEL_L, $isRaw = false)
    {
        return new Undercloud\Qode\QRImage(
            (
                $isRaw
                ? self::encode($text, $level)
                : self::encodeRaw($text, $level)
            ),
            $level
        );
    }
}
