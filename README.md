# Arsip Digital Desa Sukarami

Sistem Pengarsipan Digital berbasis Smart Web dengan OCR dan Full-Text Search untuk Kantor Kepala Desa Sukarami, Kecamatan Rambang, Kabupaten Muara Enim.

## Stack Teknologi

| Layer            | Teknologi                     |
| ---------------- | ----------------------------- |
| Backend          | Laravel 13 (PHP 8.4+)         |
| Runtime          | FrankenPHP                    |
| Frontend         | Vue.js 3 + Inertia.js v3      |
| CSS              | Tailwind CSS v4               |
| Database         | PostgreSQL 16                 |
| OCR              | Google Cloud Vision API       |
| Full-Text Search | PostgreSQL tsvector / tsquery |
| Container        | Docker + Docker Compose       |

## Cara Menjalankan (Pertama Kali)

### 1. Clone / salin project

```bash
cd d:/coding/tiwi/arsip_digital
```

### 2. Salin file environment

```bash
copy .env.example .env
```

### 3. (Opsional) Isi Google Vision API Key

Edit `.env` dan isi:

```
GOOGLE_CLOUD_VISION_API_KEY=your_api_key_here
```

Tanpa API key, OCR tidak akan berfungsi — fitur lain tetap berjalan normal.

### 4. Build dan jalankan Docker

```bash
docker compose up --build
```

> Build pertama kali membutuhkan waktu ~5–10 menit karena compile PHP extensions dari source.

### 5. Akses aplikasi

Buka browser: **http://localhost**

### Akun Default

| Nama            | Email                     | Password | Role                    |
| --------------- | ------------------------- | -------- | ----------------------- |
| Herman Diansyah | sekdes@sukarami-desa.id   | password | Sekretaris Desa (Admin) |
| Sumadi          | kades@sukarami-desa.id    | password | Kepala Desa             |
| Rusyiti         | operator@sukarami-desa.id | password | TU / Operator           |

> **Ganti password** setelah pertama kali login melalui menu Profil.

## Perintah Berguna

```bash
# Jalankan container
docker compose up -d

# Hentikan container
docker compose down

# Lihat log
docker compose logs -f app

# Masuk ke container
docker compose exec app sh

# Jalankan migrasi manual
docker compose exec app php artisan migrate

# Reset database + seed ulang
docker compose exec app php artisan migrate:fresh --seed
```

## Fitur

- ✅ Dashboard dengan statistik dan ringkasan aktivitas
- ✅ Upload dokumen (JPG, PNG, PDF) dengan drag & drop
- ✅ OCR otomatis via Google Cloud Vision API
- ✅ Koreksi hasil OCR langsung di browser
- ✅ Full-text search berbasis isi dokumen (PostgreSQL tsvector)
- ✅ Filter dokumen (kategori, subkategori, tanggal)
- ✅ Recycle Bin dengan restore dokumen
- ✅ Riwayat aktivitas (audit trail)
- ✅ Multi-bahasa (Bahasa Indonesia & English)
- ✅ Light & Dark mode
- ✅ Responsive (desktop & mobile)
- ✅ Role-based access (Kades, Sekdes, Operator, Petugas)

## Kategori Arsip

1. **Pengarsipan** — SK, Surat Masuk/Keluar, Berita Acara
2. **Penerimaan Bantuan** — PKH, BPNT, Bansos
3. **Dokumen Data Penduduk** — Dusun 1, 2, 3
4. **Dokumen Proposal Keluar**

## Struktur Folder

```
arsip_digital/
├── app/
│   ├── Http/Controllers/     # Dashboard, Document, RecycleBin, dll
│   ├── Models/               # User, Document, Category, ActivityLog
│   └── Services/             # OcrService
├── database/
│   ├── migrations/           # Skema database
│   └── seeders/              # Data awal (user, kategori)
├── resources/
│   ├── css/app.css           # Tailwind CSS v4
│   └── js/
│       ├── Pages/            # Vue pages (Dashboard, Documents, dll)
│       ├── Layouts/          # AppLayout.vue
│       └── i18n.js           # Terjemahan ID/EN
├── routes/
│   ├── web.php               # Route utama
│   ├── auth.php              # Login/logout
│   └── api.php               # OCR preview API
├── Dockerfile                # FrankenPHP + PHP 8.4
├── docker-compose.yml        # App + PostgreSQL + Redis
├── Caddyfile                 # FrankenPHP web server config
└── docker-entrypoint.sh      # Auto migrate + seed saat start
```
