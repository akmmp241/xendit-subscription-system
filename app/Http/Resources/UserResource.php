<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "mobile_number" => $this->mobile_number,
            "email" => $this->email,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at
        ];
    }
}
