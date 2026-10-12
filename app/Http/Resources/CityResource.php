<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'zip_code' => $this->zip_code,
            'population' => $this->population,
            'id_county' => $this->id_county,
            'county' => new CountyResource($this->whenLoaded('county')),
        ];
    }
}
