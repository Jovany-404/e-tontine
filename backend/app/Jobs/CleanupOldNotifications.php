<?php

namespace App\Jobs;

use App\Models\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CleanupOldNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $threshold = now()->subDays(30);

        Notification::query()
            ->where(function ($query) use ($threshold) {
                $query->where('is_read', true)
                    ->whereNotNull('read_at')
                    ->where('read_at', '<', $threshold);
            })
            ->orWhere(function ($query) use ($threshold) {
                $query->where('is_read', true)
                    ->whereNull('read_at')
                    ->where('updated_at', '<', $threshold);
            })
            ->delete();
    }
}
