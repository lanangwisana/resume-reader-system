<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtractedText extends Model
{
    use HasFactory;
    protected $table = 'extracted_texts';
    protected $primaryKey = 'id';
    protected $fillable = ['position', 'company', 'start_date', 'end_date'];
}
