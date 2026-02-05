<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AviatorGameResult extends Model
{
    use HasFactory;

    protected $fillable = ['result', 'crash_point'];
}
