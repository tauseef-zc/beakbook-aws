<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;
    //protected $connection = 'mysql2';
    //protected $table = 'device';

    protected $connection = 'remote';
    protected $table = 'Device';

    public $timestamps = false;


    public function barn()
    {
        return $this->belongsTo(Barn::class, 'barn_id', 'id');
    }
}
