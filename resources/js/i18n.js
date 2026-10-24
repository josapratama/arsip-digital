import { reactive } from "vue";

// ── Single source of truth for locale ────────────────────────────────────────
// Priority: localStorage > user DB preference > default 'id'
// Never overridden by Inertia props after user explicitly sets it.
const storedLocale = localStorage.getItem("locale");
const initialLocale =
    storedLocale && ["id", "en"].includes(storedLocale) ? storedLocale : "id";

export const i18nState = reactive({ locale: initialLocale });

// ── Translations ──────────────────────────────────────────────────────────────
const messages = {
    id: {
        nav: {
            dashboard: "Dasbor",
            documents: "Dokumen",
            categories: "Kategori",
            recycle_bin: "Sampah",
            activity_log: "Riwayat Aktivitas",
            settings: "Pengaturan",
            profile: "Profil",
            logout: "Keluar",
        },
        dashboard: {
            title: "Dasbor",
            total_documents: "Total Dokumen",
            categories: "Kategori",
            this_month: "Bulan Ini",
            recycle_bin: "Sampah",
            recent_documents: "Dokumen Terbaru",
            recent_activity: "Aktivitas Terbaru",
            category_overview: "Ringkasan Kategori",
            view_all: "Lihat Semua",
            no_documents: "Belum ada dokumen.",
        },
        documents: {
            title: "Arsip Dokumen",
            upload: "Unggah Dokumen",
            search_placeholder: "Cari isi dokumen...",
            filter: "Filter",
            all_categories: "Semua Kategori",
            no_results: "Dokumen tidak ditemukan.",
            title_label: "Judul Dokumen",
            nomor_surat: "Nomor Surat",
            category: "Kategori",
            subcategory: "Subkategori",
            date: "Tanggal Dokumen",
            description: "Keterangan",
            file: "File",
            ocr_text: "Teks OCR",
            ocr_result: "Hasil Ekstraksi Teks (OCR)",
            process_ocr: "Proses OCR",
            processing: "Memproses...",
            edit: "Edit",
            delete: "Hapus",
            download: "Unduh",
            back: "Kembali",
            save: "Simpan",
            cancel: "Batal",
            uploaded_by: "Diunggah oleh",
            upload_date: "Tanggal Unggah",
            file_size: "Ukuran File",
            ocr_processed: "OCR Diproses",
            yes: "Ya",
            no: "Belum",
        },
        recycle_bin: {
            title: "Recycle Bin",
            empty: "Recycle bin kosong.",
            restore: "Pulihkan",
            delete_permanent: "Hapus Permanen",
            confirm_delete:
                "Yakin ingin menghapus permanen? Tindakan ini tidak dapat dibatalkan.",
        },
        activity: {
            title: "Riwayat Aktivitas",
            action: "Aksi",
            description: "Keterangan",
            user: "Pengguna",
            date: "Waktu",
            actions: {
                upload: "Unggah",
                edit: "Edit",
                delete: "Hapus",
                restore: "Pulihkan",
                view: "Lihat",
                login: "Masuk",
                logout: "Keluar",
                ocr: "OCR",
            },
        },
        profile: {
            title: "Profil Saya",
            name: "Nama Lengkap",
            jabatan: "Jabatan",
            email: "Surel",
            language: "Bahasa",
            theme: "Tema",
            light: "Terang",
            dark: "Gelap",
            save: "Simpan Perubahan",
            change_password: "Ubah Kata Sandi",
            current_password: "Kata Sandi Saat Ini",
            new_password: "Kata Sandi Baru",
            confirm_password: "Konfirmasi Kata Sandi",
        },
        auth: {
            login: "Masuk",
            email: "Surel",
            password: "Kata Sandi",
            remember: "Ingat Saya",
            forgot_password: "Lupa Kata Sandi?",
            welcome: "Selamat Datang",
            subtitle: "Sistem Pengarsipan Digital",
            desa: "Desa Sukarami, Kec. Rambang, Kab. Muara Enim",
        },
        common: {
            loading: "Memuat...",
            save: "Simpan",
            cancel: "Batal",
            close: "Tutup",
            confirm: "Konfirmasi",
            search: "Cari",
            filter: "Filter",
            reset: "Reset",
            actions: "Aksi",
            status: "Status",
            date: "Tanggal",
            name: "Nama",
            description: "Keterangan",
            no_data: "Tidak ada data.",
            per_page: "per halaman",
        },
    },
    en: {
        nav: {
            dashboard: "Dashboard",
            documents: "Documents",
            categories: "Categories",
            recycle_bin: "Recycle Bin",
            activity_log: "Activity Log",
            settings: "Settings",
            profile: "Profile",
            logout: "Logout",
        },
        dashboard: {
            title: "Dashboard",
            total_documents: "Total Documents",
            categories: "Categories",
            this_month: "This Month",
            recycle_bin: "Recycle Bin",
            recent_documents: "Recent Documents",
            recent_activity: "Recent Activity",
            category_overview: "Category Overview",
            view_all: "View All",
            no_documents: "No documents yet.",
        },
        documents: {
            title: "Document Archive",
            upload: "Upload Document",
            search_placeholder: "Search document content...",
            filter: "Filter",
            all_categories: "All Categories",
            no_results: "No documents found.",
            title_label: "Document Title",
            nomor_surat: "Document Number",
            category: "Category",
            subcategory: "Subcategory",
            date: "Document Date",
            description: "Description",
            file: "File",
            ocr_text: "OCR Text",
            ocr_result: "Extracted Text (OCR)",
            process_ocr: "Process OCR",
            processing: "Processing...",
            edit: "Edit",
            delete: "Delete",
            download: "Download",
            back: "Back",
            save: "Save",
            cancel: "Cancel",
            uploaded_by: "Uploaded by",
            upload_date: "Upload Date",
            file_size: "File Size",
            ocr_processed: "OCR Processed",
            yes: "Yes",
            no: "No",
        },
        recycle_bin: {
            title: "Recycle Bin",
            empty: "Recycle bin is empty.",
            restore: "Restore",
            delete_permanent: "Delete Permanently",
            confirm_delete:
                "Are you sure you want to permanently delete? This action cannot be undone.",
        },
        activity: {
            title: "Activity Log",
            action: "Action",
            description: "Description",
            user: "User",
            date: "Date",
            actions: {
                upload: "Upload",
                edit: "Edit",
                delete: "Delete",
                restore: "Restore",
                view: "View",
                login: "Login",
                logout: "Logout",
                ocr: "OCR",
            },
        },
        profile: {
            title: "My Profile",
            name: "Full Name",
            jabatan: "Position",
            email: "Email",
            language: "Language",
            theme: "Theme",
            light: "Light",
            dark: "Dark",
            save: "Save Changes",
            change_password: "Change Password",
            current_password: "Current Password",
            new_password: "New Password",
            confirm_password: "Confirm Password",
        },
        auth: {
            login: "Sign In",
            email: "Email",
            password: "Password",
            remember: "Remember Me",
            forgot_password: "Forgot Password?",
            welcome: "Welcome",
            subtitle: "Digital Archiving System",
            desa: "Sukarami Village, Rambang District, Muara Enim",
        },
        common: {
            loading: "Loading...",
            save: "Save",
            cancel: "Cancel",
            close: "Close",
            confirm: "Confirm",
            search: "Search",
            filter: "Filter",
            reset: "Reset",
            actions: "Actions",
            status: "Status",
            date: "Date",
            name: "Name",
            description: "Description",
            no_data: "No data available.",
            per_page: "per page",
        },
    },
};

// t() for use with key paths (e.g. $t('nav.dashboard'))
export function t(key) {
    const keys = key.split(".");
    let val = messages[i18nState.locale];
    for (const k of keys) {
        if (val === undefined) return key;
        val = val[k];
    }
    return val ?? key;
}

// i18n Vue plugin — installs $t and $i18n globally
// NO router.on("finish") here — that was the bug that kept resetting locale
export const i18n = {
    install(app) {
        app.config.globalProperties.$t = t;
        app.config.globalProperties.$i18n = i18nState;
    },
};
