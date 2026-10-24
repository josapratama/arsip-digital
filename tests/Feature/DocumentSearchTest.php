<?php

namespace Tests\Feature;

use App\Models\Document;
use Tests\TestCase;

class DocumentSearchTest extends TestCase
{
    public function test_document_search_does_not_crash_with_special_characters(): void
    {
        // Test queries that previously crashed with to_tsquery syntax errors
        $specialQueries = ['& | ! ()', 'test & * /', '""', "'''", 'surat & keterangan'];

        foreach ($specialQueries as $query) {
            $results = Document::search($query)->get();
            $this->assertNotNull($results);
        }
    }

    public function test_user_can_export_documents_csv(): void
    {
        $user = \App\Models\User::first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get(route('documents.export'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
