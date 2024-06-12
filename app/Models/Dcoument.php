<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model // Make sure the class name is correct
{
    use HasFactory;

    protected $table = 'document'; 

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_name',  // Allow mass assignment for file_name
        'data',       // Allow mass assignment for data
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array', // If 'data' is used to store JSON
    ];
}
