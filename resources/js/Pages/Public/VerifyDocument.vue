<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import {
    CheckCircle2,
    XCircle,
    FileText,
    Calendar,
    Building2,
    ShieldCheck,
    Hash,
    User,
    ArrowLeft,
    Clock,
    Tag,
    Globe,
    Sun,
    Moon,
} from "lucide-vue-next";
import { useI18n, setLocale } from "@/useI18n.js";

const props = defineProps({
    valid: Boolean,
    code: String,
    document: Object,
    desa: Object,
    enable_qr: Boolean,
});

const { t, locale } = useI18n();

const isDark = ref(document.documentElement.classList.contains("dark"));
function toggleTheme() {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle("dark", isDark.value);
    localStorage.setItem("theme", isDark.value ? "dark" : "light");
}

function toggleLocale() {
    setLocale(locale.value === "id" ? "en" : "id");
}
</script>

<template>
    <Head :title="valid ? `${t('Verifikasi', 'Verification')}: ${document.title}` : t('Dokumen Tidak Ditemukan', 'Document Not Found')" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 flex flex-col justify-between py-6 px-4 sm:px-6">
        <div class="max-w-2xl mx-auto w-full">
            <!-- Top Controls (Language & Theme) -->
            <div class="flex items-center justify-between mb-6">
                <Link
                    :href="route('landing')"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-emerald-600 transition-colors shadow-xs"
                >
                    <ArrowLeft class="w-3.5 h-3.5" />
                    {{ t("Beranda Desa", "Village Home") }}
                </Link>

                <div class="flex items-center gap-2">
                    <button
                        @click="toggleLocale"
                        :title="t('Ganti Bahasa', 'Change Language')"
                        class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-emerald-500 transition-colors shadow-xs font-mono"
                    >
                        <Globe class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                        <span>{{ locale.toUpperCase() }}</span>
                    </button>

                    <button
                        @click="toggleTheme"
                        class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur text-slate-600 dark:text-slate-300 hover:text-emerald-600 transition-colors shadow-xs"
                    >
                        <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                        <Moon v-else class="w-4 h-4" />
                    </button>
                </div>
            </div>
            <!-- Village Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-emerald-600 text-white shadow-lg shadow-emerald-600/20 mb-3">
                    <ShieldCheck class="w-8 h-8" />
                </div>
                <h2 class="text-xs font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">
                    Sistem Arsip Digital Resmi
                </h2>
                <h1 class="text-xl sm:text-2xl font-black text-slate-900 dark:text-white mt-1">
                    {{ desa?.nama || "Pemerintah Desa Sukarami" }}
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                    Kecamatan {{ desa?.kecamatan || "Rambang" }}, Kabupaten {{ desa?.kabupaten || "Muara Enim" }}
                </p>
            </div>

            <!-- Card: Valid Document -->
            <div
                v-if="valid && document"
                class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-200/80 dark:border-slate-700 shadow-xl overflow-hidden"
            >
                <!-- Verification status header -->
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-white flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0">
                        <CheckCircle2 class="w-6 h-6 text-white" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold tracking-tight">
                            {{ t("DOKUMEN RESMI TERVERIFIKASI SAH", "OFFICIALLY VERIFIED DOCUMENT") }}
                        </h3>
                        <p class="text-xs text-emerald-100">
                            {{ t("Dokumen ini tercatat dalam pangkalan data arsip digital desa.", "This document is officially registered in the village digital archive database.") }}
                        </p>
                    </div>
                </div>

                <!-- Document Details -->
                <div class="p-6 space-y-5">
                    <div>
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            {{ t("Judul Dokumen", "Document Title") }}
                        </span>
                        <h4 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mt-0.5 leading-snug">
                            {{ document.title }}
                        </h4>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100 dark:border-slate-700/60">
                        <!-- Nomor Surat -->
                        <div class="flex items-start gap-2.5">
                            <Hash class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <div>
                                <span class="text-[11px] text-slate-400 block">{{ t("Nomor Surat", "Letter Number") }}</span>
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200 font-mono">
                                    {{ document.document_number || document.nomor_surat || "-" }}
                                </span>
                            </div>
                        </div>

                        <!-- Tanggal Surat -->
                        <div class="flex items-start gap-2.5">
                            <Calendar class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <div>
                                <span class="text-[11px] text-slate-400 block">{{ t("Tanggal Dokumen", "Document Date") }}</span>
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    {{ document.document_date || "-" }}
                                </span>
                            </div>
                        </div>

                        <!-- Kategori -->
                        <div class="flex items-start gap-2.5">
                            <Tag class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" />
                            <div>
                                <span class="text-[11px] text-slate-400 block">{{ t("Kategori Arsip", "Archive Category") }}</span>
                                <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                    {{ document.category_name }}
                                    <span v-if="document.subcategory_name" class="text-slate-400">
                                        &rsaquo; {{ document.subcategory_name }}
                                    </span>
                                </span>
                            </div>
                        </div>

                        <!-- Kode Klasifikasi Kemendagri -->
                        <div v-if="document.classification_code || document.kode_klasifikasi" class="flex items-start gap-2.5">
                            <Building2 class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                            <div>
                                <span class="text-[11px] text-slate-400 block">{{ t("Kode Klasifikasi Kemendagri", "Kemendagri Code") }}</span>
                                <span class="inline-block px-2 py-0.5 rounded bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300 font-mono font-bold text-xs">
                                    {{ document.classification_code || document.kode_klasifikasi }}
                                </span>
                            </div>
                        </div>

                        <!-- Waktu Pengarsipan -->
                        <div class="flex items-start gap-2.5">
                            <Clock class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                            <div>
                                <span class="text-[11px] text-slate-400 block">{{ t("Tanggal Arsip Digital", "Archived Date") }}</span>
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                    {{ document.created_at }}
                                </span>
                            </div>
                        </div>

                        <!-- Petugas Pengunggah -->
                        <div class="flex items-start gap-2.5">
                            <User class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" />
                            <div>
                                <span class="text-[11px] text-slate-400 block">{{ t("Petugas Pengunggah", "Archived By") }}</span>
                                <span class="text-xs font-medium text-slate-600 dark:text-slate-300">
                                    {{ document.uploader_name || "Sekretariat Desa" }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Kode Unik Verifikasi -->
                    <div class="mt-4 p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700/80 flex items-center justify-between gap-3">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">
                                {{ t("Kode Verifikasi Dokumen", "Verification Token") }}
                            </span>
                            <span class="text-xs font-mono font-bold text-emerald-700 dark:text-emerald-400 select-all">
                                {{ document.verification_code }}
                            </span>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300">
                            {{ t("Tervalidasi", "Validated") }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card: Invalid / Not Found -->
            <div
                v-else
                class="bg-white dark:bg-slate-800 rounded-3xl border border-red-200 dark:border-red-800/60 shadow-xl overflow-hidden p-8 text-center"
            >
                <div class="w-16 h-16 rounded-2xl bg-red-100 dark:bg-red-950/40 text-red-600 dark:text-red-400 flex items-center justify-center mx-auto mb-4">
                    <XCircle class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                    {{ t("Dokumen Tidak Ditemukan", "Document Not Found") }}
                </h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-2 max-w-md mx-auto leading-relaxed">
                    {{ t("Kode verifikasi yang dipindai tidak terdaftar dalam pangkalan data arsip desa kami atau dokumen telah dipindahkan ke tempat sampah.", "The scanned verification code is not found in our village archive database or has been deleted.") }}
                </p>
                <div v-if="code" class="mt-4 inline-block px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-900 text-xs font-mono text-slate-600 dark:text-slate-300">
                    Token: {{ code }}
                </div>
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-6">
                <Link
                    :href="route('landing')"
                    class="inline-flex items-center gap-1.5 text-xs text-slate-500 hover:text-emerald-600 transition-colors font-medium"
                >
                    <ArrowLeft class="w-3.5 h-3.5" />
                    {{ t("Kembali ke Beranda Desa", "Back to Village Home") }}
                </Link>
            </div>
        </div>

        <!-- Footer Notice -->
        <footer class="text-center text-[11px] text-slate-400 dark:text-slate-500 mt-8">
            &copy; {{ new Date().getFullYear() }} Pemerintah Desa Sukarami. {{ t("Hak Cipta Dilindungi.", "All rights reserved.") }}
        </footer>
    </div>
</template>
