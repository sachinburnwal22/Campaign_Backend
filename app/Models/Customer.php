<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'city',
        'gender',
        'date_of_birth',
        'total_spent',
        'last_order_date',
        'engagement_score',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'last_order_date' => 'date',
        'total_spent' => 'decimal:2',
        'engagement_score' => 'integer',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }
}
