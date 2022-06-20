<?php

namespace App\Http\Resources;

use App\Models\Cycle;
use App\Models\FavouriteBarns;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class BarnResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {

        $cycle = $this->cycles->first();
        $mortality = 0;
        $weight_mean = 0;
        $weight_diff = 0;
        if (!empty($cycle)) {
            $mean_today =  $cycle->barnStatistics->take(2)[0]['mean'];
            $mean_prv_day = $cycle->barnStatistics->take(2)[1]['mean'];

            $weight_percentage = ((($mean_today - $mean_prv_day) / $mean_prv_day) * 100);


            $weight_diff = (number_format((float)$weight_percentage, 3, '.', '') . '%');

            if ($mean_today < 1000) {
                $element = 'g';
            } else {
                $mean_today = $mean_today / 1000;
                $element = 'kg';
            }
            $weight_mean =  number_format((float)$mean_today, 2, '.', '') . $element;
            $mortality = $cycle->death_number . '/' . $cycle->population_number . ' (' . number_format((float)$cycle->death_number / $cycle->population_number * 100, 2, '.', '') . '%)';
        }

        //get favorite barn id's for user and barn id
        $favourite_barn = FavouriteBarns::where('user_id', Auth::user()->id)->where('barn_id', $this->id)->first();

        return [
            'barn_id' => $this->id,
            'farm_id' => $this->farm_id,
            'name' => $this->name,
            'farm' => FarmResource::make($this->farm)->name,
            'mortality' => $mortality,
            'weight' => $weight_mean,
            'weight_diff' => $weight_diff,
            'fav_barn' => $favourite_barn ? count(array($favourite_barn)) : 0,
            'cycle_id' => CycleResource::collection($this->cycles),

        ];
    }
}
