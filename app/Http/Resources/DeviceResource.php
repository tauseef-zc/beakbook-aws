<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource
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
            'device_id' => $this->id,
            'farm_id' => $this->barn->farm->id,
            'location' => $this->barn->farm->name.'|'.$this->barn->name,
            'serial_number' => $this->serial_number,
            'barn_id' => $this->barn_id,
            'status' => $this->is_connected,
            'uptime' => $this->uptime,
        ];
    }
}
