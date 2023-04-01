<?php

class Tiles {
    public const WATER = 1;
    public const SAND  = 2;
    public const GRASS = 3;
    public const WOOD  = 4;
    public const STONE = 5;
}

class MapGenerator {

    private const MAX_SEED = 2147483647;

    private const DN = 1; // direction north
    private const DE = 2; // direction east
    private const DS = 3; // direction south
    private const DW = 4; // direction west

    public int $width = 100;
    public int $height = 100;
    public int $loops = 16;
    public int $maxGrowth = 15;
    public int $fallDownRate = 20;

    public array $map = [];

    public function __construct(int $seed = null) {
        $this->map = array_fill(0, $this->width, array_fill(0, $this->height, Tiles::WATER));
        $this->generate($seed);
    }

    public function generate(int $seed = null): void {
        mt_srand(
            (is_int($seed) && $seed >= 0 && $seed <= self::MAX_SEED)
            ? $seed
            : rand(0, self::MAX_SEED)
        );
        for ($i = $this->loops; $i > 0; $i--)
            $this->generateIsland();

        // beautification #1 replace all sand with grass
        for ($x = 0; $x < $this->width; $x++)
            for ($y = 0; $y < $this->height; $y++)
                if ($this->map[$x][$y] === Tiles::SAND)
                    $this->map[$x][$y] = Tiles::GRASS;

        // beautification #2 replace single tiles with water
        for ($x = 0; $x < $this->width; $x++)
            for ($y = 0; $y < $this->height; $y++)
                if ($this->isSingle($x, $y))
                    $this->map[$x][$y] = Tiles::WATER;

        // beautification #3 grow sand on the islands
        for ($x = 0; $x < $this->width; $x++)
            for ($y = 0; $y < $this->height; $y++)
                $this->growSand($x, $y);
    }

    private function generateIsland(): void {
        $origin = ['x' => mt_rand(2, $this->width - 1), 'y' => mt_rand(2, $this->height - 1)];
        $this->map[$origin['x']][$origin['y']] = Tiles::STONE;
        $this->grow($origin, Tiles::STONE, self::DN, 1);
    }

    private function grow(array $pos, int $tile, int $direction, int $counter): void {
        if ($counter > $this->maxGrowth)
            return;

        switch ($direction) {
            case self::DN:
                $pos['y']++; break;
            case self::DE:
                $pos['x']++; break;
            case self::DS:
                $pos['y']--; break;
            case self::DW:
                $pos['x']--; break;
        }

        if (($this->map[$pos['x']][$pos['y']] ?? 0) !== Tiles::WATER)
            return;

        if ($pos['x'] < 1 || $pos['x'] > $this->width -1 || $pos['y'] < 1 || $pos['y'] > $this->height -1)
            return;

        $this->map[$pos['x']][$pos['y']] = $newTile = $this->nextTile($tile);
        if ($newTile <= Tiles::SAND)
            return;

        $c = $counter + 1;
        $this->grow($pos, $newTile, self::DN, $c);
        $this->grow($pos, $newTile, self::DE, $c);
        $this->grow($pos, $newTile, self::DS, $c);
        $this->grow($pos, $newTile, self::DW, $c);
    }

    private function nextTile(int $tile): int {
        $fallDown = mt_rand(1, 100);
        if ($fallDown > $this->fallDownRate)
            return $tile;

        return $this->nextTile($tile <= Tiles::WATER ? $tile : $tile -1);
    }

    private function isSingle(int $x, int $y): bool {
        $w = Tiles::WATER;
        return (
            ($this->map[$x +1][$y   ] ?? $w) === $w && ($this->map[$x -1][$y   ] ?? $w) === $w &&
            ($this->map[$x   ][$y +1] ?? $w) === $w && ($this->map[$x   ][$y -1] ?? $w) === $w &&
            ($this->map[$x +1][$y +1] ?? $w) === $w && ($this->map[$x +1][$y -1] ?? $w) === $w &&
            ($this->map[$x -1][$y +1] ?? $w) === $w && ($this->map[$x -1][$y -1] ?? $w) === $w
        );
    }

    private function growSand(int $x, int $y): void {
        if ($this->map[$x][$y] <= Tiles::SAND)
            return;

        if (($this->map[$x +1][$y   ] ?? 0) === Tiles::WATER)
             $this->map[$x +1][$y   ] = Tiles::SAND;
        if (($this->map[$x -1][$y   ] ?? 0) === Tiles::WATER)
             $this->map[$x -1][$y   ] = Tiles::SAND;
        if (($this->map[$x   ][$y +1] ?? 0) === Tiles::WATER)
             $this->map[$x   ][$y +1] = Tiles::SAND;
        if (($this->map[$x   ][$y -1] ?? 0) === Tiles::WATER)
             $this->map[$x   ][$y -1] = Tiles::SAND;
        if (($this->map[$x +1][$y +1] ?? 0) === Tiles::WATER)
             $this->map[$x +1][$y +1] = Tiles::SAND;
        if (($this->map[$x +1][$y -1] ?? 0) === Tiles::WATER)
             $this->map[$x +1][$y -1] = Tiles::SAND;
        if (($this->map[$x -1][$y +1] ?? 0) === Tiles::WATER)
             $this->map[$x -1][$y +1] = Tiles::SAND;
        if (($this->map[$x -1][$y -1] ?? 0) === Tiles::WATER)
             $this->map[$x -1][$y -1] = Tiles::SAND;
    }
}

class ImageGenerator {

    public int $pixelSize = 10;

    public function __construct(private MapGenerator $map) {}

    public function display(): void {
        $image = imagecreatetruecolor($this->map->width * $this->pixelSize, $this->map->height * $this->pixelSize);
        $colors = [
            Tiles::WATER => imagecolorallocate($image,   0,   0, 255),
            Tiles::SAND  => imagecolorallocate($image, 255, 255,   0),
            Tiles::GRASS => imagecolorallocate($image, 126, 200,  80),
            Tiles::WOOD  => imagecolorallocate($image,   0,  87,   0),
            Tiles::STONE => imagecolorallocate($image, 128, 128, 128)
        ];

        for ($x = 0; $x < $this->map->width; $x++)
            for ($y = 0; $y < $this->map->height; $y++)
                imagefilledrectangle(
                    $image,
                    $x * $this->pixelSize,
                    $y * $this->pixelSize,
                    $x * $this->pixelSize + $this->pixelSize,
                    $y * $this->pixelSize + $this->pixelSize,
                    $colors[$this->map->map[$x][$y]]
                );

        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
    }
}

