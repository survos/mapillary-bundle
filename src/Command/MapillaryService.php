<?php

declare(strict_types=1);

namespace Survos\MapillaryBundle\Command;

use Survos\Mapillary\Exception\MapillaryException;
use Survos\Mapillary\MapillaryClientInterface;
use Survos\Mapillary\Request\BoundingBox;
use Survos\Mapillary\Request\ImageSearch;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\MapInput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

final readonly class MapillaryService
{
    public function __construct(private MapillaryClientInterface $client)
    {
    }

    #[AsCommand('mapillary:images:search', 'Search Mapillary metadata in a small bounding box (panoramas by default)')]
    public function search(SymfonyStyle $io, #[MapInput] ImageSearchInput $input): int
    {
        try {
            $search = new ImageSearch(bbox: BoundingBox::fromString($input->bbox), isPano: $input->includeFlat ? null : true, limit: $input->limit);
            $page = $this->client->searchImages($search);
        } catch (\InvalidArgumentException $error) {
            $io->error($error->getMessage());

            return Command::INVALID;
        } catch (MapillaryException $error) {
            $io->error($error->getMessage());

            return Command::FAILURE;
        }
        $rows = [];
        foreach ($page->images as $image) {
            $point = $image->computedGeometry ?? $image->geometry;
            $rows[] = [
                $image->id,
                $image->capturedDate()->format('Y-m-d'),
                $image->isPano ? '360°' : 'flat',
                null !== $point ? sprintf('%.6f, %.6f', $point->latitude(), $point->longitude()) : 'unavailable',
                $image->creator->username ?? 'unavailable',
                null !== $image->width && null !== $image->height ? $image->width.' × '.$image->height : 'unavailable',
                $image->sourceUrl(),
            ];
        }
        $io->table(['Image', 'Captured', 'Type', 'Latitude, longitude', 'Creator', 'Original dimensions', 'Source'], $rows);
        $io->success(sprintf('Returned %d images.', count($page->images)));
        if ($page->possiblyTruncated) {
            $io->note('The result reached the requested limit. Bounding-box searches do not support pagination; this is not a complete inventory.');
        }

        return Command::SUCCESS;
    }
}
