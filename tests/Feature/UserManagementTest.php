<?php

namespace Tests\Feature;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    public function test_admin_can_view_user_management_screen(): void
    {
        $admin = User::where('email', 'admin@sukarami-desa.id')->first();
        $this->assertNotNull($admin);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_user_management_screen(): void
    {
        $kades = User::where('email', 'kades@sukarami-desa.id')->first();
        $this->assertNotNull($kades);

        $response = $this->actingAs($kades)->get(route('admin.users.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_create_new_staff_user(): void
    {
        $admin = User::where('email', 'admin@sukarami-desa.id')->first();
        $this->assertNotNull($admin);

        $testEmail = 'staff_unit_' . time() . '@sukarami-desa.id';

        $response = $this->withoutMiddleware()
            ->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Staf Baru Desa',
                'email' => $testEmail,
                'jabatan' => 'Operator Pengarsipan',
                'role' => 'sekdes-operator',
                'password' => 'password123',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => $testEmail]);

        $created = User::where('email', $testEmail)->first();
        $this->assertTrue($created->hasRole('sekdes-operator'));
        $this->assertTrue((bool) $created->is_active);
    }

    public function test_admin_can_update_user_status_and_role(): void
    {
        $admin = User::where('email', 'admin@sukarami-desa.id')->first();
        $this->assertNotNull($admin);

        $testEmail = 'staff_edit_' . time() . '@sukarami-desa.id';
        $user = User::create([
            'name' => 'Test User',
            'email' => $testEmail,
            'jabatan' => 'Staf Pelayanan',
            'password' => 'secret123',
            'is_active' => true,
        ]);
        $user->assignRole('sekdes-operator');

        $response = $this->withoutMiddleware()
            ->actingAs($admin)
            ->put(route('admin.users.update', $user->id), [
                'name' => 'Test User Updated',
                'email' => $testEmail,
                'jabatan' => 'Kasi Pemerintahan Baru',
                'role' => 'petugas-pemerintahan',
                'is_active' => false,
            ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertEquals('Kasi Pemerintahan Baru', $user->position);
        $this->assertEquals('Kasi Pemerintahan Baru', $user->jabatan);
        $this->assertFalse((bool) $user->is_active);
        $this->assertTrue($user->hasRole('petugas-pemerintahan'));
    }
}
