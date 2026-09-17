<?php

declare(strict_types=1);

namespace CentaurVova\IrdMeter;

final class IrdResult
{
    public function __construct(
        public readonly int $ifWeight,
        public readonly int $totalLines,
        public readonly int $files,
        public readonly float $density,
    ) {
    }

    public function isClean(): bool
    {
        return $this->density < 2;
    }

    public function isGood(): bool
    {
        return $this->density >= 2 && $this->density < 5;
    }

    public function needsAttention(): bool
    {
        return $this->density >= 5 && $this->density < 10;
    }

    public function isForPonies(): bool
    {
        return $this->density >= 10;
    }

    /**
     * @return array{ifWeight: int, totalLines: int, files: int, density: float}
     */
    public function toArray(): array
    {
        return [
            'ifWeight' => $this->ifWeight,
            'totalLines' => $this->totalLines,
            'files' => $this->files,
            'density' => $this->density,
        ];
    }
}
