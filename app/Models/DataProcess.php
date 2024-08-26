<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataProcess extends Model
{
    use HasFactory;
    protected $fillable = ['file_name', 'data'];

}
