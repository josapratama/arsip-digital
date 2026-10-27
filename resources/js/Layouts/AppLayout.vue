<script setup>
import { ref, computed, watch } from "vue";
import { Link, usePage, router } from "@inertiajs/vue3";
import {
    LayoutDashboard,
    FolderArchive,
    FolderTree,
    Trash2,
    ClipboardList,
    LogOut,
    Menu,
    X,
    Sun,
    Moon,
    Globe,
    Users,
    ChevronRight,
    ChevronLeft,
} from "lucide-vue-next";
import Toast from "@/Components/Toast.vue";
import { i18nState } from "@/i18n.js";
import { useI18n, setLocale } from "@/useI18n.js";

const page = usePage();
const user = computed(() => page.props.auth.user);
const routeName = computed(() => page.props.route_name ?? "");
const sidebarOpen = ref(false);

// ── i18n ──────────────────────────────────────────────────────────────────────
const { t, locale } = useI18n();

// ── Theme state — localStorage is source of truth, NOT server props ───────────
const isDark = ref(localStorage.getItem("theme") === "dark");

// Sync isDark when user changes theme from Profile page (watch localStorage)
// We do this with a storage event listener
window.addEventListener("storage", (e) => {
    if (e.key === "theme") isDark.value = e.newValue === "dark";
    if (e.key === "locale") i18nState.locale = e.newValue || "id";
});

// On first load: seed locale from user DB if localStorage has no value yet
watch(
    user,
    (u) => {
        if (!u) return;
        if (!localStorage.getItem("locale")) {
            setLocale(u.locale ?? "id");
        }
        if (!localStorage.getItem("theme")) {
            const t = u.theme ?? "light";
            isDark.value = t === "dark";
            localStorage.setItem("theme", t);
            document.documentElement.classList.toggle("dark", t === "dark");
        }
    },
    { immediate: true },
);

// ── Toast ─────────────────────────────────────────────────────────────────────
const toastMessage = ref("");
const toastType = ref("success");

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            toastMessage.value = flash.success;
            toastType.value = "success";
            window.scrollTo({ top: 0, behavior: "smooth" });
        } else if (flash?.error) {
            toastMessage.value = flash.error;
            toastType.value = "error";
            window.scrollTo({ top: 0, behavior: "smooth" });
        }
    },
    { deep: true },
);

function clearToast() {
    toastMessage.value = "";
}

// ── Topbar: dynamic page title + breadcrumb ───────────────────────────────────
const pageConfig = computed(() => {
    const r = routeName.value;
    const cur = locale.value;
    const getT = (idText, enText, jaText) => {
        if (cur === "id") return idText;
        if (cur === "ja") return jaText || enText;
        return enText;
    };
    const map = {
        dashboard: { title: getT("Dasbor", "Dashboard", "ダッシュボード"), crumbs: [] },
        "documents.index": {
            title: getT("Arsip Dokumen", "Document Archive", "文書アーカイブ"),
            crumbs: [],
        },
        "documents.create": {
            title: getT("Unggah Dokumen", "Upload Document", "ドキュメントのアップロード"),
            crumbs: [
                {
                    label: getT("Dokumen", "Documents", "文書"),
                    href: route("documents.index"),
                },
            ],
        },
        "documents.batch.create": {
            title: getT("Unggah Masal Dokumen", "Batch Upload Documents", "ドキュメント一括アップロード"),
            crumbs: [
                {
                    label: getT("Dokumen", "Documents", "文書"),
                    href: route("documents.index"),
                },
            ],
        },
        "documents.show": {
            title: getT("Detail Dokumen", "Document Detail", "文書詳細"),
            crumbs: [
                {
                    label: getT("Dokumen", "Documents", "文書"),
                    href: route("documents.index"),
                },
            ],
        },
        "documents.edit": {
            title: getT("Edit Dokumen", "Edit Document", "ドキュメントを編集"),
            crumbs: [
                {
                    label: getT("Dokumen", "Documents", "文書"),
                    href: route("documents.index"),
                },
            ],
        },
        "recycle-bin.index": {
            title: getT("Kotak Sampah", "Recycle Bin", "ごみ箱"),
            crumbs: [],
        },
        "activity-log.index": {
            title: getT("Riwayat Aktivitas", "Activity Log", "アクティビティログ"),
            crumbs: [],
        },
        "admin.categories.index": {
            title: getT("Kategori Dokumen", "Document Categories", "文書カテゴリ"),
            crumbs: [],
        },
        "profile.edit": {
            title: getT("Profil Saya", "My Profile", "私のプロフィール"),
            crumbs: [],
        },
        "admin.site-settings.index": {
            title: getT("Pengaturan Halaman", "Page Settings", "ページ設定"),
            crumbs: [],
        },
        "admin.users.index": {
            title: getT("Manajemen Pengguna", "User Management", "ユーザー管理"),
            crumbs: [],
        },
    };
    return map[r] ?? { title: "", crumbs: [] };
});

// ── Theme toggle ──────────────────────────────────────────────────────────────
function toggleTheme() {
    const newTheme = isDark.value ? "light" : "dark";
    isDark.value = newTheme === "dark";
    document.documentElement.classList.toggle("dark", newTheme === "dark");
    localStorage.setItem("theme", newTheme);
    router.patch(
        route("profile.preference"),
        { theme: newTheme },
        { preserveScroll: true, preserveState: true },
    );
}

// ── Locale toggle ─────────────────────────────────────────────────────────────
function toggleLocale() {
    const next = { id: "en", en: "ja", ja: "id" };
    const newLocale = next[locale.value] || "id";
    setLocale(newLocale);
    router.patch(
        route("profile.preference"),
        { locale: newLocale },
        { preserveScroll: true, preserveState: true },
    );
}

// ── Nav ───────────────────────────────────────────────────────────────────────
const isAdmin = computed(() => user.value?.is_admin === true);

const navItems = computed(() => {
    const id = locale.value === "id";
    const items = [
        {
            name: id ? "Dasbor" : "Dashboard",
            href: route("dashboard"),
            icon: LayoutDashboard,
            current: route().current("dashboard"),
        },
        {
            name: id ? "Dokumen" : "Documents",
            href: route("documents.index"),
            icon: FolderArchive,
            current: route().current("documents.*"),
        },
        {
            name: id ? "Kotak Sampah" : "Recycle Bin",
            href: route("recycle-bin.index"),
            icon: Trash2,
            current: route().current("recycle-bin.*"),
        },
    ];

    // Admin-only nav items
    if (isAdmin.value) {
        items.push({
            name: id ? "Kategori Dokumen" : "Categories",
            href: route("admin.categories.index"),
            icon: FolderTree,
            current: route().current("admin.categories.*"),
        });
        items.push({
            name: id ? "Riwayat Aktivitas" : "Activity Log",
            href: route("activity-log.index"),
            icon: ClipboardList,
            current: route().current("activity-log.*"),
        });
        items.push({
            name: id ? "Pengaturan Halaman" : "Page Settings",
            href: route("admin.site-settings.index"),
            icon: Globe,
            current: route().current("admin.site-settings.*"),
        });
        items.push({
            name: id ? "Manajemen Pengguna" : "Users",
            href: route("admin.users.index"),
            icon: Users,
            current: route().current("admin.users.*"),
        });
    }

    return items;
});

function logout() {
    // Clear UI prefs on logout so next user gets fresh state
    localStorage.removeItem("locale");
    localStorage.removeItem("theme");
    router.post(route("logout"));
}

// ── Logout confirmation ───────────────────────────────────────────────────────
const showLogoutModal = ref(false);
</script>

<template>
    <div
        class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100"
    >
        <Toast :message="toastMessage" :type="toastType" @close="clearToast" />

        <!-- Mobile overlay -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 z-20 bg-black/50 lg:hidden"
        />

        <!-- ── Sidebar ── -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-30 w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700',
                'transform transition-transform duration-300 ease-in-out lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
            ]"
        >
            <div
                class="flex items-center gap-3 px-5 py-5 border-b border-slate-200 dark:border-slate-700"
            >
                <div
                    class="w-9 h-9 rounded-xl bg-primary-600 flex items-center justify-center shrink-0"
                >
                    <FolderArchive class="w-5 h-5 text-white" />
                </div>
                <div class="min-w-0">
                    <p
                        class="text-sm font-semibold truncate text-slate-900 dark:text-white"
                    >
                        {{
                            locale === "id"
                                ? "Arsip Digital"
                                : "Digital Archive"
                        }}
                    </p>
                    <p
                        class="text-xs text-slate-500 dark:text-slate-400 truncate"
                    >
                        Desa Sukarami
                    </p>
                </div>
            </div>

            <nav class="p-3 space-y-1">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
                        item.current
                            ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white',
                    ]"
                    @click="sidebarOpen = false"
                >
                    <component :is="item.icon" class="w-4.5 h-4.5 shrink-0" />
                    {{ item.name }}
                </Link>
            </nav>

            <div
                class="absolute bottom-0 left-0 right-0 p-3 border-t border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800"
            >
                <!-- Quick Language & Theme switch inside sidebar for mobile drawer -->
                <div class="flex items-center justify-between px-3 py-2 mb-2 bg-slate-50 dark:bg-slate-700/50 rounded-xl text-xs">
                    <span class="text-slate-500 dark:text-slate-400 flex items-center gap-1.5 font-medium">
                        <Globe class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                        {{ t("Bahasa", "Language") }}
                    </span>
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="setLocale('id')"
                            :class="[
                                'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all',
                                locale === 'id'
                                    ? 'bg-emerald-600 text-white shadow-xs'
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                            ]"
                        >
                            ID
                        </button>
                        <span class="text-slate-300 dark:text-slate-600">|</span>
                        <button
                            type="button"
                            @click="setLocale('en')"
                            :class="[
                                'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all',
                                locale === 'en'
                                    ? 'bg-emerald-600 text-white shadow-xs'
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                            ]"
                        >
                            EN
                        </button>
                        <span class="text-slate-300 dark:text-slate-600">|</span>
                        <button
                            type="button"
                            @click="setLocale('ja')"
                            :class="[
                                'px-2 py-0.5 rounded-md text-[11px] font-bold transition-all',
                                locale === 'ja'
                                    ? 'bg-emerald-600 text-white shadow-xs'
                                    : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'
                            ]"
                        >
                            JA
                        </button>
                    </div>
                </div>

                <Link
                    :href="route('profile.edit')"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors"
                    @click="sidebarOpen = false"
                >
                    <div
                        class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center shrink-0"
                    >
                        <span
                            class="text-xs font-semibold text-primary-700 dark:text-primary-300"
                        >
                            {{ user?.name?.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-sm font-medium truncate text-slate-900 dark:text-white"
                        >
                            {{ user?.name }}
                        </p>
                        <p
                            class="text-xs text-slate-500 dark:text-slate-400 truncate"
                        >
                            {{ user?.position || user?.jabatan }}
                        </p>
                    </div>
                    <ChevronRight class="w-4 h-4 text-slate-400 shrink-0" />
                </Link>
                <button
                    @click="showLogoutModal = true"
                    class="flex w-full items-center gap-3 px-3 py-2 rounded-lg text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors mt-1"
                >
                    <LogOut class="w-4.5 h-4.5" />
                    {{ locale === "id" ? "Keluar" : "Logout" }}
                </button>
            </div>
        </aside>

        <!-- ── Main ── -->
        <div class="lg:pl-64 flex flex-col min-h-screen">
            <!-- Top bar -->
            <header
                class="sticky top-0 z-10 bg-white/90 dark:bg-slate-800/90 backdrop-blur border-b border-slate-200 dark:border-slate-700"
            >
                <div class="flex items-center h-14 px-4 sm:px-6 gap-3">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-2 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 shrink-0"
                    >
                        <Menu v-if="!sidebarOpen" class="w-5 h-5" />
                        <X v-else class="w-5 h-5" />
                    </button>

                    <!-- Dynamic title + breadcrumb -->
                    <div class="flex-1 min-w-0">
                        <nav
                            v-if="pageConfig.crumbs.length"
                            class="flex items-center gap-1 text-xs text-slate-400 mb-0.5"
                        >
                            <Link
                                :href="route('dashboard')"
                                class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors"
                            >
                                {{ locale === "id" ? "Beranda" : "Home" }}
                            </Link>
                            <template
                                v-for="(crumb, i) in pageConfig.crumbs"
                                :key="i"
                            >
                                <ChevronRight class="w-3 h-3 shrink-0" />
                                <Link
                                    :href="crumb.href"
                                    class="hover:text-slate-600 dark:hover:text-slate-300 transition-colors truncate"
                                >
                                    {{ crumb.label }}
                                </Link>
                            </template>
                            <ChevronRight
                                v-if="pageConfig.crumbs.length"
                                class="w-3 h-3 shrink-0"
                            />
                            <span
                                class="text-slate-500 dark:text-slate-400 truncate"
                                >{{ pageConfig.title }}</span
                            >
                        </nav>
                        <h1
                            v-if="pageConfig.title"
                            class="text-sm sm:text-base font-semibold text-slate-900 dark:text-white truncate leading-tight"
                        >
                            {{ pageConfig.title }}
                        </h1>
                    </div>

                    <!-- Right controls -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <!-- Universal Language Switcher Button (Always visible on mobile & desktop) -->
                        <button
                            @click="toggleLocale"
                            :title="locale === 'id' ? 'Switch to English' : (locale === 'en' ? 'Switch to Japanese' : 'Ganti ke Bahasa Indonesia')"
                            class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-700/60 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 transition-all shadow-xs"
                        >
                            <Globe class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <span class="font-bold text-xs tracking-wider uppercase font-mono">
                                {{ locale.toUpperCase() }}
                            </span>
                        </button>
                        <button
                            @click="toggleTheme"
                            class="p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <Sun v-if="isDark" class="w-4.5 h-4.5" />
                            <Moon v-else class="w-4.5 h-4.5" />
                        </button>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6">
                <slot />
            </main>

            <footer
                class="px-6 py-3 border-t border-slate-200 dark:border-slate-700 text-center text-xs text-slate-400"
            >
                Sistem Pengarsipan Digital — Desa Sukarami, Kec. Rambang, Kab.
                Muara Enim
            </footer>
        </div>
    </div>

    <!-- ── Logout Confirmation Modal ── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showLogoutModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="showLogoutModal = false"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/50 backdrop-blur-sm"
                    @click="showLogoutModal = false"
                />

                <!-- Modal card -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="scale-100 opacity-100"
                    leave-to-class="scale-95 opacity-0"
                >
                    <div
                        v-if="showLogoutModal"
                        class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-sm p-6"
                    >
                        <!-- Icon -->
                        <div class="flex justify-center mb-4">
                            <div
                                class="w-14 h-14 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center"
                            >
                                <LogOut
                                    class="w-6 h-6 text-red-600 dark:text-red-400"
                                />
                            </div>
                        </div>

                        <!-- Text -->
                        <div class="text-center mb-6">
                            <h3
                                class="text-base font-semibold text-slate-900 dark:text-white mb-1"
                            >
                                {{
                                    locale === "id"
                                        ? "Keluar dari Sistem?"
                                        : "Sign Out?"
                                }}
                            </h3>
                            <p
                                class="text-sm text-slate-500 dark:text-slate-400"
                            >
                                {{
                                    locale === "id"
                                        ? "Anda akan keluar dari akun ini. Pastikan semua pekerjaan sudah tersimpan."
                                        : "You will be signed out of this account. Make sure all your work is saved."
                                }}
                            </p>
                        </div>

                        <!-- Buttons -->
                        <div class="flex gap-3">
                            <button
                                @click="showLogoutModal = false"
                                class="flex-1 px-4 py-2.5 text-sm font-medium border border-slate-200 dark:border-slate-600 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                            >
                                {{ locale === "id" ? "Batal" : "Cancel" }}
                            </button>
                            <button
                                @click="logout"
                                class="flex-1 px-4 py-2.5 text-sm font-semibold bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors flex items-center justify-center gap-2"
                            >
                                <LogOut class="w-4 h-4" />
                                {{
                                    locale === "id"
                                        ? "Ya, Keluar"
                                        : "Yes, Sign Out"
                                }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
