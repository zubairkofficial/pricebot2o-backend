<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiModel extends Model
{
    use HasFactory;

    protected $fillable = ['provider_name', 'model_name'];

    // Define the reverse relationship with ApiKey
    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }
}
