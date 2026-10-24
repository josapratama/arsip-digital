<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    FolderArchive,
    FileText,
    CalendarDays,
    Trash2,
    ArrowRight,
    Upload,
    Search,
} from "lucide-vue-next";
import { useI18n } from "@/useI18n.js";

const props = defineProps({
    stats: Object,
    recentDocuments: Array,
    categoryStats: Array,
    recentActivity: Array,
});

const { t, locale } = useI18n();

const statCards = computed(() => [
    {
        label: t("Total Dokumen", "Total Documents"),
        value: props.stats.total_documents,
        icon: FolderArchive,
        color: "bg-primary-600",
        bg: "bg-primary-50 dark:bg-primary-950/40",
        text: "text-primary-700 dark:text-primary-300",
    },
    {
        label: t("Kategori", "Categories"),
        value: props.stats.total_categories,
        icon: FileText,
        color: "bg-teal-600",
        bg: "bg-teal-50 dark:bg-teal-950/40",
        text: "text-teal-700 dark:text-teal-300",
    },
    {
        label: t("Bulan Ini", "This Month"),
        value: props.stats.documents_this_month,
        icon: CalendarDays,
        color: "bg-emerald-600",
        bg: "bg-emerald-50 dark:bg-emerald-950/40",
        text: "text-emerald-700 dark:text-emerald-300",
    },
    {
        label: t("Kotak Sampah", "Recycle Bin"),
        value: props.stats.recycle_bin_count,
        icon: Trash2,
        color: "bg-rose-500",
        bg: "bg-rose-50 dark:bg-rose-950/40",
        text: "text-rose-600 dark:text-rose-400",
    },
]);

const colorMap = {
    emerald: "bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300",
    green: "bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300",
    blue: "bg-sky-100 dark:bg-sky-950/50 text-sky-700 dark:text-sky-300",
    teal: "bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-300",
    purple: "bg-purple-100 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300",
    orange: "bg-orange-100 dark:bg-orange-950/50 text-orange-700 dark:text-orange-300",
};

const actionColorMap = {
    upload: "bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300",
    edit: "bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300",
    delete: "bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300",
    restore:
        "bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-300",
    view: "bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300",
    login: "bg-primary-100 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300",
    ocr: "bg-primary-100 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300",
};
</script>

<template>
    <AppLayout>
        <Head :title="t('Dasbor', 'Dashboard')" />

        <!-- Page subtitle only — title shown in topbar -->
        <div
            class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6"
        >
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{
                    t(
                        "Selamat datang di Sistem Pengarsipan Digital Desa Sukarami",
                        "Welcome to Desa Sukarami Digital Archiving System",
                    )
                }}
            </p>
            <div class="flex gap-2 shrink-0">
                <Link
                    :href="route('documents.index')"
                    class="flex items-center gap-2 px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                >
                    <Search class="w-4 h-4" />
                    {{ t("Cari", "Search") }}
                </Link>
                <Link
                    :href="route('documents.create')"
                    class="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-medium transition-colors"
                >
                    <Upload class="w-4 h-4" />
                    {{ t("Unggah", "Upload") }}
                </Link>
            </div>
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
            <div
                v-for="stat in statCards"
                :key="stat.label"
                class="bg-white dark:bg-slate-800 rounded-2xl p-4 sm:p-5 border border-slate-200 dark:border-slate-700"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <p
                            class="text-xs sm:text-sm text-slate-500 dark:text-slate-400"
                        >
                            {{ stat.label }}
                        </p>
                        <p
                            class="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mt-1"
                        >
                            {{ stat.value ?? 0 }}
                        </p>
                    </div>
                    <div
                        :class="[
                            'w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center',
                            stat.bg,
                        ]"
                    >
                        <component
                            :is="stat.icon"
                            :class="['w-4.5 h-4.5 sm:w-5 sm:h-5', stat.text]"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Main grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 sm:gap-6">
            <!-- Recent documents -->
            <div
                class="xl:col-span-2 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700"
            >
                <div
                    class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700"
                >
                    <h2 class="font-semibold text-slate-900 dark:text-white">
                        {{ t("Dokumen Terbaru", "Recent Documents") }}
                    </h2>
                    <Link
                        :href="route('documents.index')"
                        class="flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium"
                    >
                        {{ t("Lihat Semua", "View All") }}
                        <ArrowRight class="w-3.5 h-3.5" />
                    </Link>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-700">
                    <div
                        v-if="!recentDocuments?.length"
                        class="px-5 py-8 text-center text-sm text-slate-400"
                    >
                        {{ t("Belum ada dokumen.", "No documents yet.") }}
                    </div>
                    <Link
                        v-for="doc in recentDocuments"
                        :key="doc.id"
                        :href="route('documents.show', doc.id)"
                        class="flex items-center gap-3 px-5 py-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors"
                    >
                        <div
                            class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center shrink-0"
                        >
                            <FileText
                                class="w-4.5 h-4.5 text-slate-500 dark:text-slate-400"
                            />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm font-medium text-slate-900 dark:text-white truncate"
                            >
                                {{ doc.title }}
                            </p>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400 truncate"
                            >
                                {{ doc.category }}
                                {{
                                    doc.subcategory
                                        ? `› ${doc.subcategory}`
                                        : ""
                                }}
                                · {{ doc.created_at }}
                            </p>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Right column -->
            <div class="space-y-4 sm:space-y-6">
                <!-- Category overview -->
                <div
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700"
                >
                    <div
                        class="px-5 py-4 border-b border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("Ringkasan Kategori", "Category Overview") }}
                        </h2>
                    </div>
                    <div class="p-4 space-y-2.5">
                        <div
                            v-for="cat in categoryStats"
                            :key="cat.id"
                            class="flex items-center gap-3"
                        >
                            <span
                                :class="[
                                    'px-2.5 py-1.5 rounded-lg text-xs font-medium flex-1 min-w-0',
                                    colorMap[cat.color] || colorMap.blue,
                                ]"
                            >
                                {{
                                    locale === "en" && cat.name_en
                                        ? cat.name_en
                                        : cat.name
                                }}
                            </span>
                            <span
                                class="text-sm font-semibold text-slate-700 dark:text-slate-300 shrink-0"
                            >
                                {{ cat.documents_count }}
                            </span>
                        </div>
                        <div
                            v-if="!categoryStats?.length"
                            class="text-center text-sm text-slate-400 py-4"
                        >
                            -
                        </div>
                    </div>
                </div>

                <!-- Recent activity -->
                <div
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700"
                >
                    <div
                        class="flex items-center justify-between px-5 py-4 border-b border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("Aktivitas Terbaru", "Recent Activity") }}
                        </h2>
                        <Link
                            :href="route('activity-log.index')"
                            class="text-xs text-primary-600 dark:text-primary-400 hover:underline font-medium"
                        >
                            {{ t("Semua", "All") }}
                        </Link>
                    </div>
                    <div
                        class="divide-y divide-slate-100 dark:divide-slate-700"
                    >
                        <div
                            v-if="!recentActivity?.length"
                            class="px-5 py-6 text-center text-sm text-slate-400"
                        >
                            {{ t("Belum ada aktivitas.", "No activity yet.") }}
                        </div>
                        <div
                            v-for="log in recentActivity"
                            :key="log.id"
                            class="px-5 py-3"
                        >
                            <div class="flex items-start gap-2.5">
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-md text-xs font-medium shrink-0 mt-0.5',
                                        actionColorMap[log.action] ||
                                            actionColorMap.view,
                                    ]"
                                >
                                    {{ log.action }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p
                                        class="text-xs text-slate-700 dark:text-slate-300 line-clamp-2"
                                    >
                                        {{ log.description }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-0.5">
                                        {{ log.user }} · {{ log.created_at }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
