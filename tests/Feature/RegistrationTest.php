<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    public function test_registration_screen_cannot_be_rendered_when_disabled(): void
    {
        SiteSetting::set('enable_registration', '0');

        $response = $this->get('/register');
        $response->assertStatus(404);
    }

    public function test_user_cannot_register_when_disabled(): void
    {
        SiteSetting::set('enable_registration', '0');

        $response = $this->withoutMiddleware()
            ->post('/register', [
                'name' => 'Test User',
                'email' => 'testuser@sukarami-desa.id',
                'jabatan' => 'Staf Pelayanan',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertStatus(404);
        $this->assertDatabaseMissing('users', ['email' => 'testuser@sukarami-desa.id']);
    }

    public function test_registration_screen_can_be_rendered_when_enabled(): void
    {
        SiteSetting::set('enable_registration', '1');

        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_new_users_can_register_when_enabled(): void
    {
        SiteSetting::set('enable_registration', '1');

        $email = 'stafbaru_' . time() . '@sukarami-desa.id';

        $response = $this->withoutMiddleware()
            ->post('/register', [
                'name' => 'Staf Baru Desa',
                'email' => $email,
                'jabatan' => 'Operator Arsip',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => $email]);

        // Cleanup
        SiteSetting::set('enable_registration', '0');
    }
}
