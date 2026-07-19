<?php

namespace Tests\Feature;

use App\Models\Homework;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HomeworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_homework(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/homework', [
            'title' => 'Math Assignment',
            'description' => 'Finish algebra exercises.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Math Assignment')
            ->assertJsonPath('data.description', 'Finish algebra exercises.');

        $this->assertDatabaseHas('homework', [
            'user_id' => $user->id,
            'title' => 'Math Assignment',
        ]);
    }

    public function test_user_can_list_only_their_homework(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        Homework::create([
            'user_id' => $user->id,
            'title' => 'Own Homework',
            'description' => 'Visible data.',
        ]);

        Homework::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Homework',
            'description' => 'Hidden data.',
        ]);

        $response = $this->getJson('/api/homework');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Own Homework');
    }

    public function test_user_can_update_homework(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $homework = Homework::create([
            'user_id' => $user->id,
            'title' => 'Old Title',
            'description' => 'Old description.',
        ]);

        $response = $this->putJson("/api/homework/{$homework->id}", [
            'title' => 'Updated Title',
            'description' => 'Updated description.',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Title')
            ->assertJsonPath('data.description', 'Updated description.');
    }

    public function test_user_can_delete_only_their_homework(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $ownHomework = Homework::create([
            'user_id' => $user->id,
            'title' => 'Own Homework',
            'description' => 'Visible data.',
        ]);

        $otherHomework = Homework::create([
            'user_id' => $otherUser->id,
            'title' => 'Other Homework',
            'description' => 'Hidden data.',
        ]);

        $this->deleteJson("/api/homework/{$otherHomework->id}")->assertNotFound();

        $this->deleteJson("/api/homework/{$ownHomework->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('homework', ['id' => $ownHomework->id]);
        $this->assertDatabaseHas('homework', ['id' => $otherHomework->id]);
    }

    public function test_title_and_description_are_required(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/homework', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'description']);
    }
}
