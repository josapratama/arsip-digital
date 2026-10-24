<?php

namespace App\Services;

use App\Models\Category;

class KlasifikasiKemendagriService
{
    /**
     * Daftar Standar Kode Klasifikasi Surat Dinas Pemerintahan Desa (Permendagri)
     */
    public const KLASIFIKASI_DESA = [
        // ── 000 : UMUM ────────────────────────────────────────────────────────
        ['kode' => '005', 'nama' => 'Undangan / Rapat / Pertemuan Dinas', 'bidang' => 'Umum'],
        ['kode' => '010', 'nama' => 'Tata Usaha / Berita Acara / Notulen', 'bidang' => 'Umum'],
        ['kode' => '020', 'nama' => 'Peralatan / Perlengkapan / Inventaris Desa', 'bidang' => 'Umum'],
        ['kode' => '040', 'nama' => 'Perpustakaan / Dokumentasi Desa', 'bidang' => 'Umum'],

        // ── 100 : PEMERINTAHAN ────────────────────────────────────────────────
        ['kode' => '140', 'nama' => 'Pemerintahan Desa / Kelurahan', 'bidang' => 'Pemerintahan'],
        ['kode' => '141', 'nama' => 'Kepala Desa & Perangkat Desa (Pengangkatan/Pemberhentian)', 'bidang' => 'Pemerintahan'],
        ['kode' => '142', 'nama' => 'Badan Permusyawaratan Desa (BPD)', 'bidang' => 'Pemerintahan'],
        ['kode' => '143', 'nama' => 'Lembaga Kemasyarakatan Desa (RT/RW, Karang Taruna, LPM)', 'bidang' => 'Pemerintahan'],
        ['kode' => '146', 'nama' => 'Peraturan Desa (Perdes) & Peraturan Bersama', 'bidang' => 'Pemerintahan'],
        ['kode' => '180', 'nama' => 'Hukum / Surat Keputusan (SK) / Surat Perdamaian', 'bidang' => 'Pemerintahan'],

        // ── 200 : POLITIK ─────────────────────────────────────────────────────
        ['kode' => '270', 'nama' => 'Pemilihan Kepala Desa (Pilkades) / Pemilu', 'bidang' => 'Politik'],

        // ── 300 : KEAMANAN DAN KETERTIBAN ─────────────────────────────────────
        ['kode' => '330', 'nama' => 'Ketertiban Umum & Perlindungan Masyarakat (Satlinmas)', 'bidang' => 'Keamanan'],
        ['kode' => '332', 'nama' => 'Penanggulangan Bencana Alam / Kebakaran', 'bidang' => 'Keamanan'],

        // ── 400 : KESEJAHTERAAN RAKYAT ────────────────────────────────────────
        ['kode' => '400.1', 'nama' => 'Bantuan Sosial (PKH, BPNT, BLT Desa, Bansos)', 'bidang' => 'Kesra'],
        ['kode' => '440', 'nama' => 'Kesehatan Masyarakat, Posyandu, & Pencegahan Stunting', 'bidang' => 'Kesra'],
        ['kode' => '450', 'nama' => 'Keagamaan, Peribadatan, & Hari Besar Islam', 'bidang' => 'Kesra'],
        ['kode' => '470', 'nama' => 'Administrasi Kependudukan (Pengantar KK/KTP, Pindah, Kematian)', 'bidang' => 'Kesra'],

        // ── 500 : PEREKONOMIAN ────────────────────────────────────────────────
        ['kode' => '510', 'nama' => 'Perdagangan & Pasar Desa', 'bidang' => 'Perekonomian'],
        ['kode' => '520', 'nama' => 'Pertanian, Perkebunan, & Ketahanan Pangan Desa', 'bidang' => 'Perekonomian'],
        ['kode' => '580', 'nama' => 'Badan Usaha Milik Desa (BUMDes) & Koperasi', 'bidang' => 'Perekonomian'],

        // ── 600 : PEKERJAAN UMUM ──────────────────────────────────────────────
        ['kode' => '600', 'nama' => 'Pembangunan Sarana Prasarana / Jalan Desa / Fisik', 'bidang' => 'Pekerjaan Umum'],

        // ── 700 : PENGAWASAN ──────────────────────────────────────────────────
        ['kode' => '700', 'nama' => 'Laporan Hasil Pemeriksaan / Audit Inspektorat', 'bidang' => 'Pengawasan'],

        // ── 800 : KEPEGAWAIAN ─────────────────────────────────────────────────
        ['kode' => '800', 'nama' => 'Administrasi Pegawai Desa / Cuti / Disiplin', 'bidang' => 'Kepegawaian'],

        // ── 900 : KEUANGAN ────────────────────────────────────────────────────
        ['kode' => '900', 'nama' => 'Anggaran Pendapatan dan Belanja Desa (APBDes)', 'bidang' => 'Keuangan'],
        ['kode' => '910', 'nama' => 'Pengelolaan Kas Desa, SPJ, & Pajak', 'bidang' => 'Keuangan'],
        ['kode' => '950', 'nama' => 'Laporan Pertanggungjawaban Keuangan (LPJ Desa)', 'bidang' => 'Keuangan'],
    ];

    /**
     * Dapatkan semua daftar klasifikasi Kemendagri
     */
    public function getAll(): array
    {
        return self::KLASIFIKASI_DESA;
    }

    /**
     * Analisis teks dan nomor surat untuk merekomendasikan Kode Kemendagri & Kategori Desa
     */
    public function suggest(string $text, ?string $title = null, ?string $nomorSurat = null): ?array
    {
        $haystack = strtolower(($nomorSurat ?? '') . ' ' . ($title ?? '') . ' ' . $text);
        if (strlen(trim($haystack)) < 3) {
            return null;
        }

        // 1. Ekstraksi langsung dari pola nomor surat resmi Kemendagri (contoh: 400.1.2.2/... atau 140/01/...)
        if (!empty($nomorSurat)) {
            if (preg_match('/^\s*(400(?:\.1)?|005|010|020|140|141|142|143|146|180|270|330|332|440|450|470|510|520|580|600|700|800|900|910|950)\b/i', $nomorSurat, $m)) {
                return $this->buildRecommendation($m[1], 'Nomor Surat Kedinasan');
            }
        }

        // 2. Deteksi Berdasarkan Kata Kunci Utama
        $rules = [
            // Bantuan Sosial (PKH, BPNT, BLT)
            '400.1' => ['pkh', 'bpnt', 'blt', 'bantuan sosial', 'bansos', 'penerima bantuan', 'keluarga harapan', 'sembako', 'pangan murah'],
            // Kependudukan
            '470' => ['kependudukan', 'ktp', 'kartu keluarga', 'surat pindah', 'surat kematian', 'surat kelahiran', 'domisili', 'daftar warga', 'dusun 1', 'dusun 2', 'dusun 3'],
            // Kesehatan & Stunting
            '440' => ['posyandu', 'stunting', 'puskesmas', 'kesehatan', 'imunisasi', 'balita', 'ibu hamil'],
            // Undangan
            '005' => ['undangan', 'rapat', 'musyawarah desa', 'musdes', 'undangan rapat', 'pertemuan'],
            // SK / Keputusan Kades
            '180' => ['surat keputusan', 'sk kepala desa', 'sk kades', 'mengangkat', 'memberhentikan', 'penetapan', 'memutuskan', 'perdamaian', 'kesepakatan damai', 'sengketa'],
            // Berita Acara
            '010' => ['berita acara', 'notulen', 'notula', 'tata usaha', 'kegiatan desa', 'pelaksanaan'],
            // Proposal
            '010_prop' => ['proposal', 'pengajuan dana', 'permohonan bantuan dana', 'proposal permohonan'],
            // Keuangan / APBDes
            '900' => ['apbdes', 'anggaran pendapatan', 'dana desa', 'realisasi anggaran', 'kas desa', 'spj', 'pajak', 'lpj'],
            // Pertanian / Ketahanan Pangan
            '520' => ['pertanian', 'ketahanan pangan', 'perkebunan', 'panen', 'irigasi desa', 'petani'],
            // BUMDes
            '580' => ['bumdes', 'badan usaha milik desa', 'unit usaha desa'],
        ];

        foreach ($rules as $code => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($haystack, $kw)) {
                    $targetCode = ($code === '010_prop') ? '010' : $code;
                    return $this->buildRecommendation($targetCode, "Kata Kunci '{$kw}'");
                }
            }
        }

        return null;
    }

    /**
     * Susun objek rekomendasi lengkap dengan pemetaan Kategori database arsip digital
     */
    protected function buildRecommendation(string $kode, string $alasan = ''): array
    {
        // Cari metadata kode
        $meta = collect(self::KLASIFIKASI_DESA)->firstWhere('kode', $kode) ?: [
            'kode' => $kode,
            'nama' => "Klasifikasi {$kode}",
            'bidang' => 'Pemerintahan Desa',
        ];

        // Petakan ke kategori yang ada di database Desa Sukarami
        $catMapping = match ($kode) {
            '400.1' => ['category_slug' => 'penerimaan-bantuan', 'subcat_slug' => null],
            '470'   => ['category_slug' => 'dokumen-data-penduduk', 'subcat_slug' => null],
            '005'   => ['category_slug' => 'pengarsipan', 'subcat_slug' => 'surat-masuk-dan-keluar'],
            '180'   => ['category_slug' => 'pengarsipan', 'subcat_slug' => 'sk-surat-keputusan'],
            '010'   => ['category_slug' => 'pengarsipan', 'subcat_slug' => 'surat-berita-acara'],
            default => ['category_slug' => 'pengarsipan', 'subcat_slug' => null],
        };

        $category = Category::where('slug', $catMapping['category_slug'])->first();
        $subcategory = !empty($catMapping['subcat_slug'])
            ? Category::where('slug', $catMapping['subcat_slug'])->where('parent_id', $category?->id)->first()
            : null;

        return [
            'kode' => $meta['kode'],
            'nama' => $meta['nama'],
            'bidang' => $meta['bidang'],
            'alasan' => $alasan,
            'category_id' => $category?->id,
            'subcategory_id' => $subcategory?->id,
            'category_name' => $category?->name,
            'subcategory_name' => $subcategory?->name,
        ];
    }
}
