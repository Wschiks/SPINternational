<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guest_home_page_shows_sign_in_and_registration(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Inloggen en spelen')
            ->assertSee('Account aanmaken')
            ->assertDontSee('Uitloggen');
    }

    public function test_signed_in_home_page_shows_game_and_account_actions(): void
    {
        $user = User::factory()->make(['name' => 'Speler']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk()
            ->assertSee('Verder spelen')
            ->assertSee('Mijn profiel')
            ->assertSee('Uitloggen')
            ->assertDontSee('Inloggen en spelen');
    }
}
