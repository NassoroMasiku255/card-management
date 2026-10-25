<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_root_route_shows_the_landing_page_to_guests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('NiaLike')
            ->assertSee('Hamasika Technology');
    }

    public function test_the_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }
}
