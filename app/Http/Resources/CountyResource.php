<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'badge' => $this->badge,
            'badge_url' => $this->badge_url,
            // Csak akkor szerepel, ha a lekérdezés withSum('cities', 'population')-t használt.
            // A SUM() város nélküli megyénél NULL, ezért (int) cast: ott 0 legyen.
            'population' => $this->when(
                array_key_exists('cities_sum_population', $this->resource->getAttributes()),
                fn () => (int) $this->cities_sum_population,
            ),
        ];
    }
}
