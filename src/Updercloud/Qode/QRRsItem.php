<?php

namespace Undercloud\Qode;

class QRrsItem
{
    public $mm;
    public $nn;
    public $alphaTo = array();
    public $indexOf = array();
    public $ganpoly = array();
    public $nroots;
    public $fcr;
    public $prim;
    public $iprim;
    public $pad;
    public $gfpoly;

    public function modnn($x)
    {
        while ($x >= $this->nn) {
            $x -= $this->nn;
            $x = ($x >> $this->mm) + ($x & $this->nn);
        }

        return $x;
    }

    public static function initRsChar($symsize, $gfpoly, $fcr, $prim, $nroots, $pad)
    {
        $rs = null;

        if ($symsize < 0 || $symsize > 8) {
            return $rs;
        }

        if ($fcr < 0 || $fcr >= (1 << $symsize)) {
            return $rs;
        }

        if ($prim <= 0 || $prim >= (1 << $symsize)) {
            return $rs;
        }

        if ($nroots < 0 || $nroots >= (1 << $symsize)) {
            return $rs;
        }

        if ($pad < 0 || $pad >= ((1 << $symsize) - 1 - $nroots)) {
            return $rs;
        }

        $rs = new QRrsItem();
        $rs->mm = $symsize;
        $rs->nn = (1 << $symsize) - 1;
        $rs->pad = $pad;

        $rs->alphaTo = array_fill(0, $rs->nn + 1, 0);
        $rs->indexOf = array_fill(0, $rs->nn + 1, 0);

        $nn = &$rs->nn;
        $a0 = &$nn;

        $rs->indexOf[0] = $a0;
        $rs->alphaTo[$a0] = 0;
        $sr = 1;

        for ($i = 0; $i < $rs->nn; $i++) {
            $rs->indexOf[$sr] = $i;
            $rs->alphaTo[$i] = $sr;
            $sr <<= 1;

            if ($sr & (1 << $symsize)) {
                $sr ^= $gfpoly;
            }

            $sr &= $rs->nn;
        }

        if ($sr != 1) {
            $rs = null;

            return $rs;
        }

        $rs->genpoly = array_fill(0, $nroots + 1, 0);

        $rs->fcr = $fcr;
        $rs->prim = $prim;
        $rs->nroots = $nroots;
        $rs->gfpoly = $gfpoly;

        for ($iprim = 1; ($iprim % $prim) != 0; $iprim += $rs->nn);

        $rs->iprim = (int)($iprim / $prim);
        $rs->genpoly[0] = 1;

        for ($i = 0,$root = $fcr * $prim; $i < $nroots; $i++, $root += $prim) {
            $rs->genpoly[$i + 1] = 1;

            for ($j = $i; $j > 0; $j--) {
                if ($rs->genpoly[$j] != 0) {
                    $rs->genpoly[$j] = (
                        $rs->genpoly[$j - 1]
                        ^
                        $rs->alphaTo[$rs->modnn($rs->indexOf[$rs->genpoly[$j]] + $root)]
                    );
                } else {
                    $rs->genpoly[$j] = $rs->genpoly[$j - 1];
                }
            }

            $rs->genpoly[0] = $rs->alphaTo[$rs->modnn($rs->indexOf[$rs->genpoly[0]] + $root)];
        }

        for ($i = 0; $i <= $nroots; $i++) {
            $rs->genpoly[$i] = $rs->indexOf[$rs->genpoly[$i]];
        }

        return $rs;
    }

    public function encodeRsChar($data, &$parity)
    {
        $nn       = &$this->nn;
        $alphaTo = &$this->alphaTo;
        $indexOf = &$this->indexOf;
        $ganpoly  = &$this->genpoly;
        $nroots   = &$this->nroots;
        $pad      = &$this->pad;
        $a0       = &$nn;

        $parity = array_fill(0, $nroots, 0);

        for ($i = 0; $i < ($nn - $nroots - $pad); $i++) {
            $feedback = $indexOf[$data[$i] ^ $parity[0]];
            if ($feedback != $a0) {
                $feedback = $this->modnn($nn - $ganpoly[$nroots] + $feedback);

                for ($j = 1; $j < $nroots; $j++) {
                    $parity[$j] ^= $alphaTo[$this->modnn($feedback + $ganpoly[$nroots - $j])];
                }
            }

            array_shift($parity);
            if ($feedback != $a0) {
                array_push($parity, $alphaTo[$this->modnn($feedback + $ganpoly[0])]);
            } else {
                array_push($parity, 0);
            }
        }
    }
}
