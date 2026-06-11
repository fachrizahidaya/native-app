<?php

namespace Tests\Feature;

use App\Models\GroceryBudget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GroceryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_grocery_budget_with_expenses_and_summary(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/groceries', [
            'title' => 'Groceries June',
            'budget_amount' => 1000000,
            'period_start' => '2026-06-25',
            'expenses' => [
                [
                    'name' => 'Rice',
                    'amount' => 250000,
                    'expense_date' => '2026-06-25',
                ],
                [
                    'name' => 'Vegetables',
                    'amount' => 150000,
                    'expense_date' => '2026-07-01',
                    'notes' => 'Weekly shopping',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.period_start', '2026-06-25')
            ->assertJsonPath('data.period_end', '2026-07-24')
            ->assertJsonPath('data.total_expenses', 400000)
            ->assertJsonPath('data.budget_difference', 600000)
            ->assertJsonPath('data.remaining_budget', 600000)
            ->assertJsonPath('data.over_budget', false)
            ->assertJsonCount(2, 'data.expenses');
    }

    public function test_period_end_uses_last_day_when_next_month_does_not_have_start_day(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/groceries', [
            'budget_amount' => 500000,
            'period_start' => '2026-01-30',
            'expenses' => [],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.period_end', '2026-02-28');
    }

    public function test_user_can_update_grocery_budget_and_replace_expenses(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $budget = GroceryBudget::create([
            'user_id' => $user->id,
            'title' => 'Old Budget',
            'budget_amount' => 300000,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
        ]);

        $budget->expenses()->create([
            'name' => 'Old Expense',
            'amount' => 50000,
            'expense_date' => '2026-05-02',
        ]);

        $response = $this->putJson("/api/groceries/{$budget->id}", [
            'title' => 'Updated Budget',
            'budget_amount' => 750000,
            'period_start' => '2026-06-25',
            'expenses' => [
                [
                    'name' => 'Milk',
                    'amount' => 100000,
                    'expense_date' => '2026-07-05',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Budget')
            ->assertJsonPath('data.period_end', '2026-07-24')
            ->assertJsonPath('data.total_expenses', 100000)
            ->assertJsonCount(1, 'data.expenses');
    }

    public function test_user_can_delete_only_their_own_grocery_budget(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $ownBudget = GroceryBudget::create([
            'user_id' => $user->id,
            'title' => 'Own Budget',
            'budget_amount' => 300000,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
        ]);

        $otherBudget = GroceryBudget::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Budget',
            'budget_amount' => 300000,
            'period_start' => '2026-05-01',
            'period_end' => '2026-05-31',
        ]);

        $this->deleteJson("/api/groceries/{$otherBudget->id}")->assertNotFound();

        $this->deleteJson("/api/groceries/{$ownBudget->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('grocery_budgets', ['id' => $ownBudget->id]);
        $this->assertDatabaseHas('grocery_budgets', ['id' => $otherBudget->id]);
    }

    public function test_expense_date_must_be_inside_budget_period(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/groceries', [
            'budget_amount' => 500000,
            'period_start' => '2026-06-25',
            'expenses' => [
                [
                    'name' => 'Outside Period',
                    'amount' => 10000,
                    'expense_date' => '2026-07-25',
                ],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('expenses.0.expense_date');
    }
}
