<?php

namespace App\Http\Controllers;

use App\Models\GroceryBudget;
use App\Models\GroceryExpense;
use Illuminate\Http\Request;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GroceryExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(GroceryBudget $grocery)
    {
        return response()->json([
            'success' => true,
            'data' => $grocery->expenses()->latest()->get(),
        ]);
    }

    private function ensureOwnsExpense(
    Request $request,
    GroceryBudget $grocery,
    GroceryExpense $expense
): void {

    abort_unless(
        $grocery->user_id === $request->user()->id,
        404
    );

    abort_unless(
        $expense->grocery_budget_id === $grocery->id,
        404
    );
}

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, GroceryBudget $grocery)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $expense = $grocery->expenses()->create($validated);

        return response()->json([
            'success' => true,
            'data' => $expense,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(GroceryBudget $grocery, GroceryExpense $expense)
    {
        return response()->json([
            'success' => true,
            'data' => $expense,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, GroceryBudget $grocery, GroceryExpense $expense)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'amount' => 'sometimes|numeric|min:0',
            'expense_date' => 'sometimes|date',
            'notes' => 'nullable|string',
        ]);

        $expense->update($validated);

        return response()->json([
            'success' => true,
            'data' => $expense->fresh(),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GroceryBudget $grocery, GroceryExpense $expense)
    {
        $expense->delete();

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully',
        ]);
    }
}