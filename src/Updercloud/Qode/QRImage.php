<?php

namespace Undercloud\Qode;

class QRImage
{
    private $style = QR_STYLE_CIRCLE;
    private $keyStyle = QR_STYLE_CIRCLE;
    private $frame = array();
    private $width = 0;
    private $height = 0;
    private $level;

    private $primaryColor = '#000';
    private $backgroundColor = '#fff';

    private $useGradient = false;
    private $useRadialGradient = false;
    private $gradientColorFrom = '#2980b9';
    private $gradientColorTo = '#8e44ad';
    private $gradientRotateAngle = 45;

    private $pixelPerPoint = 8;
    private $outerFrame = 4;

    private $useCleanCenter = true;
    private $useCenterImage = false;//'https://cdn-icons-png.flaticon.com/128/272/272629.png';

    public function __construct(array $frame, $level = QR_ECLEVEL_L)
    {
        $this->frame = $frame;
        $this->height = count($frame);
        $this->width = count($frame[0]);
        $this->level = $level;
    }

    public function setPrimaryColor($primaryColor)
    {
        $this->primaryColor = $primaryColor;

        return $this;
    }

    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    public function setGradient(bool $flag)
    {
        if ($this->useGradient != $flag) {
            list($this->primaryColor, $this->backgroundColor) = [$this->backgroundColor, $this->primaryColor];
        }

        $this->useGradient = $flag;

        return $this;
    }

    public function setRadialGradient(bool $flag)
    {
        $this->useRadialGradient = $flag;

        return $this;
    }

    public function setGradientValue($gradientColorFrom, $gradientColorTo)
    {
        $this->gradientColorFrom = $gradientColorFrom;
        $this->gradientColorTo   = $gradientColorTo;

        return $this;
    }

    public function setGradientAngle(int $gradientRotateAngle)
    {
        $this->gradientRotateAngle = $gradientRotateAngle;

        return $this;
    }

    private function getQrLogoSafeArea()
    {
        $eccFactors = [
            QR_ECLEVEL_L => 0.18,
            QR_ECLEVEL_M => 0.22,
            QR_ECLEVEL_Q => 0.28,
            QR_ECLEVEL_H => 0.32
        ];

        if (!isset($eccFactors[$this->level])) {
            return false;
        }

        $logoSizeFactor = $eccFactors[$this->level];

        $minFixedPatternPffset = 12;

        if ($this->width < 2 * $minFixedPatternPffset || $this->height < 2 * $minFixedPatternPffset) {
            return false;
        }

        $availableCenterWidth = $this->width - (2 * $minFixedPatternPffset);
        $availableCenterHeight = $this->height - (2 * $minFixedPatternPffset);

        if ($availableCenterWidth <= 0 || $availableCenterHeight <= 0) {
            return false;
        }

        $desiredLogoSide = floor($logoSizeFactor * min($this->width, $this->height));

        $logoSideModules = min($desiredLogoSide, $availableCenterWidth, $availableCenterHeight);

        if ($logoSideModules < 1) {
            return false;
        }

        $xMin = floor(($this->width - $logoSideModules) / 2);
        $yMin = floor(($this->height - $logoSideModules) / 2);

        $xMax = $xMin + $logoSideModules - 1;
        $yMax = $yMin + $logoSideModules - 1;

        if (
            $xMin < $minFixedPatternPffset ||
            $yMin < $minFixedPatternPffset ||
            $xMax >= $this->width - $minFixedPatternPffset ||
            $yMax >= $this->height - $minFixedPatternPffset
        ) {
            $xMin = $minFixedPatternPffset;
            $yMin = $minFixedPatternPffset;
            $xMax = $this->width - $minFixedPatternPffset - 1;
            $yMax = $this->height - $minFixedPatternPffset - 1;

            if ($xMin > $xMax || $yMin > $yMax) {
                 return false;
            }

            $logoSideModules = min($xMax - $xMin + 1, $yMax - $yMin + 1);
            $xMin = floor(($this->width - $logoSideModules) / 2);
            $yMin = floor(($this->height - $logoSideModules) / 2);
            $xMax = $xMin + $logoSideModules - 1;
            $yMax = $yMin + $logoSideModules - 1;
        }

        return [
            'xMin' => $xMin,
            'yMin' => $yMin,
            'xMax' => $xMax,
            'yMax' => $yMax,
            'width' => $logoSideModules,
            'height' => $logoSideModules,
        ];
    }

    public static function getAnglesKind(array $frame)
    {
        $angles = array();
        foreach ($frame as $line => $columns) {
            foreach ($columns as $column => $cell) {
                if ($cell == QR_VALUE_FILL) {
                    $angles[$line][$column] = array();

                    if (0 == $frame[$line - 1][$column] and 0 == $frame[$line][$column - 1]) {
                        $angles[$line][$column][] = 'top-left';
                    }

                    if (0 == $frame[$line - 1][$column] and 0 == $frame[$line][$column + 1]) {
                        $angles[$line][$column][] = 'top-right';
                    }

                    if (0 == $frame[$line][$column + 1] and 0 == $frame[$line + 1][$column]) {
                        $angles[$line][$column][] = 'bottom-right';
                    }

                    if (0 == $frame[$line][$column - 1] and 0 == $frame[$line + 1][$column]) {
                        $angles[$line][$column][] = 'bottom-left';
                    }
                }
            }
        }

        return $angles;
    }

    public function generateRoundedRectSvg($x, $y, array $radius = [])
    {
        $radius = array_fill_keys($radius, $this->pixelPerPoint / 2);

        $rx = $radius['top-left'] ? : 0;
        $ry = $radius['top-left'] ? : 0;

        $topRightRx = $radius['top-right'] ? : 0;
        $topRightRy = $radius['top-right'] ? : 0;

        $bottomRightRx = $radius['bottom-right'] ? : 0;
        $bottomRightRy = $radius['bottom-right'] ? : 0;

        $bottomLeftRx = $radius['bottom-left'] ? : 0;
        $bottomLeftRy = $radius['bottom-left'] ? : 0;

        $path = "M {$x} {$y}";

        $path .= " L " . ($x + $this->pixelPerPoint - $topRightRx) . " {$y}";
        if ($topRightRx > 0) {
            $path .= (
                " A {$topRightRx} {$topRightRy} 0 0 1 " .
                ($x + $this->pixelPerPoint) .
                " " .
                ($y + $this->pixelPerPoint / 2)
            );
        }

        $path .= " L " . ($x + $this->pixelPerPoint) . " " . ($y + $this->pixelPerPoint - $bottomRightRx);
        if ($bottomRightRx > 0) {
            $path .= (
                " A {$bottomRightRx} {$bottomRightRy} 0 0 1 " .
                ($x + $this->pixelPerPoint / 2) .
                " " .
                ($y + $this->pixelPerPoint)
            );
        }

        $path .= " L " . ($x + $bottomLeftRx) . " " . ($y + $this->pixelPerPoint);
        if ($bottomLeftRx > 0) {
            $path .= " A {$bottomLeftRx} {$bottomLeftRy} 0 0 1 {$x} " . ($y + $this->pixelPerPoint / 2);
        }

        $path .= " L {$x} " . ($y + $this->pixelPerPoint - $rx);
        if ($rx > 0) {
            $path .= " A {$rx} {$ry} 0 0 1 " . ($x + $this->pixelPerPoint / 2) . " {$y}";
        }

        $path .= " Z";

        $svg = '<path d="' . $path . '" fill="' . $this->primaryColor . '" />';

        return $svg;
    }

    private function findKeyCornerIndex(array $frame)
    {
        foreach ($frame as $row) {
            foreach ($row as $index => $cell) {
                if (!$cell) {
                    break;
                }
            }
        }

        return $index;
    }

    private function findKeyCorners(array $frame)
    {
        $size   = count($frame);
        $index  = $this->findKeyCornerIndex($frame);
        $offset = ($size - $index);

        foreach ($frame as $x => &$row) {
            foreach ($row as $y => &$cell) {
                if (
                       ($x < $index and $y < $index)
                    or ($x >= $offset and $y < $index)
                    or ($x < $index and $y >= $offset)
                ) {
                    $cell = $cell ? 2 : 3;
                }
            }
        }

        return $frame;
    }

    private function resolveGradient()
    {
        $kindGradientTag = ($this->useRadialGradient ? 'radialGradient' : 'linearGradient');

        return (
            '<' . $kindGradientTag . ' ' .
                'gradientTransform="rotate(' . $this->gradientRotateAngle . ')" ' .
                'id="qr-gradient">' .
                '<stop ' .
                    'offset="5%" ' .
                    'stop-color="' . $this->gradientColorFrom . '"/>' .
                '<stop ' .
                    'offset="95%" ' .
                    'stop-color="' . $this->gradientColorTo . '"/>' .
            '</' . $kindGradientTag . '>'
        );
    }

    private function resolveRectCorners($startX, $startY, $eyeSize)
    {
        $roundedCorner = (
            ($this->keyStyle === QR_KEY_STYLE_ELEGANT)
            ? ($this->pixelPerPoint / 0.75)
            : 0
        );

        return (
            '<rect ' .
                'rx="' . ($roundedCorner) . '" ' .
                'ry="' . ($roundedCorner) . '" ' .
                'x="' . ($startX + $this->outerFrame) . '" ' .
                'y="' . ($startY + $this->outerFrame) . '" ' .
                'fill="' . $this->primaryColor . '" ' .
                'width="' . ($eyeSize * $this->pixelPerPoint) . '" ' .
                'height="' . ($eyeSize * $this->pixelPerPoint) . '"/>' .
            '<rect ' .
                'rx="' . ($roundedCorner) . '" ' .
                'ry="' . ($roundedCorner) . '" ' .
                'x="' . ($startX + (1 * $this->pixelPerPoint + $this->outerFrame)) . '" ' .
                'y="' . ($startY + (1 * $this->pixelPerPoint + $this->outerFrame)) . '" ' .
                'fill="' . $this->backgroundColor . '" ' .
                'width="' . (($eyeSize - 2) * $this->pixelPerPoint) . '" ' .
                'height="' . (($eyeSize - 2) * $this->pixelPerPoint) . '"/>' .
            '<rect ' .
                'rx="' . ($roundedCorner) . '" ' .
                'ry="' . ($roundedCorner) . '" ' .
                'x="' . ($startX + (2 * $this->pixelPerPoint + $this->outerFrame)) . '" ' .
                'y="' . ($startY + (2 * $this->pixelPerPoint + $this->outerFrame)) . '" ' .
                'fill="' . $this->primaryColor . '" ' .
                'width="' . (($eyeSize - 4) * $this->pixelPerPoint) . '" ' .
                'height="' . (($eyeSize - 4) * $this->pixelPerPoint) . '"/>'
        );
    }

    private function resolveCircleCorners($centerX, $centerY, $eyeSize)
    {
        return (
            '<circle ' .
                'cx="' . $centerX . '" ' .
                'cy="' . $centerY . '" ' .
                'r="' . (($eyeSize / 2) * $this->pixelPerPoint - ($this->pixelPerPoint / 2)) . '" ' .
                'stroke-width="' . $this->pixelPerPoint  . '" ' .
                'stroke="' . $this->primaryColor . '" ' .
                'fill="' . $this->primaryColor . '" />' .
            '<circle ' .
                'cx="' . $centerX . '" ' .
                'cy="' . $centerY . '" ' .
                'r="' . (($eyeSize / 2 - 1) * $this->pixelPerPoint) . '" ' .
                'stroke-width="' . ($this->pixelPerPoint / 2)  . '" ' .
                'stroke="' . $this->backgroundColor . '" ' .
                'fill="' . $this->backgroundColor . '" />' .
            '<circle ' .
                'cx="' . $centerX . '" ' .
                'cy="' . $centerY . '" ' .
                'r="' . (($eyeSize / 2 - 2) * $this->pixelPerPoint) . '" ' .
                'fill="' . $this->primaryColor . '"/>'
        );
    }

    private function resolveKeyCorners()
    {
        $eyeSize = $this->findKeyCornerIndex($this->frame);

        $subSvg = '';
        if ($this->keyStyle === QR_KEY_STYLE_CIRCLE) {
            $subSvg .= $this->resolveCircleCorners(
                (($eyeSize / 2 * $this->pixelPerPoint) + $this->outerFrame),
                (($eyeSize / 2 * $this->pixelPerPoint) + $this->outerFrame),
                $eyeSize
            );

            $subSvg .= $this->resolveCircleCorners(
                (((($eyeSize / 2) + $this->width - $eyeSize) * $this->pixelPerPoint) + $this->outerFrame),
                (($eyeSize / 2 * $this->pixelPerPoint) + $this->outerFrame),
                $eyeSize
            );

            $subSvg .= $this->resolveCircleCorners(
                (($eyeSize / 2 * $this->pixelPerPoint) + $this->outerFrame),
                (((($eyeSize / 2) + $this->width - $eyeSize) * $this->pixelPerPoint) + $this->outerFrame),
                $eyeSize
            );
        } else {
            $subSvg .= $this->resolveRectCorners(
                0,
                0,
                $eyeSize
            );

            $subSvg .= $this->resolveRectCorners(
                ($this->width - $eyeSize) * $this->pixelPerPoint,
                0,
                $eyeSize
            );

            $subSvg .= $this->resolveRectCorners(
                0,
                ($this->height - $eyeSize) * $this->pixelPerPoint,
                $eyeSize
            );
        }

        return $subSvg;
    }

    private function resolveCleanCenter(array $frame)
    {
        $clean = self::getQrLogoSafeArea();
        foreach ($frame as $line => $columns) {
            foreach ($columns as $column => $cell) {
                if ($column >= $clean['xMin'] and $column <= $clean['xMax']) {
                    if ($line >= $clean['yMin'] and $line <= $clean['yMax']) {
                        $frame[$line][$column] = 0;
                    }
                }
            }
        }

        return array($frame, $clean);
    }

    public function draw()
    {
        $frame = $this->findKeyCorners($this->frame);

        if ($this->useCenterImage) {
            $this->useCleanCenter = true;
        }

        if ($this->useCleanCenter) {
            list($frame, $clean) = $this->resolveCleanCenter($frame);
        }

        $this->height = count($frame);
        $this->width = count($frame[0]);

        $imageWidth = ($this->width * $this->pixelPerPoint) + (2 * $this->outerFrame);
        $imageHeight = ($this->height * $this->pixelPerPoint) + (2 * $this->outerFrame);

        $svg = (
            '<?xml version="1.0" encoding="UTF-8"?>' .
            '<svg ' .
                'version="1.1" ' .
                'xmlns="http://www.w3.org/2000/svg" ' .
                'xmlns:xlink="http://www.w3.org/1999/xlink" ' .
                'viewBox="0 0 ' . $imageWidth . ' ' . $imageHeight . '" ' .
                'width="' . $imageWidth . '" ' .
                'height="' . $imageHeight . '">'
        );

        if ($this->useGradient) {
            $svg .= (
                '<defs>' .
                    $this->resolveGradient() .
                    '<mask id="gmask">'
            );
        } else {
             $svg .= (
                '<rect ' .
                    'x="0" ' .
                    'y="0" ' .
                    'width="' . $imageWidth . '" ' .
                    'height="' . $imageHeight . '" ' .
                    'fill="' . $this->backgroundColor . '" />'
            );
        }

        $svg .= $this->resolveKeyCorners();

        if ($this->style === QR_STYLE_CIRCLE) {
            $radius = $this->pixelPerPoint / 2.5;
            $offset = $this->pixelPerPoint / 2;
        } elseif ($this->style === QR_STYLE_ELEGANT) {
            $angles = self::getAnglesKind($frame);
        }

        foreach ($frame as $line => $columns) {
            foreach ($columns as $column => $cell) {
                if ($cell == QR_KEY_ZERO or $cell == QR_KEY_FILL) {
                    continue;
                }

                $realX = $column  * $this->pixelPerPoint + $this->outerFrame;
                $realY = $line * $this->pixelPerPoint + $this->outerFrame;

                if ($cell == QR_VALUE_FILL) {
                    if ($this->style === QR_STYLE_CIRCLE) {
                        $svg .= (
                            '<circle ' .
                                'cx="' . ($realX + $offset) . '" ' .
                                'cy="' . ($realY + $offset) . '" ' .
                                'r="' . $radius . '" ' .
                                'fill="' . $this->primaryColor . '" />'
                        );
                    } elseif ($this->style === QR_STYLE_ELEGANT) {
                        $svg .= $this->generateRoundedRectSvg(
                            $realX,
                            $realY,
                            $angles[$line][$column]
                        );
                    } else {
                        $svg .= (
                            '<rect ' .
                                'x="' . $realX . '" ' .
                                'y="' . $realY . '" ' .
                                'fill="' . $this->primaryColor . '" ' .
                                'width="' . $this->pixelPerPoint . '" ' .
                                'height="' . $this->pixelPerPoint . '"/>'
                        );
                    }
                }
            }
        }

        if ($this->useGradient) {
            $svg .= '</mask>';
            $svg .= '</defs>';

            $svg .= (
                '<rect ' .
                    'x="0" ' .
                    'y="0" ' .
                    'width="' . $imageWidth . '" ' .
                    'height="' . $imageHeight . '" ' .
                    'fill="url(#qr-gradient)" ' .
                    'mask="url(#gmask)"/>'
            );
        }

        if ($this->useCenterImage) {
            if ($clean and $clean['width'] and $clean['height']) {
                $imageX = (($clean['xMin'] * $this->pixelPerPoint) + ($this->pixelPerPoint / 2) + $this->outerFrame);
                $imageY = (($clean['yMin'] * $this->pixelPerPoint) + ($this->pixelPerPoint / 2) + $this->outerFrame);

                $svg .= (
                    '<image ' .
                        'x="' . $imageX . '" ' .
                        'y="' . $imageY . '" ' .
                        'width="' . (($clean['width'] * $this->pixelPerPoint)  - ($this->pixelPerPoint)) . '" ' .
                        'height="' . (($clean['height'] * $this->pixelPerPoint) - ($this->pixelPerPoint)) . '" ' .
                        'href="' . $this->useCenterImage . '"/>'
                );
            }
        }

        $svg .= '</svg>';

        return $svg;
    }

    public function ascii($fill = '██', $empty = '  ', $corners = '██')
    {
        $frame = $this->findKeyCorners($this->frame);

        if ($this->useCleanCenter) {
            list($frame, $clean) = $this->resolveCleanCenter($frame);
        }

        return implode(PHP_EOL, array_map(
            function ($line) use ($fill, $empty, $corners) {
                return implode(array_map(
                    function ($cell) use ($fill, $empty, $corners) {
                        if ($cell == QR_KEY_FILL) {
                            return $corners;
                        } elseif ($cell == QR_VALUE_FILL) {
                            return $fill;
                        }

                        return $empty;
                    },
                    $line
                ));
            },
            $frame
        ));
    }

    public function __toString()
    {
        return $this->ascii();
    }
}
