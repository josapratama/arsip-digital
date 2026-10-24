<script setup>
import { ref, computed } from "vue";
import { Link, Head } from "@inertiajs/vue3";
import {
    MapPin,
    Users,
    ChevronDown,
    ChevronRight,
    Menu,
    X,
    FolderArchive,
    ExternalLink,
    Target,
    BookOpen,
    Sun,
    Moon,
    Globe,
    Compass,
    Calendar,
    Layers,
    ArrowUpRight,
    Sparkles,
    UserPlus,
} from "lucide-vue-next";
import { useI18n, setLocale } from "@/useI18n.js";

const props = defineProps({ settings: Object });

const { t, locale } = useI18n();

const isDark = ref(document.documentElement.classList.contains("dark"));

function toggleTheme() {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle("dark", isDark.value);
    localStorage.setItem("theme", isDark.value ? "dark" : "light");
}

function toggleLocale() {
    const next = locale.value === "id" ? "en" : "id";
    setLocale(next);
}

const s = computed(() => {
    const raw = props.settings ?? {};
    const get = (key, fallback = "") =>
        raw[key] !== undefined && raw[key] !== null && raw[key] !== ""
            ? raw[key]
            : fallback;
    return {
        heroTitle: get("hero_title", "Desa Sukarami"),
        heroSubtitle: get(
            "hero_subtitle",
            "Kecamatan Rambang, Kabupaten Muara Enim"
        ),
        heroTagline: get(
            "hero_tagline",
            "Bersama Membangun Desa yang Agamis, Maju, dan Sejahtera"
        ),
        heroImage: get("hero_image"),
        desaName: get("desa_name", "Desa Sukarami"),
        kecamatan: get("kecamatan", "Rambang"),
        kabupaten: get("kabupaten", "Muara Enim"),
        luasDesa: get("luas_desa", "460.000 m²"),
        jumlahDusun: get("jumlah_dusun", "3 Dusun"),
        kepalaDesa: get("kepala_desa", "Sumadi"),
        fotoKades: get("foto_kades"),
        batasUtara: get("batas_utara"),
        batasTimur: get("batas_timur"),
        batasSelatan: get("batas_selatan"),
        batasBarat: get("batas_barat"),
        visi: get("visi"),
        misi: Array.isArray(raw["misi"]) ? raw["misi"] : [],
        sejarahSingkat: get("sejarah_singkat"),
        sejarahLengkap: get("sejarah_lengkap"),
        struktur: Array.isArray(raw["struktur_pemerintah"])
            ? raw["struktur_pemerintah"]
            : [],
    };
});

const mobileMenuOpen = ref(false);
const showFullSejarah = ref(false);

const navLinks = computed(() => [
    { id: "beranda", label: t("Beranda", "Home") },
    { id: "profil", label: t("Profil Desa", "Village Profile") },
    { id: "sejarah", label: t("Sejarah", "History") },
    { id: "visi-misi", label: t("Visi & Misi", "Vision & Mission") },
    { id: "struktur", label: t("Struktur Organisasi", "Structure") },
]);

function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: "smooth" });
    mobileMenuOpen.value = false;
}

const strukturByLevel = computed(() => {
    const grouped = {};
    (s.value.struktur || []).forEach((item) => {
        if (!grouped[item.level]) grouped[item.level] = [];
        grouped[item.level].push(item);
    });
    return grouped;
});

const levelColors = {
    blue: "bg-primary-600 text-white border-primary-700",
    cyan: "bg-teal-600 text-white border-teal-700",
    orange: "bg-amber-600 text-white border-amber-700",
    green: "bg-emerald-600 text-white border-emerald-700",
    pink: "bg-rose-500 text-white border-rose-600",
};
</script>

<template>
    <Head :title="`${s.desaName} — ${s.kecamatan}, ${s.kabupaten}`" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans text-slate-800 dark:text-slate-100 antialiased selection:bg-primary-500 selection:text-white">
        <!-- ── Navbar ── -->
        <nav
            class="fixed top-0 left-0 right-0 z-50 bg-white/85 dark:bg-slate-900/85 backdrop-blur-md border-b border-slate-200/80 dark:border-slate-800 shadow-sm transition-colors"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-primary-600 flex items-center justify-center text-white shadow-md shadow-primary-600/20"
                        >
                            <FolderArchive class="w-5 h-5" />
                        </div>
                        <div>
                            <p
                                class="text-sm font-bold text-slate-900 dark:text-white leading-tight"
                            >
                                {{ s.desaName }}
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight">
                                Kec. {{ s.kecamatan }}, Kab. {{ s.kabupaten }}
                            </p>
                        </div>
                    </div>

                    <!-- Desktop nav links -->
                    <div class="hidden lg:flex items-center gap-1">
                        <button
                            v-for="link in navLinks"
                            :key="link.id"
                            @click="scrollTo(link.id)"
                            class="px-3 py-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 hover:bg-primary-50 dark:hover:bg-primary-950/40 rounded-xl transition-colors"
                        >
                            {{ link.label }}
                        </button>
                    </div>

                    <!-- Right tools: Locale + Theme + Portal CTA -->
                    <div class="hidden sm:flex items-center gap-2">
                        <!-- Locale toggle -->
                        <button
                            @click="toggleLocale"
                            :title="t('Ganti Bahasa', 'Change Language')"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-primary-500 transition-colors shadow-sm"
                        >
                            <Globe class="w-3.5 h-3.5 text-primary-600" />
                            <span>{{ locale.toUpperCase() }}</span>
                        </button>

                        <!-- Theme toggle -->
                        <button
                            @click="toggleTheme"
                            :title="t('Ganti Tema', 'Change Theme')"
                            class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:text-primary-600 transition-colors shadow-sm"
                        >
                            <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                            <Moon v-else class="w-4 h-4" />
                        </button>

                        <!-- Daftar Petugas CTA (Only when enabled by admin) -->
                        <Link
                            v-if="$page.props.can_register"
                            :href="route('register')"
                            class="hidden md:inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold rounded-xl text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-950/40 border border-primary-200 dark:border-primary-800 transition-colors shadow-sm"
                        >
                            <UserPlus class="w-3.5 h-3.5" />
                            <span>{{ t("Daftar Petugas", "Register") }}</span>
                        </Link>

                        <!-- Sistem Arsip Login CTA -->
                        <Link
                            :href="route('login')"
                            class="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-primary-600/20 ml-1"
                        >
                            <FolderArchive class="w-3.5 h-3.5" />
                            <span>{{ t("Masuk Sistem Arsip", "Archive Portal") }}</span>
                        </Link>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="flex items-center gap-1 sm:hidden">
                        <button
                            @click="toggleLocale"
                            class="px-2 py-1 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-bold text-slate-700 dark:text-slate-200"
                        >
                            {{ locale.toUpperCase() }}
                        </button>
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                        >
                            <Menu v-if="!mobileMenuOpen" class="w-5 h-5" />
                            <X v-else class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu dropdown -->
            <div
                v-if="mobileMenuOpen"
                class="lg:hidden border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-4 py-3 space-y-2"
            >
                <button
                    v-for="link in navLinks"
                    :key="link.id"
                    @click="scrollTo(link.id)"
                    class="block w-full text-left px-3 py-2 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-primary-50 dark:hover:bg-primary-950/40 hover:text-primary-600 rounded-xl transition-colors"
                >
                    {{ link.label }}
                </button>
                <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <button
                        @click="toggleTheme"
                        class="flex items-center gap-2 text-xs font-medium text-slate-600 dark:text-slate-300"
                    >
                        <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                        <Moon v-else class="w-4 h-4" />
                        <span>{{ isDark ? t("Mode Gelap", "Dark Mode") : t("Mode Terang", "Light Mode") }}</span>
                    </button>
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="$page.props.can_register"
                            :href="route('register')"
                            class="px-2.5 py-1.5 border border-primary-200 dark:border-primary-800 text-primary-700 dark:text-primary-300 rounded-lg text-xs font-bold"
                        >
                            {{ t("Daftar", "Register") }}
                        </Link>
                        <Link
                            :href="route('login')"
                            class="flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 text-white rounded-lg text-xs font-bold shadow-sm"
                        >
                            <FolderArchive class="w-3.5 h-3.5" />
                            {{ t("Masuk Arsip", "Sign In") }}
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- ── HERO SECTION ── -->
        <section id="beranda" class="relative pt-24 pb-20 md:pt-32 md:pb-28 overflow-hidden">
            <!-- Background Image or Gradient -->
            <div class="absolute inset-0 z-0">
                <div
                    v-if="s.heroImage"
                    :style="`background-image: url('/storage/${s.heroImage}')`"
                    class="w-full h-full bg-cover bg-center"
                />
                <div
                    v-else
                    class="w-full h-full bg-gradient-to-br from-primary-900 via-emerald-800 to-slate-950"
                />
                <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-[2px]" />
                <div
                    class="absolute inset-0 opacity-10"
                    style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;"
                />
            </div>

            <div class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-white">
                <div
                    class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-white/15 dark:bg-white/10 backdrop-blur-md rounded-full text-xs font-semibold mb-6 border border-white/20 shadow-sm"
                >
                    <MapPin class="w-3.5 h-3.5 text-primary-300" />
                    <span>{{ s.kecamatan }}, {{ s.kabupaten }}, Sumatera Selatan</span>
                </div>

                <h1
                    class="text-4xl sm:text-5xl lg:text-6xl font-black mb-4 leading-tight tracking-tight text-white drop-shadow-md"
                >
                    {{ s.heroTitle }}
                </h1>

                <p class="text-base sm:text-lg text-emerald-100 max-w-2xl mx-auto mb-4 font-medium">
                    {{ s.heroSubtitle }}
                </p>

                <p class="text-sm sm:text-base text-white/80 max-w-xl mx-auto mb-9 italic">
                    "{{ s.heroTagline }}"
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center max-w-md mx-auto">
                    <button
                        @click="scrollTo('profil')"
                        class="flex items-center justify-center gap-2 px-6 py-3 bg-white text-primary-900 font-bold rounded-2xl hover:bg-emerald-50 transition-all shadow-lg"
                    >
                        <span>{{ t("Jelajahi Profil Desa", "Explore Village Profile") }}</span>
                        <ChevronRight class="w-4 h-4" />
                    </button>
                    <Link
                        :href="route('login')"
                        class="flex items-center justify-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-500 text-white font-bold rounded-2xl backdrop-blur transition-all border border-white/20 shadow-lg shadow-primary-950/40"
                    >
                        <FolderArchive class="w-4 h-4" />
                        <span>{{ t("Buka Sistem Arsip", "Digital Archive Portal") }}</span>
                    </Link>
                </div>

                <!-- Scroll down indicator -->
                <button
                    @click="scrollTo('profil')"
                    class="mt-14 inline-flex flex-col items-center gap-1 text-white/60 hover:text-white transition-colors"
                >
                    <span class="text-[11px] font-semibold tracking-wider uppercase">{{ t("Gulir Ke Bawah", "Scroll Down") }}</span>
                    <ChevronDown class="w-4 h-4 animate-bounce" />
                </button>
            </div>
        </section>

        <!-- ── QUICK STATS STRIP ── -->
        <section class="bg-primary-700 dark:bg-primary-900 py-8 border-y border-primary-600/50">
            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center text-white">
                    <div class="p-2">
                        <p class="text-2xl sm:text-3xl font-black tracking-tight">{{ s.luasDesa }}</p>
                        <p class="text-xs text-primary-200 mt-1 uppercase font-semibold">{{ t("Luas Wilayah", "Total Area") }}</p>
                    </div>
                    <div class="p-2">
                        <p class="text-2xl sm:text-3xl font-black tracking-tight">{{ s.jumlahDusun }}</p>
                        <p class="text-xs text-primary-200 mt-1 uppercase font-semibold">{{ t("Jumlah Dusun", "Hamlets") }}</p>
                    </div>
                    <div class="p-2">
                        <p class="text-2xl sm:text-3xl font-black tracking-tight">4</p>
                        <p class="text-xs text-primary-200 mt-1 uppercase font-semibold">{{ t("Batas Geografis", "Border Areas") }}</p>
                    </div>
                    <div class="p-2">
                        <p class="text-2xl sm:text-3xl font-black tracking-tight">2021–2027</p>
                        <p class="text-xs text-primary-200 mt-1 uppercase font-semibold">{{ t("Periode BPD & Kades", "Governance Term") }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── PROFIL DESA ── -->
        <section id="profil" class="py-20 bg-white dark:bg-slate-900 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <span
                        class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 text-xs font-bold rounded-full uppercase tracking-wider mb-2"
                    >
                        {{ t("Informasi Wilayah", "Territory Overview") }}
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ t("Profil Pemerintahan Desa", "Village Government Profile") }}
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1 max-w-xl mx-auto">
                        {{ t("Data demografis, kepemimpinan, dan posisi geografis Desa Sukarami", "Demographics, leadership, and geographic borders of Sukarami") }}
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    <!-- Kepala Desa Card -->
                    <div
                        class="bg-slate-50 dark:bg-slate-800 rounded-3xl border border-slate-200/80 dark:border-slate-700 overflow-hidden shadow-sm"
                    >
                        <div class="bg-gradient-to-r from-primary-600 to-emerald-600 px-6 py-5 text-white">
                            <span class="text-primary-100 text-xs font-semibold uppercase tracking-wider">
                                {{ t("Kepala Desa Periode Berjalan", "Incumbent Village Head") }}
                            </span>
                            <h3 class="text-2xl font-black tracking-tight mt-0.5">
                                {{ s.kepalaDesa }}
                            </h3>
                            <p class="text-xs text-primary-100">
                                {{ s.desaName }}, Kec. {{ s.kecamatan }}
                            </p>
                        </div>

                        <div class="p-6">
                            <div v-if="s.fotoKades" class="mb-5 text-center">
                                <img
                                    :src="`/storage/${s.fotoKades}`"
                                    :alt="s.kepalaDesa"
                                    class="w-32 h-32 rounded-2xl object-cover shadow-md mx-auto border-2 border-white dark:border-slate-700"
                                />
                            </div>
                            <div
                                v-else
                                class="w-24 h-24 rounded-2xl bg-primary-100 dark:bg-primary-950/50 text-primary-600 dark:text-primary-300 flex items-center justify-center mx-auto mb-4 border border-primary-200 dark:border-primary-800"
                            >
                                <Users class="w-10 h-10" />
                            </div>

                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-700">
                                    <span class="text-slate-500 dark:text-slate-400">{{ t("Luas Wilayah", "Area") }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ s.luasDesa }}</span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-700">
                                    <span class="text-slate-500 dark:text-slate-400">{{ t("Jumlah Dusun", "Hamlets") }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ s.jumlahDusun }}</span>
                                </div>
                                <div class="flex justify-between py-2 border-b border-slate-200 dark:border-slate-700">
                                    <span class="text-slate-500 dark:text-slate-400">{{ t("Kecamatan", "District") }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ s.kecamatan }}</span>
                                </div>
                                <div class="flex justify-between py-2">
                                    <span class="text-slate-500 dark:text-slate-400">{{ t("Kabupaten", "Regency") }}</span>
                                    <span class="font-bold text-slate-900 dark:text-white">{{ s.kabupaten }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Batas Wilayah Card -->
                    <div class="space-y-4">
                        <div class="bg-slate-50 dark:bg-slate-800 rounded-3xl border border-slate-200/80 dark:border-slate-700 p-6 shadow-sm">
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2">
                                <Compass class="w-5 h-5 text-primary-600" />
                                {{ t("Batas Geografis Wilayah", "Geographical Boundaries") }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">
                                {{ t("Letak dan batas administrasi dengan desa-desa sekitar", "Administrative boundaries with surrounding villages") }}
                            </p>

                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm">
                                    <p class="text-[11px] font-bold text-primary-600 uppercase mb-1">{{ t("Utara ↑", "North ↑") }}</p>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-snug">{{ s.batasUtara || "—" }}</p>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm">
                                    <p class="text-[11px] font-bold text-primary-600 uppercase mb-1">{{ t("Timur →", "East →") }}</p>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-snug">{{ s.batasTimur || "—" }}</p>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm">
                                    <p class="text-[11px] font-bold text-primary-600 uppercase mb-1">{{ t("← Barat", "← West") }}</p>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-snug">{{ s.batasBarat || "—" }}</p>
                                </div>
                                <div class="p-3.5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm">
                                    <p class="text-[11px] font-bold text-primary-600 uppercase mb-1">{{ t("↓ Selatan", "↓ South") }}</p>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 leading-snug">{{ s.batasSelatan || "—" }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Digital Archive Highlights card -->
                        <div class="bg-gradient-to-br from-primary-900 to-emerald-950 rounded-3xl p-6 text-white shadow-sm border border-primary-800">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center">
                                    <FolderArchive class="w-5 h-5 text-white" />
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold">{{ t("Pusat Arsip Digital Desa", "Village Digital Archive Center") }}</h4>
                                    <p class="text-xs text-primary-200">{{ t("Terintegrasi dengan OCR & pencarian otomatis", "Integrated with OCR & smart search") }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-white/80 leading-relaxed mb-4">
                                {{ t("Dokumen SK, Surat Keluar/Masuk, Surat Perdamaian, dan Bantuan tersimpan aman secara terenkripsi.", "Official decrees, incoming/outgoing letters, and aid archives securely stored.") }}
                            </p>
                            <Link
                                :href="route('login')"
                                class="inline-flex items-center gap-1 text-xs font-bold text-primary-200 hover:text-white transition-colors"
                            >
                                <span>{{ t("Buka dashboard arsip", "Open archive dashboard") }}</span>
                                <ArrowUpRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── SEJARAH DESA ── -->
        <section id="sejarah" class="py-20 bg-slate-100 dark:bg-slate-950 transition-colors">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <span
                        class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 text-xs font-bold rounded-full uppercase tracking-wider mb-2"
                    >
                        {{ t("Napak Tilas", "Heritage") }}
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ t("Asal-Usul Desa Sukarami", "History of Desa Sukarami") }}
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                        {{ t("Perjalanan sejarah panjang terbentuknya desa dari masa ke masa", "Long historical journey of the village development") }}
                    </p>
                </div>

                <div class="relative">
                    <!-- Vertical Timeline line -->
                    <div
                        class="absolute left-4 sm:left-8 top-0 bottom-0 w-0.5 bg-gradient-to-b from-primary-600 to-emerald-400 rounded-full"
                    />

                    <div class="space-y-8">
                        <div
                            v-for="(event, i) in [
                                {
                                    year: '1832',
                                    title: 'Danau Air Henek Jeheng',
                                    desc: 'Cikal bakal desa bermula di kawasan Danau Air Henek Jeheng di bawah pimpinan Kerio GEMUNTUR.',
                                },
                                {
                                    year: '1862',
                                    title: 'Pindah ke Padang Bindu',
                                    desc: 'Karena wabah penyakit, warga pindah ke dusun Padang Bindu dekat sungai Suban.',
                                },
                                {
                                    year: '1890',
                                    title: 'Dusun SUKE DANE',
                                    desc: 'Berpindah ke tepi Sungai Rambang, diberi nama Suke Dane yang berarti Senang Berkumpul.',
                                },
                                {
                                    year: '±1900',
                                    title: 'Menjadi SUKAR RAMI',
                                    desc: 'Dipindahkan ke seberang sungai Rambang, berganti nama Sukar Rami yang berarti Sulit Datang Beramai-ramai.',
                                },
                                {
                                    year: '1918',
                                    title: 'SUKARAMI Modern',
                                    desc: 'Setelah banjir berulang, desa pindah ke lokasi saat ini dekat sungai Toman dan resmi bernama SUKARAMI.',
                                },
                            ]"
                            :key="i"
                            class="relative pl-12 sm:pl-20"
                        >
                            <div
                                class="absolute left-1.5 sm:left-5.5 w-5 h-5 rounded-full bg-primary-600 border-4 border-white dark:border-slate-900 shadow-md top-1"
                            />
                            <div
                                class="inline-block px-2.5 py-0.5 bg-primary-50 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800 text-xs font-bold rounded-full mb-1"
                            >
                                {{ event.year }}
                            </div>
                            <h4 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white mb-1">
                                {{ event.title }}
                            </h4>
                            <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                                {{ event.desc }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Full history expandable accordion -->
                <div
                    v-if="s.sejarahLengkap"
                    class="mt-10 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm"
                >
                    <button
                        @click="showFullSejarah = !showFullSejarah"
                        class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors"
                    >
                        <div class="flex items-center gap-2">
                            <BookOpen class="w-4 h-4 text-primary-600" />
                            <span class="font-bold text-slate-900 dark:text-white text-sm">
                                {{ t("Baca Naskah Sejarah Lengkap", "Read Complete Historical Document") }}
                            </span>
                        </div>
                        <ChevronDown
                            :class="[
                                'w-4 h-4 text-slate-400 transition-transform',
                                showFullSejarah ? 'rotate-180' : '',
                            ]"
                        />
                    </button>
                    <div v-if="showFullSejarah" class="px-6 pb-6 pt-2 border-t border-slate-100 dark:border-slate-700">
                        <p class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">
                            {{ s.sejarahLengkap }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── VISI & MISI ── -->
        <section
            id="visi-misi"
            class="py-20 bg-gradient-to-br from-primary-900 via-emerald-800 to-slate-950 text-white"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <span
                        class="inline-block px-3 py-1 bg-white/15 text-primary-200 text-xs font-bold rounded-full uppercase tracking-wider mb-2 border border-white/20"
                    >
                        {{ t("Cita-Cita Desa", "Vision & Direction") }}
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tight">
                        {{ t("Visi & Misi Pembangunan", "Village Vision & Mission") }}
                    </h2>
                    <p class="text-primary-200 text-sm mt-1">
                        {{ t("Pedoman arah kebijakan dan pelayanan warga Desa Sukarami", "Policy direction and public welfare guidelines") }}
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
                    <!-- Visi -->
                    <div class="bg-white/10 backdrop-blur-md rounded-3xl p-8 border border-white/20 shadow-lg">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-amber-400 text-slate-900 flex items-center justify-center font-bold">
                                <Target class="w-5 h-5" />
                            </div>
                            <h3 class="text-xl font-bold text-white">{{ t("Visi Desa", "Village Vision") }}</h3>
                        </div>
                        <div class="bg-white/10 rounded-2xl p-6 border-l-4 border-amber-400">
                            <p class="text-white font-medium text-base sm:text-lg italic leading-relaxed">
                                "{{ s.visi }}"
                            </p>
                        </div>
                    </div>

                    <!-- Misi -->
                    <div class="bg-white/10 backdrop-blur-md rounded-3xl p-8 border border-white/20 shadow-lg">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="w-10 h-10 rounded-xl bg-primary-400 text-slate-950 flex items-center justify-center font-bold">
                                <Sparkles class="w-5 h-5" />
                            </div>
                            <h3 class="text-xl font-bold text-white">{{ t("Misi Strategis", "Strategic Missions") }}</h3>
                        </div>
                        <ol class="space-y-3">
                            <li
                                v-for="(item, i) in s.misi || []"
                                :key="i"
                                class="flex items-start gap-3 bg-white/5 p-3 rounded-xl border border-white/10"
                            >
                                <span
                                    class="w-6 h-6 rounded-lg bg-primary-500 text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5"
                                >
                                    {{ i + 1 }}
                                </span>
                                <p class="text-emerald-100 text-xs sm:text-sm leading-relaxed">
                                    {{ item }}
                                </p>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── STRUKTUR PEMERINTAHAN ── -->
        <section id="struktur" class="py-20 bg-white dark:bg-slate-900 transition-colors">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-14">
                    <span
                        class="inline-block px-3 py-1 bg-primary-100 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 text-xs font-bold rounded-full uppercase tracking-wider mb-2"
                    >
                        {{ t("Bagan Kerja", "Hierarchy") }}
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tracking-tight">
                        {{ t("Struktur Organisasi Pemerintah Desa", "Government Organizational Chart") }}
                    </h2>
                    <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">
                        {{ t("Susunan perangkat desa dan jajaran pelayanan masyarakat", "Village apparatus and community service personnel") }}
                    </p>
                </div>

                <div class="space-y-6">
                    <div
                        v-for="(members, level) in strukturByLevel"
                        :key="level"
                        class="flex flex-wrap justify-center gap-3"
                    >
                        <div
                            v-for="member in members"
                            :key="member.nama"
                            :class="[
                                'px-5 py-3.5 rounded-2xl border-2 text-center min-w-[150px] shadow-sm transition-transform hover:-translate-y-0.5',
                                levelColors[member.warna] || levelColors.blue,
                            ]"
                        >
                            <p class="text-xs font-semibold opacity-90 leading-tight">
                                {{ member.jabatan }}
                            </p>
                            <p class="font-bold text-sm mt-1 leading-tight">
                                {{ member.nama }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── CTA BANNER: SISTEM ARSIP DIGITAL ── -->
        <section class="py-20 bg-slate-50 dark:bg-slate-950 transition-colors">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div
                    class="bg-gradient-to-r from-primary-700 via-emerald-600 to-primary-800 rounded-3xl p-10 sm:p-14 shadow-xl text-white relative overflow-hidden"
                >
                    <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-inner">
                        <FolderArchive class="w-8 h-8 text-white" />
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black tracking-tight mb-3">
                        {{ t("Layanan Pengarsipan Digital Terpadu", "Unified Village Digital Archiving") }}
                    </h2>
                    <p class="text-primary-100 text-sm sm:text-base max-w-xl mx-auto leading-relaxed mb-8 font-medium">
                        {{
                            t(
                                "Kemudahan akses arsip, pencarian isi teks dengan OCR, dan perlindungan berkas penting desa dalam satu platform modern.",
                                "Seamless archive access, fulltext OCR search, and secure preservation of village documents in one modern platform."
                            )
                        }}
                    </p>
                    <Link
                        :href="route('login')"
                        class="inline-flex items-center gap-2 px-8 py-3.5 bg-white text-primary-900 font-bold rounded-2xl hover:bg-emerald-50 transition-all shadow-lg hover:shadow-xl"
                    >
                        <span>{{ t("Masuk ke Dashboard Sistem", "Access System Dashboard") }}</span>
                        <ExternalLink class="w-4 h-4" />
                    </Link>
                </div>
            </div>
        </section>

        <!-- ── FOOTER ── -->
        <footer class="bg-slate-900 text-slate-400 py-10 border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4 text-center md:text-left">
                    <div>
                        <p class="text-white font-bold text-base flex items-center justify-center md:justify-start gap-2">
                            <FolderArchive class="w-4 h-4 text-primary-400" />
                            {{ s.desaName }}
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Kec. {{ s.kecamatan }}, Kab. {{ s.kabupaten }}, Sumatera Selatan
                        </p>
                    </div>

                    <div class="text-xs">
                        <p>
                            {{ t("Sistem Informasi Pengarsipan Digital Desa", "Village Digital Archiving System") }} &copy; {{ new Date().getFullYear() }}
                        </p>
                        <p class="text-slate-500 text-[11px] mt-0.5">
                            {{ t("Aman, Terpercaya, dan Terstandarisasi", "Secure, Trusted, and Standardized") }}
                        </p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
