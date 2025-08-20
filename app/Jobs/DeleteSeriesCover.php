<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DeleteSeriesCover implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    private string $seriesCoverPath;

    public function __construct(string $seriesCoverPath)
    {
        $this->seriesCoverPath = $seriesCoverPath;
    }

    public function handle(): void
    {
        try {
            if (!empty($this->seriesCoverPath) && Storage::disk('public')->exists($this->seriesCoverPath)) {
                Storage::disk('public')->delete($this->seriesCoverPath);
                
                Log::info('Series cover deleted successfully', [
                    'file_path' => $this->seriesCoverPath
                ]);
            } else {
                Log::warning('Series cover file not found or path is empty', [
                    'file_path' => $this->seriesCoverPath
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to delete series cover', [
                'file_path' => $this->seriesCoverPath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('DeleteSeriesCover job failed permanently', [
            'file_path' => $this->seriesCoverPath,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    public function backoff(): array
    {
        return [1, 5, 10]; 
    }
}