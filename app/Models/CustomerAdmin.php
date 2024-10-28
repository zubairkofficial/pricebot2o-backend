<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerAdmin extends Model
{
    use HasFactory;

    // Specify the table if it's not the plural form of the model name
    protected $table = 'customer_admins';

    // Define fillable fields
    protected $fillable = [
        'user_id',
        // Add other fields if necessary
    ];

    /**
     * Get the user that owns the CustomerAdmin.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
