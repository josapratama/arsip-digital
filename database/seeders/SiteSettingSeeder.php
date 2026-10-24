<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // ── HERO ─────────────────────────────────────────────────────────
            ['key' => 'hero_title',    'value' => 'Desa Sukarami',                    'type' => 'text',     'group' => 'hero',    'label' => 'Judul Hero',            'sort_order' => 1],
            ['key' => 'hero_subtitle', 'value' => 'Kecamatan Rambang, Kabupaten Muara Enim, Sumatera Selatan', 'type' => 'text', 'group' => 'hero', 'label' => 'Subtitle Hero', 'sort_order' => 2],
            ['key' => 'hero_tagline',  'value' => 'Bersama Membangun Desa yang Agamis, Maju, dan Sejahtera', 'type' => 'text',  'group' => 'hero',    'label' => 'Tagline Hero',  'sort_order' => 3],
            ['key' => 'hero_image',    'value' => '',                                 'type' => 'image',    'group' => 'hero',    'label' => 'Foto Latar Hero',       'sort_order' => 4],

            // ── GENERAL ───────────────────────────────────────────────────────
            ['key' => 'desa_name',     'value' => 'Desa Sukarami',                   'type' => 'text',     'group' => 'general', 'label' => 'Nama Desa',             'sort_order' => 1],
            ['key' => 'kecamatan',     'value' => 'Rambang',                         'type' => 'text',     'group' => 'general', 'label' => 'Kecamatan',             'sort_order' => 2],
            ['key' => 'kabupaten',     'value' => 'Muara Enim',                      'type' => 'text',     'group' => 'general', 'label' => 'Kabupaten',             'sort_order' => 3],
            ['key' => 'luas_desa',     'value' => '460.000 m²',                      'type' => 'text',     'group' => 'general', 'label' => 'Luas Desa',             'sort_order' => 4],
            ['key' => 'jumlah_dusun',  'value' => '3 Dusun',                         'type' => 'text',     'group' => 'general', 'label' => 'Jumlah Dusun',          'sort_order' => 5],
            ['key' => 'kepala_desa',   'value' => 'Sumadi',                          'type' => 'text',     'group' => 'general', 'label' => 'Nama Kepala Desa',      'sort_order' => 6],
            ['key' => 'foto_kades',    'value' => '',                                'type' => 'image',    'group' => 'general', 'label' => 'Foto Kepala Desa',      'sort_order' => 7],

            // ── BATAS WILAYAH ─────────────────────────────────────────────────
            ['key' => 'batas_utara',   'value' => 'Desa Air Keruh, Kecamatan Rambang',  'type' => 'text', 'group' => 'wilayah', 'label' => 'Batas Utara',  'sort_order' => 1],
            ['key' => 'batas_timur',   'value' => 'Desa Sugihan, Kecamatan Rambang',    'type' => 'text', 'group' => 'wilayah', 'label' => 'Batas Timur',  'sort_order' => 2],
            ['key' => 'batas_selatan', 'value' => 'Desa Sugihan, Kecamatan Rambang',    'type' => 'text', 'group' => 'wilayah', 'label' => 'Batas Selatan','sort_order' => 3],
            ['key' => 'batas_barat',   'value' => 'Desa Tanjung Dalam, Kecamatan Rambang', 'type' => 'text', 'group' => 'wilayah', 'label' => 'Batas Barat', 'sort_order' => 4],

            // ── VISI ──────────────────────────────────────────────────────────
            ['key' => 'visi',          'value' => 'Terwujudnya Masyarakat Desa Sukarami yang Agamis, Maju, Adil, Cerdas, Berbudaya, Berakhlak dan Sejahtera', 'type' => 'textarea', 'group' => 'visi_misi', 'label' => 'Visi Kepala Desa', 'sort_order' => 1],

            // ── MISI ──────────────────────────────────────────────────────────
            ['key' => 'misi', 'value' => json_encode([
                'Mengoptimalkan penyelenggaraan Pemerintahan Desa yang Transparan, Jujur, Adil dengan pengambilan keputusan yang cepat dan benar sesuai dengan JUKNIS, JUKLAK dan Peraturan yang berlaku.',
                'Mewujudkan kondisi masyarakat yang Aman, Tertib, Rukun dan Damai dalam kehidupan bermasyarakat dengan berpegang teguh pada prinsip-prinsip Agama dan Adat Istiadat.',
                'Meningkatkan Profesionalitas dan mengaktifkan seluruh Perangkat Desa serta menjalin kerjasama yang baik dengan BPD dan Lembaga-lembaga Desa.',
                'Mewujudkan terealisasinya segala Anggaran dan Bantuan Desa seperti Dana PKK, Karang Taruna, Lembaga-lembaga Desa dan yang lainnya.',
                'Mengedepankan azas musyawarah mufakat dan kejujuran dalam segala kegiatan perencanaan pelaksanaan Pembangunan Desa.',
                'Mewujudkan Sarana dan Prasarana Desa yang memadai.',
                'Memberdayakan potensi-potensi yang ada dalam masyarakat yang meliputi Pemberdayaan Pemuda dan Pemudi dengan dibawahi Karang Taruna, Pemberdayaan Perempuan dan Pemberdayaan Ekonomi kerakyatan seperti dengan mengaktifkan BUMDes untuk meningkatkan kesejahteraan masyarakat.',
                'Meningkatkan pelayanan Kesehatan masyarakat Desa yang maksimal.',
                'Meningkatkan Kehidupan Desa secara Dinamis dalam segi Keagamaan dan Kebudayaan.',
            ]), 'type' => 'json', 'group' => 'visi_misi', 'label' => 'Misi Kepala Desa', 'sort_order' => 2],

            // ── SEJARAH ───────────────────────────────────────────────────────
            ['key' => 'sejarah_singkat', 'value' => 'Desa Sukarami memiliki sejarah panjang yang bermula sejak tahun 1832. Nama SUKARAMI berasal dari kata "Suka" yang artinya Senang/Suka dan "Rami" yang artinya Ramai, dengan makna Suka/Senang dengan Keramaian. Desa ini telah mengalami beberapa kali perpindahan lokasi sebelum menetap di tempat saat ini di tepi Sungai Toman.', 'type' => 'textarea', 'group' => 'sejarah', 'label' => 'Sejarah Singkat', 'sort_order' => 1],

            ['key' => 'sejarah_lengkap', 'value' => 'Asal mula nama SUKARAMI pada tahun 1832 bernama "DANAU AIR HENEK JEHENG" terletak di Danau Air Henek Jehing di dekat Jambat Sungkai dibawah pimpinan Kerio GEMUNTUR. Pada Tahun 1862 karena penduduk terjangkit penyakit mewabah yang sangat mematikan, sehingga banyak warga yang sulit mendapatkan keturunan. Pada tahun 1862 Masehi berpindah ke dusun Padang Bindu didekat sungai Suban anak sungai Rambang dibawah pimpinan Kerio GEMUNTUR. Pada tahun 1890 berpindah ke dusun Lame dekat Sungai Rambang Mentas/himbe pulau diberi nama Dusun SUKE DANE. Kata SUKE DANE berasal dari bahasa melayu tua, "SUKE" artinya Senang dan "DANE" artinya Berkumpul. Kerie DULKARAP kemudian mengubah nama dusun Sukedane menjadi Sukar Rami, dimana "Sukar" artinya Sulit dan "Rami" artinya Ramai. Pada tahun 1918 karena sering terjadi banjir terus-menerus, KERIO DULKARAP memutuskan pindah ke dusun yang sekarang didekat sungai Toman diberi nama SUKARAMI.', 'type' => 'textarea', 'group' => 'sejarah', 'label' => 'Sejarah Lengkap', 'sort_order' => 2],

            // ── STRUKTUR ORGANISASI ───────────────────────────────────────────
            ['key' => 'struktur_pemerintah', 'value' => json_encode([
                ['jabatan' => 'Kepala Desa',      'nama' => 'Sumadi',           'level' => 1, 'warna' => 'blue'],
                ['jabatan' => 'BPD',              'nama' => 'Samsidi',          'level' => 1, 'warna' => 'cyan'],
                ['jabatan' => 'Sekretaris Desa',  'nama' => 'Herman Diansyah',  'level' => 2, 'warna' => 'blue'],
                ['jabatan' => 'Kaur TU',          'nama' => 'Rusyiti',          'level' => 3, 'warna' => 'orange'],
                ['jabatan' => 'Kaur Keuangan',    'nama' => 'Ahan Febriansyah', 'level' => 3, 'warna' => 'orange'],
                ['jabatan' => 'Kaur Perencanaan', 'nama' => 'Hadi',             'level' => 3, 'warna' => 'orange'],
                ['jabatan' => 'Kasi Pemerintahan','nama' => 'Desri Jhadi',      'level' => 3, 'warna' => 'green'],
                ['jabatan' => 'Kasi Kesejahteraan','nama' => 'Robi Merdus',     'level' => 3, 'warna' => 'green'],
                ['jabatan' => 'Kasi Pelayanan',   'nama' => 'Patra Adrius',     'level' => 3, 'warna' => 'green'],
                ['jabatan' => 'Kepala Dusun 1',   'nama' => 'Radius Purandi',   'level' => 4, 'warna' => 'pink'],
                ['jabatan' => 'Kepala Dusun 2',   'nama' => 'Srs Antono',       'level' => 4, 'warna' => 'pink'],
                ['jabatan' => 'Kepala Dusun 3',   'nama' => 'Rahman',           'level' => 4, 'warna' => 'pink'],
            ]), 'type' => 'json', 'group' => 'struktur', 'label' => 'Struktur Pemerintahan', 'sort_order' => 1],

            // ── SYSTEM ────────────────────────────────────────────────────────
            ['key' => 'enable_registration', 'value' => '0', 'type' => 'boolean', 'group' => 'system', 'label' => 'Izinkan Pendaftaran Pengguna Baru', 'sort_order' => 1],
            ['key' => 'ocr_driver', 'value' => 'local', 'type' => 'text', 'group' => 'system', 'label' => 'Mesin Pembaca Teks (OCR)', 'sort_order' => 2],
            ['key' => 'google_vision_api_key', 'value' => '', 'type' => 'text', 'group' => 'system', 'label' => 'Google Cloud Vision API Key', 'sort_order' => 3],
            ['key' => 'ocr_max_pdf_pages', 'value' => '10', 'type' => 'text', 'group' => 'system', 'label' => 'Maksimal Halaman PDF untuk OCR', 'sort_order' => 4],
            ['key' => 'enable_document_qr', 'value' => '1', 'type' => 'boolean', 'group' => 'system', 'label' => 'Tampilkan QR Code Verifikasi pada Dokumen (Cetak/Unduh/Detail)', 'sort_order' => 5],
        ];

        foreach ($settings as $setting) {
            SiteSetting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
