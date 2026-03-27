<?php

namespace App\GameEngine\Terrain\Generator;

/**
 * Bruit de Perlin 2D deterministe avec support octaves.
 *
 * Genere des valeurs continues entre -1 et 1 a partir de coordonnees (x, y).
 * Le seed permet de reproduire exactement la meme carte.
 */
class PerlinNoise
{
    /** @var int[] Table de permutations (512 entrees, doublees pour eviter le modulo) */
    private array $perm;

    public function __construct(int $seed = 0)
    {
        $this->perm = $this->buildPermutationTable($seed);
    }

    /**
     * Bruit de Perlin 2D brut.
     *
     * @return float Valeur entre -1 et 1
     */
    public function noise(float $x, float $y): float
    {
        // Coordonnees de la cellule unitaire
        $xi = (int) floor($x) & 255;
        $yi = (int) floor($y) & 255;

        // Offsets fractionnaires dans la cellule
        $xf = $x - floor($x);
        $yf = $y - floor($y);

        // Courbes de lissage (fade)
        $u = $this->fade($xf);
        $v = $this->fade($yf);

        // Hash des 4 coins
        $aa = $this->perm[$this->perm[$xi] + $yi];
        $ab = $this->perm[$this->perm[$xi] + $yi + 1];
        $ba = $this->perm[$this->perm[$xi + 1] + $yi];
        $bb = $this->perm[$this->perm[$xi + 1] + $yi + 1];

        // Interpolation bilineaire des gradients
        $x1 = $this->lerp(
            $this->grad($aa, $xf, $yf),
            $this->grad($ba, $xf - 1.0, $yf),
            $u
        );
        $x2 = $this->lerp(
            $this->grad($ab, $xf, $yf - 1.0),
            $this->grad($bb, $xf - 1.0, $yf - 1.0),
            $u
        );

        return $this->lerp($x1, $x2, $v);
    }

    /**
     * Bruit avec octaves (fBm — fractional Brownian motion).
     *
     * @param float $x           Coordonnee X
     * @param float $y           Coordonnee Y
     * @param int   $octaves     Nombre d'octaves (detail)
     * @param float $lacunarity  Facteur de frequence entre octaves (defaut 2.0)
     * @param float $persistence Facteur d'amplitude entre octaves (defaut 0.5)
     *
     * @return float Valeur normalisee entre -1 et 1
     */
    public function octaveNoise(float $x, float $y, int $octaves = 4, float $lacunarity = 2.0, float $persistence = 0.5): float
    {
        $total = 0.0;
        $frequency = 1.0;
        $amplitude = 1.0;
        $maxValue = 0.0;

        for ($i = 0; $i < $octaves; ++$i) {
            $total += $this->noise($x * $frequency, $y * $frequency) * $amplitude;
            $maxValue += $amplitude;
            $amplitude *= $persistence;
            $frequency *= $lacunarity;
        }

        return $total / $maxValue;
    }

    /**
     * Genere une heightmap 2D normalisee entre 0 et 1.
     *
     * @param int   $width    Largeur en tiles
     * @param int   $height   Hauteur en tiles
     * @param float $scale    Echelle du bruit (plus grand = plus zoom out)
     * @param int   $octaves  Nombre d'octaves
     *
     * @return float[][] Tableau [x][y] de valeurs entre 0 et 1
     */
    public function generateHeightmap(int $width, int $height, float $scale = 0.05, int $octaves = 4): array
    {
        $heightmap = [];

        for ($x = 0; $x < $width; ++$x) {
            $column = [];
            for ($y = 0; $y < $height; ++$y) {
                // Convertir noise [-1,1] en [0,1]
                $value = ($this->octaveNoise($x * $scale, $y * $scale, $octaves) + 1.0) / 2.0;
                $column[$y] = max(0.0, min(1.0, $value));
            }
            $heightmap[$x] = $column;
        }

        return $heightmap;
    }

    /**
     * Courbe de lissage 6t^5 - 15t^4 + 10t^3 (Perlin ameliore).
     */
    private function fade(float $t): float
    {
        return $t * $t * $t * ($t * ($t * 6.0 - 15.0) + 10.0);
    }

    /**
     * Interpolation lineaire.
     */
    private function lerp(float $a, float $b, float $t): float
    {
        return $a + $t * ($b - $a);
    }

    /**
     * Gradient pseudo-aleatoire pour un hash et un offset.
     */
    private function grad(int $hash, float $x, float $y): float
    {
        return match ($hash & 3) {
            0 => $x + $y,
            1 => -$x + $y,
            2 => $x - $y,
            3 => -$x - $y,
        };
    }

    /**
     * Construit la table de permutations a partir d'un seed.
     *
     * @return int[] 512 entrees (table doublee)
     */
    private function buildPermutationTable(int $seed): array
    {
        // Table de base 0-255
        $p = range(0, 255);

        // Fisher-Yates shuffle deterministe
        $rng = $seed;
        for ($i = 255; $i > 0; --$i) {
            // LCG simple pour un shuffle deterministe
            $rng = ($rng * 1103515245 + 12345) & 0x7FFFFFFF;
            $j = $rng % ($i + 1);
            [$p[$i], $p[$j]] = [$p[$j], $p[$i]];
        }

        // Doubler la table pour eviter le modulo
        $perm = [];
        for ($i = 0; $i < 512; ++$i) {
            $perm[$i] = $p[$i & 255];
        }

        return $perm;
    }
}
