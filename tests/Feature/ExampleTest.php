<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_halaman_utama_mengarahkan_ke_kalender_publik(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/kalender-sekolah');
    }

    public function test_halaman_kalender_publik_dapat_diakses_bebas(): void
    {
        $response = $this->get('/kalender-sekolah');

        $response->assertStatus(200);
    }
}
