<?php

declare(strict_types=1);

namespace Survos\MapillaryBundle\Command;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Validator\Constraints as Assert;

final class NearbySearchInput
{
    #[Argument('Center latitude')]
    #[Assert\Range(min: -90, max: 90)]
    public float $latitude;

    #[Argument('Center longitude')]
    #[Assert\Range(min: -180, max: 180)]
    public float $longitude;

    #[Option('Search radius in meters')]
    #[Assert\Range(min: 1, max: 50)]
    public int $radius = 50;

    #[Option('Maximum nearby candidates before local panorama filtering')]
    #[Assert\Range(min: 1, max: 100)]
    public int $limit = 20;

    #[Option('Include flat images; otherwise only returned panoramas are displayed')]
    public bool $includeFlat = false;
}
