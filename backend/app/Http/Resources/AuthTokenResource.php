<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthTokenResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'user' => new UserResource($this->user),
            'token' => $this->token,
            'token_type' => $this->token_type,
        ];
    }
}
