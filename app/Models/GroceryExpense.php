<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroceryExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'grocery_budget_id',
        'name',
        'amount',
        'expense_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expense_date' => 'date',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(GroceryBudget::class, 'grocery_budget_id');
    }
}
