<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractSolutions extends Model
{
    use HasFactory;

    protected $fillable = ['file_name', 'doctype', 'data'];
}
