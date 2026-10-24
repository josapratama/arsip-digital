<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ── Roles ────────────────────────────────────────────────────────────
        $roles = [
            'admin'                 => 'Administrator',
            'kepala-desa'           => 'Kepala Desa',
            'sekdes-operator'       => 'Sekdes / TU / Operator',
            'petugas-kesejahteraan' => 'Kasi Kesejahteraan',
            'petugas-pemerintahan'  => 'Kasi Pemerintahan',
        ];

        foreach ($roles as $slug => $label) {
            Role::firstOrCreate(['name' => $slug, 'guard_name' => 'web']);
        }

        // ── Permissions ──────────────────────────────────────────────────────
        $all = [
            // dokumen
            'view-documents', 'create-documents', 'edit-documents',
            'delete-documents', 'restore-documents',
            // admin only
            'manage-categories', 'manage-users', 'manage-settings',
            // monitoring
            'view-reports', 'view-activity-logs',
        ];

        foreach ($all as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // ── Role → Permission mapping ─────────────────────────────────────────
        // Admin: semua
        Role::findByName('admin')->syncPermissions($all);

        // Kepala Desa: monitoring saja
        Role::findByName('kepala-desa')->syncPermissions([
            'view-documents', 'view-reports',
        ]);

        // Sekdes + TU + Operator: kelola dokumen pengarsipan & proposal
        Role::findByName('sekdes-operator')->syncPermissions([
            'view-documents', 'create-documents', 'edit-documents',
            'delete-documents', 'restore-documents', 'view-reports',
        ]);

        // Kasi Kesejahteraan: kelola bantuan
        Role::findByName('petugas-kesejahteraan')->syncPermissions([
            'view-documents', 'create-documents', 'edit-documents',
            'delete-documents', 'restore-documents',
        ]);

        // Kasi Pemerintahan: kelola data penduduk
        Role::findByName('petugas-pemerintahan')->syncPermissions([
            'view-documents', 'create-documents', 'edit-documents',
            'delete-documents', 'restore-documents',
        ]);

        // ── Akun Default (5 Gmail) ────────────────────────────────────────────

        // 1. Admin
        $this->createUser(
            'Admin Sistem',
            'admin@sukarami-desa.id',
            'Administrator Sistem',
            'admin'
        );

        // 2. Kepala Desa
        $this->createUser(
            'Sumadi',
            'kades@sukarami-desa.id',
            'Kepala Desa',
            'kepala-desa'
        );

        // 3. Sekdes + TU + Operator (1 akun bersama)
        $this->createUser(
            'Herman Diansyah',
            'sekdes@sukarami-desa.id',
            'Sekretaris Desa / TU / Operator',
            'sekdes-operator'
        );

        // 4. Kasi Kesejahteraan — Penerimaan Bantuan
        $this->createUser(
            'Robi Merdus',
            'kesejahteraan@sukarami-desa.id',
            'Kasi Kesejahteraan',
            'petugas-kesejahteraan'
        );

        // 5. Kasi Pemerintahan — Data Penduduk
        $this->createUser(
            'Desri Jhadi',
            'pemerintahan@sukarami-desa.id',
            'Kasi Pemerintahan',
            'petugas-pemerintahan'
        );
    }

    private function createUser(string $name, string $email, string $jabatan, string $role): void
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'jabatan'           => $jabatan,
                'password'          => bcrypt('password'),
                'locale'            => 'id',
                'theme'             => 'light',
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
