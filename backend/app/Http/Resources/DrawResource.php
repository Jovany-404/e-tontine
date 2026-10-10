<?php

namespace App\Http\Resources;

use App\Models\Draw;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Draw
 */
class DrawResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->public_id,
            'cycle_number' => $this->whenLoaded('cycle', fn () => $this->cycle->number),
            'round' => $this->round,
            'amount' => $this->amount,
            'drawn_at' => $this->drawn_at?->toIso8601String(),
            'winner' => $this->whenLoaded('winner', fn () => [
                'id' => $this->winner->user->public_id,
                'name' => $this->winner->user->name,
            ]),
        ];
    }
}
