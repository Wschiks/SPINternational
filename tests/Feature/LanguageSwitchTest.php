<?php

namespace Tests\Feature;

use Tests\TestCase;

class LanguageSwitchTest extends TestCase
{
    public function test_the_site_is_dutch_until_another_language_is_picked(): void
    {
        $this->get('/')->assertOk()->assertSee('Inloggen en spelen');
    }

    public function test_picking_english_translates_the_site_and_returns_to_the_page(): void
    {
        $this->from('/login')->post('/language/en')->assertRedirect('/login');

        $this->get('/')->assertOk()
            ->assertSee('Log in and play')
            ->assertDontSee('Inloggen en spelen');
    }

    public function test_an_unknown_language_is_refused(): void
    {
        $this->post('/language/de')->assertNotFound();
    }
}
