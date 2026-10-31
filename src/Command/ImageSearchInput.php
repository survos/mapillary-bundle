<?php

declare(strict_types=1);

namespace Survos\MapillaryBundle\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class ImageSearchInput
{
    #[Argument('Bounding box: west,south,east,north; area must be less than 0.01 square degrees')]
    #[Assert\Regex('/^-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?$/')]
    public string $bbox;

    #[Option('Maximum images to return; this is a sample, not a complete city inventory')]
    #[Assert\Range(min: 1, max: 2000)]
    public int $limit = 20;

    #[Option('Include flat images as well as panoramas')]
    public bool $includeFlat = false;
}
