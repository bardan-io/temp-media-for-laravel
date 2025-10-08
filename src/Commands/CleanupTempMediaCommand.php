<?php

declare(strict_types=1);

namespace BardanIO\TempMedia\Commands;

use BardanIO\TempMedia\Contracts\MediaTransferServiceInterface;
use BardanIO\TempMedia\Contracts\TempMediaServiceInterface;
use Illuminate\Console\Command;

final class CleanupTempMediaCommand extends Command
{
    protected $signature = 'temp-media:cleanup
                            {--expired-only : Only clean up expired temp media}
                            {--processed-only : Only clean up processed temp media}
                            {--dry-run : Show what would be cleaned without actually deleting}';

    protected $description = 'Clean up expired and processed temporary media files';

    public function __construct(
        private readonly TempMediaServiceInterface $tempMediaService,
        private readonly MediaTransferServiceInterface $mediaTransferService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting temporary media cleanup...');

        $expiredCount = 0;
        $processedCount = 0;
        $isDryRun = $this->option('dry-run');

        // Clean expired temp media
        if (! $this->option('processed-only')) {
            if ($isDryRun) {
                // For dry run, we'd need to add a method to count expired without deleting
                $this->info('Dry run: Would clean expired temp media');
            } else {
                $expiredCount = $this->tempMediaService->cleanupExpired();
                $this->info("Cleaned up {$expiredCount} expired temp media files");
            }
        }

        // Clean processed temp media
        if (! $this->option('expired-only')) {
            if ($isDryRun) {
                $this->info('Dry run: Would clean processed temp media');
            } else {
                $processedCount = $this->mediaTransferService->cleanupProcessedTempMedia();
                $this->info("Cleaned up {$processedCount} processed temp media files");
            }
        }

        if ($isDryRun) {
            $this->info('Dry run completed - no files were actually deleted');
        } else {
            $totalCleaned = $expiredCount + $processedCount;
            $this->info("Total cleanup completed: {$totalCleaned} files removed");
        }

        // Show statistics
        $stats = $this->mediaTransferService->getTransferStats();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Temp Media', $stats['total_temp_media']],
                ['Active Temp Media', $stats['active_temp_media']],
                ['Processed Temp Media', $stats['processed_temp_media']],
                ['Expired Temp Media', $stats['expired_temp_media']],
            ]
        );

        return self::SUCCESS;
    }
}
