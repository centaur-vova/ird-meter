<?php

declare(strict_types=1);

namespace CentaurVova\IrdMeter;

interface BranchCounter
{
    public function getWeight(): int;
}
