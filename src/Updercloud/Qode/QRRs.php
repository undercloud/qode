<?php

namespace Undercloud\Qode;

class QRrs
{
    public static $items = array();

    public static function initRs($symsize, $gfpoly, $fcr, $prim, $nroots, $pad)
    {
        foreach (self::$items as $rs) {
            if ($rs->pad != $pad) {
                continue;
            }

            if ($rs->nroots != $nroots) {
                continue;
            }

            if ($rs->mm != $symsize) {
                continue;
            }

            if ($rs->gfpoly != $gfpoly) {
                continue;
            }

            if ($rs->fcr != $fcr) {
                continue;
            }

            if ($rs->prim != $prim) {
                continue;
            }

            return $rs;
        }

        $rs = QRrsItem::initRsChar($symsize, $gfpoly, $fcr, $prim, $nroots, $pad);
        array_unshift(self::$items, $rs);

        return $rs;
    }
}
