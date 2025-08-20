<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class SeriesCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $seriesName;
    public int $seriesId;
    public int $seriesSeasonsQty;
    public int $seriesEpisodesPerSeason;

    public function __construct(
        string $seriesName,
        int $seriesId,
        int $seriesSeasonsQty,
        int $seriesEpisodesPerSeason
    ) {
        $this->seriesName = $seriesName;
        $this->seriesId = $seriesId;
        $this->seriesSeasonsQty = $seriesSeasonsQty;
        $this->seriesEpisodesPerSeason = $seriesEpisodesPerSeason;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}