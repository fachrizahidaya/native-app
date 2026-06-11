<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroceryBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'budget_amount',
        'period_start',
        'period_end',
    ];

    protected function casts(): array
    {
        return [
            'budget_amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
{
    return $this->hasMany(GroceryExpense::class, 'grocery_budget_id');
}
}
