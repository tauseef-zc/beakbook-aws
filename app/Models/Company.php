<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    //protected $connection = 'mysql2';
    //protected $table = 'company';

    protected $connection = 'remote';
    protected $table = 'Company';

}
