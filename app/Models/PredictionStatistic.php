<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PredictionStatistic extends Model
{
    use HasFactory;

    //protected $connection = 'mysql2';
    //protected $table = 'predictionstatistic';

    protected $connection = 'remote';
    protected $table = 'PredictionStatistic';

}
