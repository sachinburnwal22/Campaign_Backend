<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'segment_id',
        'channel',
        'message',
        'status',
        'expected_revenue',
        'predicted_revenue',
        'predicted_conversion_rate',
    ];

    protected $casts = [
        'expected_revenue' => 'decimal:2',
        'predicted_revenue' => 'decimal:2',
        'predicted_conversion_rate' => 'decimal:2',
    ];

    public function segment(): BelongsTo
    {
        return $this->belongsTo(Segment::class);
    }

    public function communications(): HasMany
    {
        return $this->hasMany(Communication::class);
    }
}
