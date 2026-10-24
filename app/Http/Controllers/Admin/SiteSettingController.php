<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SiteSettingController extends Controller
{
    public function index()
    {
        // Send as flat array keyed by 'key' — simple and predictable
        $settings = SiteSetting::orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn($s) => [
                $s->key => [
                    'id'    => $s->id,
                    'key'   => $s->key,
                    'value' => $s->value,
                    'type'  => $s->type,
                    'group' => $s->group,
                    'label' => $s->label,
                ]
            ]);

        return Inertia::render('Admin/SiteSettings', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'updates'         => 'required|array',
            'updates.*.key'   => 'required|string|exists:site_settings,key',
            'updates.*.value' => 'nullable',
        ]);

        foreach ($request->updates as $item) {
            SiteSetting::where('key', $item['key'])
                ->update(['value' => $item['value'] ?? '']);
        }

        SiteSetting::clearCache();
        \App\Models\ActivityLog::record('settings', 'Memperbarui pengaturan halaman website');

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'key'   => 'required|string|exists:site_settings,key',
        ]);

        $old = SiteSetting::where('key', $request->key)->value('value');
        if ($old && Storage::disk('public')->exists($old)) {
            Storage::disk('public')->delete($old);
        }

        $path = $request->file('image')->store('site', 'public');
        SiteSetting::where('key', $request->key)->update(['value' => $path]);
        SiteSetting::clearCache();

        \App\Models\ActivityLog::record('settings', "Mengunggah gambar pengaturan: {$request->key}");

        return response()->json([
            'success' => true,
            'path'    => $path,
            'url'     => asset('storage/' . $path),
        ]);
    }

    public function updateJson(Request $request)
    {
        $request->validate([
            'key'   => 'required|string|exists:site_settings,key',
            'value' => 'required|array',
        ]);

        SiteSetting::where('key', $request->key)
            ->update(['value' => json_encode($request->value, JSON_UNESCAPED_UNICODE)]);
        SiteSetting::clearCache();

        \App\Models\ActivityLog::record('settings', "Memperbarui konfigurasi: {$request->key}");

        return back()->with('success', 'Data berhasil diperbarui.');
    }
}
