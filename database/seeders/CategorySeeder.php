<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // ── Pengarsipan (Sekdes/TU) ─────────────────────────────────────
            [
                'name'     => 'Pengarsipan',
                'name_en'  => 'Archiving',
                'icon'     => 'folder-archive',
                'color'    => 'blue',
                'children' => [
                    ['name' => 'SK (Surat Keputusan)',    'name_en' => 'Decree Letters'],
                    ['name' => 'Surat Masuk dan Keluar',  'name_en' => 'Incoming & Outgoing Letters'],
                    ['name' => 'Surat Berita Acara',      'name_en' => 'Minutes of Meeting'],
                ],
            ],

            // ── Dokumen Proposal Keluar (Sekdes/TU) ─────────────────────────
            [
                'name'     => 'Dokumen Proposal Keluar',
                'name_en'  => 'Outgoing Proposals',
                'icon'     => 'file-output',
                'color'    => 'orange',
                'children' => [],
            ],

            // ── Surat Perdamaian (Sekdes/TU) ────────────────────────────────
            [
                'name'     => 'Surat Perdamaian',
                'name_en'  => 'Peace Letters',
                'icon'     => 'handshake',
                'color'    => 'teal',
                'children' => [],
            ],

            // ── Informasi Kegiatan Desa (Sekdes/TU) ─────────────────────────
            [
                'name'     => 'Informasi Kegiatan Desa',
                'name_en'  => 'Village Activity Information',
                'icon'     => 'megaphone',
                'color'    => 'yellow',
                'children' => [],
            ],

            // ── Penerimaan Bantuan (Kasi Kesejahteraan) ─────────────────────
            [
                'name'     => 'Penerimaan Bantuan',
                'name_en'  => 'Aid Receipts',
                'icon'     => 'hand-heart',
                'color'    => 'green',
                'children' => [
                    ['name' => 'PKH',    'name_en' => 'PKH (Family Hope Program)'],
                    ['name' => 'BPNT',   'name_en' => 'BPNT (Non-Cash Food Aid)'],
                    ['name' => 'Bansos', 'name_en' => 'Social Assistance'],
                ],
            ],

            // ── Dokumen Data Penduduk (Kasi Pemerintahan) ───────────────────
            [
                'name'     => 'Dokumen Data Penduduk',
                'name_en'  => 'Resident Data Documents',
                'icon'     => 'users',
                'color'    => 'purple',
                'children' => [
                    ['name' => 'Dusun 1', 'name_en' => 'Hamlet 1'],
                    ['name' => 'Dusun 2', 'name_en' => 'Hamlet 2'],
                    ['name' => 'Dusun 3', 'name_en' => 'Hamlet 3'],
                ],
            ],
        ];

        foreach ($categories as $order => $data) {
            $children = $data['children'];
            unset($data['children']);

            // Skip if already exists
            if (Category::where('slug', Str::slug($data['name']))->exists()) {
                continue;
            }

            $parent = Category::create([
                'name'       => $data['name'],
                'name_en'    => $data['name_en'],
                'slug'       => Str::slug($data['name']),
                'icon'       => $data['icon'],
                'color'      => $data['color'],
                'sort_order' => $order,
                'parent_id'  => null,
            ]);

            foreach ($children as $childOrder => $child) {
                Category::firstOrCreate(
                    ['slug' => Str::slug($child['name'])],
                    [
                        'name'       => $child['name'],
                        'name_en'    => $child['name_en'],
                        'icon'       => null,
                        'color'      => $data['color'],
                        'sort_order' => $childOrder,
                        'parent_id'  => $parent->id,
                    ]
                );
            }
        }
    }
}
