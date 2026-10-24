<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Tests\TestCase;

class DocumentPermissionTest extends TestCase
{
    public function test_kades_cannot_update_or_delete_documents(): void
    {
        $kades = User::where('email', 'kades@sukarami-desa.id')->first();
        $this->assertNotNull($kades);

        $doc = Document::first();
        if ($doc) {
            $this->assertTrue($kades->can('view', $doc));
            $this->assertFalse($kades->can('update', $doc));
            $this->assertFalse($kades->can('delete', $doc));
        }
    }

    public function test_admin_can_update_documents(): void
    {
        $admin = User::where('email', 'admin@sukarami-desa.id')->first();
        $this->assertNotNull($admin);

        $doc = Document::first();
        if ($doc) {
            $this->assertTrue($admin->can('update', $doc));
            $this->assertTrue($admin->can('delete', $doc));
        }
    }
}
