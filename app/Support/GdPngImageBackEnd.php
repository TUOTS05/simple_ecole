<?php

namespace App\Support;

use BaconQrCode\Renderer\Color\ColorInterface;
use BaconQrCode\Renderer\Image\ImageBackEndInterface;
use BaconQrCode\Renderer\Path\Close;
use BaconQrCode\Renderer\Path\Curve;
use BaconQrCode\Renderer\Path\EllipticArc;
use BaconQrCode\Renderer\Path\Line;
use BaconQrCode\Renderer\Path\Move;
use BaconQrCode\Renderer\Path\Path;
use BaconQrCode\Renderer\RendererStyle\Gradient;

/**
 * Rendu QR en PNG via l'extension GD.
 *
 * BaconQrCode ne fournit nativement qu'un rendu SVG (sans dépendance) ou PNG via Imagick, or ce
 * serveur n'a que GD d'installé, et dompdf (utilisé pour générer les cartes en PDF) ne sait pas
 * afficher une balise <svg> inline (testé : le contenu SVG est silencieusement ignoré). On
 * implémente donc ce back-end minimal qui rasterise directement les chemins du QR — uniquement
 * des rectangles pleins issus de Move/Line/Close, un QR standard n'utilisant pas de courbes.
 */
class GdPngImageBackEnd implements ImageBackEndInterface
{
    /** @var resource|\GdImage */
    private $image;

    /** @var array<int, array{0:float,1:float,2:float,3:float,4:float,5:float}> */
    private array $stack = [];

    /** @var array{0:float,1:float,2:float,3:float,4:float,5:float} */
    private array $transform = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];

    public function new(int $size, ColorInterface $backgroundColor): void
    {
        $this->image = imagecreatetruecolor($size, $size);
        imagefill($this->image, 0, 0, $this->allocateColor($backgroundColor));
        $this->transform = [1.0, 0.0, 0.0, 1.0, 0.0, 0.0];
        $this->stack = [];
    }

    public function scale(float $size): void
    {
        $this->transform = $this->compose($this->transform, [$size, 0.0, 0.0, $size, 0.0, 0.0]);
    }

    public function translate(float $x, float $y): void
    {
        $this->transform = $this->compose($this->transform, [1.0, 0.0, 0.0, 1.0, $x, $y]);
    }

    public function rotate(int $degrees): void
    {
        $rad = deg2rad($degrees);
        $cos = cos($rad);
        $sin = sin($rad);
        $this->transform = $this->compose($this->transform, [$cos, $sin, -$sin, $cos, 0.0, 0.0]);
    }

    public function push(): void
    {
        $this->stack[] = $this->transform;
    }

    public function pop(): void
    {
        $this->transform = array_pop($this->stack) ?? $this->transform;
    }

    public function drawPathWithColor(Path $path, ColorInterface $color): void
    {
        // BaconQrCode compresse le QR en UN seul chemin composé de plusieurs sous-contours
        // (un par bloc de modules adjacents, plus les 3 motifs de repérage aux coins) avec la
        // règle "evenodd" : les contours imbriqués (ex. l'anneau des motifs de repérage) créent
        // des trous par parité de croisement. imagefilledpolygon() ne gère pas ça nativement
        // (chaque sous-contour serait rempli plein, sans découper les trous), d'où un rendu de
        // remplissage par balayage de lignes (scanline) appliquant cette règle manuellement.
        $this->fillEvenOdd($this->extractPolygons($path), $this->allocateColor($color));
    }

    /**
     * @param array<int, array<int, array{0: float, 1: float}>> $polygons
     */
    private function fillEvenOdd(array $polygons, int $color): void
    {
        $edges = [];
        $minY = $maxY = null;

        foreach ($polygons as $polygon) {
            $count = count($polygon);
            for ($i = 0; $i < $count; $i++) {
                [$x1, $y1] = $polygon[$i];
                [$x2, $y2] = $polygon[($i + 1) % $count];

                if ($y1 === $y2) {
                    continue; // arête horizontale : ne participe pas au balayage
                }

                $edges[] = [$x1, $y1, $x2, $y2];
                $minY = $minY === null ? min($y1, $y2) : min($minY, $y1, $y2);
                $maxY = $maxY === null ? max($y1, $y2) : max($maxY, $y1, $y2);
            }
        }

        if (! $edges) {
            return;
        }

        $width = imagesx($this->image);
        $height = imagesy($this->image);
        $yStart = max(0, (int) floor($minY));
        $yEnd = min($height - 1, (int) ceil($maxY));

        for ($y = $yStart; $y <= $yEnd; $y++) {
            $scanY = $y + 0.5; // centre du pixel : évite les ambiguïtés sur les arêtes horizontales
            $crossings = [];

            foreach ($edges as [$x1, $y1, $x2, $y2]) {
                $edgeYMin = min($y1, $y2);
                $edgeYMax = max($y1, $y2);
                if ($scanY < $edgeYMin || $scanY >= $edgeYMax) {
                    continue;
                }
                $t = ($scanY - $y1) / ($y2 - $y1);
                $crossings[] = $x1 + $t * ($x2 - $x1);
            }

            sort($crossings);

            for ($i = 0; $i + 1 < count($crossings); $i += 2) {
                $xStart = max(0, (int) round($crossings[$i]));
                $xEnd = min($width - 1, (int) round($crossings[$i + 1]) - 1);
                if ($xEnd >= $xStart) {
                    imageline($this->image, $xStart, $y, $xEnd, $y, $color);
                }
            }
        }
    }

    public function drawPathWithGradient(
        Path $path,
        Gradient $gradient,
        float $x,
        float $y,
        float $width,
        float $height
    ): void {
        // Pas de dégradé utilisé pour le QR de la carte : on retombe sur une couleur pleine.
        $this->drawPathWithColor($path, $gradient->getStartColor());
    }

    public function done(): string
    {
        ob_start();
        imagepng($this->image);
        $blob = ob_get_clean();
        imagedestroy($this->image);

        return $blob;
    }

    /**
     * @return array<int, array<int, array{0: float, 1: float}>>
     */
    private function extractPolygons(Path $path): array
    {
        $polygons = [];
        $current = [];

        foreach ($path as $op) {
            if ($op instanceof Move) {
                if (! empty($current)) {
                    $polygons[] = $current;
                }
                $current = [$this->transformPoint($op->getX(), $op->getY())];
            } elseif ($op instanceof Line) {
                $current[] = $this->transformPoint($op->getX(), $op->getY());
            } elseif ($op instanceof Close) {
                if (! empty($current)) {
                    $polygons[] = $current;
                }
                $current = [];
            } elseif ($op instanceof Curve || $op instanceof EllipticArc) {
                // Non émis par le rendu QR standard (rectangles uniquement).
                continue;
            }
        }

        if (! empty($current)) {
            $polygons[] = $current;
        }

        return $polygons;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function transformPoint(float $x, float $y): array
    {
        [$a, $b, $c, $d, $e, $f] = $this->transform;

        return [$a * $x + $c * $y + $e, $b * $x + $d * $y + $f];
    }

    /**
     * Compose deux matrices affines 2x3 (a b c d e f) telles que le point transformé soit
     * m1(m2(point)) — c'est-à-dire que m2 est appliquée dans le repère local introduit par m1,
     * comme des <g transform="m1"><g transform="m2"> imbriqués.
     *
     * @param array{0:float,1:float,2:float,3:float,4:float,5:float} $m1
     * @param array{0:float,1:float,2:float,3:float,4:float,5:float} $m2
     * @return array{0:float,1:float,2:float,3:float,4:float,5:float}
     */
    private function compose(array $m1, array $m2): array
    {
        [$a1, $b1, $c1, $d1, $e1, $f1] = $m1;
        [$a2, $b2, $c2, $d2, $e2, $f2] = $m2;

        return [
            $a1 * $a2 + $c1 * $b2,
            $b1 * $a2 + $d1 * $b2,
            $a1 * $c2 + $c1 * $d2,
            $b1 * $c2 + $d1 * $d2,
            $a1 * $e2 + $c1 * $f2 + $e1,
            $b1 * $e2 + $d1 * $f2 + $f1,
        ];
    }

    private function allocateColor(ColorInterface $color): int
    {
        $rgb = $color->toRgb();

        return imagecolorallocate($this->image, $rgb->getRed(), $rgb->getGreen(), $rgb->getBlue());
    }
}
