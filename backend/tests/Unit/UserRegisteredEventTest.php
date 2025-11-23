<?php

namespace Tests\Unit;

use App\Events\UserRegistered;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegisteredEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_registered_event_contains_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $event = new UserRegistered($user);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals('Test User', $event->user->name);
        $this->assertEquals('test@example.com', $event->user->email);
    }

    public function test_user_registered_event_is_serializable(): void
    {
        $user = User::factory()->create();

        $event = new UserRegistered($user);

        // Test that the event can be serialized (important for queue)
        $serialized = serialize($event);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(UserRegistered::class, $unserialized);
        $this->assertEquals($user->id, $unserialized->user->id);
        $this->assertEquals($user->email, $unserialized->user->email);
    }
}

