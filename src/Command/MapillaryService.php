<?php

declare(strict_types=1);

namespace Survos\MapillaryBundle\Command;

use Survos\Mapillary\Exception\MapillaryException;
use Survos\Mapillary\MapillaryClientInterface;
use Survos\Mapillary\Model\Image;
use Survos\Mapillary\Model\ImagePage;
use Survos\Mapillary\Request\BoundingBox;
use Survos\Mapillary\Request\ImageSearch;
use Survos\Mapillary\Request\NearbySearch;
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

        return $this->display($io, $page);
    }

    #[AsCommand('mapillary:images:nearby', 'Find nearby images within 50 meters; display returned panoramas by default')]
    public function nearby(SymfonyStyle $io, #[MapInput] NearbySearchInput $input): int
    {
        try {
            $page = $this->client->nearbyImages(new NearbySearch($input->latitude, $input->longitude, $input->radius, $input->limit));
        } catch (\InvalidArgumentException $error) {
            $io->error($error->getMessage());

            return Command::INVALID;
        } catch (MapillaryException $error) {
            $io->error($error->getMessage());

            return Command::FAILURE;
        }
        if (!$input->includeFlat) {
            $io->note(sprintf('Filtering panoramas from %d nearby candidates; this is not a complete inventory.', count($page->images)));
            $page = new ImagePage(array_values(array_filter($page->images, static fn (Image $image): bool => $image->isPano)), null, $page->possiblyTruncated);
        }

        return $this->display($io, $page);
    }

    private function display(SymfonyStyle $io, ImagePage $page): int
    {
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
            $io->note('The candidate result reached the requested limit. This bounded search is not a complete inventory.');
        }

        return Command::SUCCESS;
    }
}
