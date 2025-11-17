<?php

namespace Undercloud\Qode;

class QRencode
{
    public $casesensitive = true;
    public $eightbit = false;

    public $version = 0;

    public $level = QR_ECLEVEL_L;
    public $hint = QR_MODE_8;

    public static function factory($level = QR_ECLEVEL_L)
    {
        $enc = new QRencode();
        switch ($level . '') {
            default:
            case QR_ECLEVEL_L:
                    $enc->level = QR_ECLEVEL_L;
                break;
            case QR_ECLEVEL_M:
                    $enc->level = QR_ECLEVEL_M;
                break;
            case QR_ECLEVEL_Q:
                    $enc->level = QR_ECLEVEL_Q;
                break;
            case QR_ECLEVEL_H:
                    $enc->level = QR_ECLEVEL_H;
                break;
        }

        return $enc;
    }

    public function encodeRAW($intext)
    {
        $code = new QRcode();

        if ($this->eightbit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString($intext, $this->version, $this->level, $this->hint, $this->casesensitive);
        }

        return $code->data;
    }

    public function encode($intext)
    {
        $code = new QRcode();

        if ($this->eightbit) {
            $code->encodeString8bit($intext, $this->version, $this->level);
        } else {
            $code->encodeString($intext, $this->version, $this->level, $this->hint, $this->casesensitive);
        }

        return QRTools::binarize($code->data);
    }
}
