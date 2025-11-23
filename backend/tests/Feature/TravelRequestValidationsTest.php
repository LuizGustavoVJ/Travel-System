<?php

namespace Tests\Feature;

use App\Models\TravelRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TravelRequestValidationsTest extends TestCase
{
    use RefreshDatabase;

    private function authenticatedUser(): array
    {
        $user = User::factory()->create();
        $token = auth()->login($user);
        return [$user, $token];
    }

    public function test_cannot_create_travel_request_with_past_start_date(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'destination' => 'São Paulo',
                'start_date' => now()->subDays(5)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_cannot_create_travel_request_with_end_date_before_start_date(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'destination' => 'São Paulo',
                'start_date' => now()->addDays(10)->toDateString(),
                'end_date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_cannot_update_approved_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$travelRequest->id}", [
                'destination' => 'New Destination',
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_update_cancelled_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$travelRequest->id}", [
                'destination' => 'New Destination',
            ]);

        $response->assertStatus(403);
    }

    public function test_cannot_delete_approved_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'approved',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/travel-requests/{$travelRequest->id}");

        $response->assertStatus(403);
    }

    public function test_cannot_delete_cancelled_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/travel-requests/{$travelRequest->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_requested_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$travelRequest->id}", [
                'destination' => 'New Destination',
            ]);

        $response->assertStatus(200);
    }

    public function test_can_delete_requested_travel_request(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/travel-requests/{$travelRequest->id}");

        $response->assertStatus(200);
    }

    public function test_validation_requires_destination(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'start_date' => now()->addDays(5)->toDateString(),
                'end_date' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destination']);
    }

    public function test_validation_requires_start_date(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'destination' => 'São Paulo',
                'end_date' => now()->addDays(10)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    public function test_validation_requires_end_date(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/travel-requests', [
                'destination' => 'São Paulo',
                'start_date' => now()->addDays(5)->toDateString(),
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_cannot_update_travel_request_with_end_date_before_start_date_from_database(): void
    {
        [$user, $token] = $this->authenticatedUser();

        // Cria um pedido com start_date no futuro
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        // Tenta atualizar apenas end_date para uma data anterior ao start_date do banco
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$travelRequest->id}", [
                'end_date' => now()->addDays(5)->toDateString(), // Antes do start_date (10 dias)
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_can_update_travel_request_with_end_date_after_start_date_from_database(): void
    {
        [$user, $token] = $this->authenticatedUser();

        // Cria um pedido com start_date no futuro
        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        // Atualiza apenas end_date para uma data posterior ao start_date do banco
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$travelRequest->id}", [
                'end_date' => now()->addDays(20)->toDateString(), // Depois do start_date (10 dias)
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'end_date' => now()->addDays(20)->toDateString(),
        ]);
    }

    public function test_cannot_update_travel_request_with_end_date_before_provided_start_date(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        // Tenta atualizar ambos start_date e end_date, mas end_date antes de start_date
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$travelRequest->id}", [
                'start_date' => now()->addDays(20)->toDateString(),
                'end_date' => now()->addDays(15)->toDateString(), // Antes do start_date fornecido
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_can_update_travel_request_with_both_dates_valid(): void
    {
        [$user, $token] = $this->authenticatedUser();

        $travelRequest = TravelRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'requested',
            'start_date' => now()->addDays(10),
            'end_date' => now()->addDays(15),
        ]);

        // Atualiza ambos start_date e end_date com valores válidos
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->putJson("/api/travel-requests/{$travelRequest->id}", [
                'start_date' => now()->addDays(20)->toDateString(),
                'end_date' => now()->addDays(25)->toDateString(), // Depois do start_date
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('travel_requests', [
            'id' => $travelRequest->id,
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
        ]);
    }
}

