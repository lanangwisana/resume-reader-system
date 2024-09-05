<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Competition extends Model
{
    use HasFactory;
    protected $table = 'competitions';
    protected $primaryKey = 'id';
    protected $fillable = ['competition_name', 'year', 'organizer', 'achievement'];
}
