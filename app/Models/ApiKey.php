<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'key', 'api_model_id'];

    // Define the relationship with ApiModel
    public function apiModel()
    {
        return $this->hasMany(ApiModel::class, 'api_model_id');
    }
}
