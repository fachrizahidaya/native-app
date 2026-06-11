<?php

namespace App\Http\Controllers;

use App\Models\GroceryBudget;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GroceryBudgetController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $budgets = GroceryBudget::with('expenses')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('period_start')
            ->get()
            ->map(fn (GroceryBudget $budget) => $this->formatBudget($budget));

        return response()->json([
            'success' => true,
            'data' => $budgets,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedData($request);
        $periodStart = CarbonImmutable::parse($data['period_start'])->startOfDay();
        $periodEnd = $this->calculatePeriodEnd($periodStart);

        $this->validateExpenseDates($data['expenses'] ?? [], $periodStart, $periodEnd);

        $budget = DB::transaction(function () use ($request, $data, $periodStart, $periodEnd) {
            $budget = GroceryBudget::create([
                'user_id' => $request->user()->id,
                'title' => $data['title'] ?? 'Groceries',
                'budget_amount' => $data['budget_amount'],
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            $this->syncExpenses($budget, $data['expenses'] ?? []);

            return $budget->load('expenses');
        });

        return response()->json([
            'success' => true,
            'message' => 'Grocery budget created successfully',
            'data' => $this->formatBudget($budget),
        ], 201);
    }

    public function show(Request $request, GroceryBudget $grocery): JsonResponse
    {
        $this->ensureOwnsBudget($request, $grocery);

        return response()->json([
            'success' => true,
            'data' => $this->formatBudget($grocery->load('expenses')),
        ]);
    }

    public function update(Request $request, GroceryBudget $grocery): JsonResponse
    {
        $this->ensureOwnsBudget($request, $grocery);

        $data = $this->validatedData($request);
        $periodStart = CarbonImmutable::parse($data['period_start'])->startOfDay();
        $periodEnd = $this->calculatePeriodEnd($periodStart);

        $this->validateExpenseDates($data['expenses'] ?? [], $periodStart, $periodEnd);

        $budget = DB::transaction(function () use ($grocery, $data, $periodStart, $periodEnd) {
            $grocery->update([
                'title' => $data['title'] ?? 'Groceries',
                'budget_amount' => $data['budget_amount'],
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ]);

            $grocery->expenses()->delete();
            $this->syncExpenses($grocery, $data['expenses'] ?? []);

            return $grocery->refresh()->load('expenses');
        });

        return response()->json([
            'success' => true,
            'message' => 'Grocery budget updated successfully',
            'data' => $this->formatBudget($budget),
        ]);
    }

    public function destroy(Request $request, GroceryBudget $grocery): JsonResponse
    {
        $this->ensureOwnsBudget($request, $grocery);

        $grocery->delete();

        return response()->json([
            'success' => true,
            'message' => 'Grocery budget deleted successfully',
        ]);
    }

    private function validatedData(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'budget_amount' => 'required|numeric|min:0',
            'period_start' => 'required|date',
            'expenses' => 'nullable|array',
            'expenses.*.name' => 'required_with:expenses|string|max:255',
            'expenses.*.amount' => 'required_with:expenses|numeric|min:0',
            'expenses.*.expense_date' => 'required_with:expenses|date',
            'expenses.*.notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    private function validateExpenseDates(array $expenses, CarbonImmutable $periodStart, CarbonImmutable $periodEnd): void
    {
        foreach ($expenses as $index => $expense) {
            $expenseDate = CarbonImmutable::parse($expense['expense_date'])->startOfDay();

            if ($expenseDate->lt($periodStart) || $expenseDate->gt($periodEnd)) {
                throw ValidationException::withMessages([
                    "expenses.$index.expense_date" => [
                        "Expense date must be between {$periodStart->toDateString()} and {$periodEnd->toDateString()}.",
                    ],
                ]);
            }
        }
    }

    private function syncExpenses(GroceryBudget $budget, array $expenses): void
    {
        foreach ($expenses as $expense) {
            $budget->expenses()->create([
                'name' => $expense['name'],
                'amount' => $expense['amount'],
                'expense_date' => CarbonImmutable::parse($expense['expense_date'])->toDateString(),
                'notes' => $expense['notes'] ?? null,
            ]);
        }
    }

    private function calculatePeriodEnd(CarbonImmutable $periodStart): CarbonImmutable
    {
        $nextMonth = $periodStart->firstOfMonth()->addMonth();

        if ($periodStart->day > $nextMonth->daysInMonth) {
            return $nextMonth->endOfMonth()->startOfDay();
        }

        return $nextMonth->day($periodStart->day)->subDay()->startOfDay();
    }

    private function ensureOwnsBudget(Request $request, GroceryBudget $budget): void
    {
        abort_unless($budget->user_id === $request->user()->id, 404);
    }

    private function formatBudget(GroceryBudget $budget): array
    {
        $expenses = $budget->expenses->sortBy('expense_date')->values();
        $totalExpenses = (float) $expenses->sum(fn ($expense) => (float) $expense->amount);
        $budgetAmount = (float) $budget->budget_amount;
        $difference = $budgetAmount - $totalExpenses;

        return [
            'id' => $budget->id,
            'title' => $budget->title,
            'budget_amount' => $budgetAmount,
            'period_start' => $budget->period_start->toDateString(),
            'period_end' => $budget->period_end->toDateString(),
            'expenses' => $expenses->map(fn ($expense) => [
                'id' => $expense->id,
                'name' => $expense->name,
                'amount' => (float) $expense->amount,
                'expense_date' => $expense->expense_date->toDateString(),
                'notes' => $expense->notes,
            ])->values(),
            'total_expenses' => $totalExpenses,
            'budget_difference' => $difference,
            'remaining_budget' => max($difference, 0),
            'over_budget' => $difference < 0,
        ];
    }
}
