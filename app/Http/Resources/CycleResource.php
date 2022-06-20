<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CycleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'cycle_id' => $this->id,
            'starting_date' => $this->starting_date,
            // 'mortality' => $this->death_number . '/' . $this->population_number . ' (' . number_format((float)$this->death_number / $this->population_number * 100, 2, '.', '') . '%)',
            // 'statistics' => $this->barnstatistics->first()->mean,
        ];
    }
}
