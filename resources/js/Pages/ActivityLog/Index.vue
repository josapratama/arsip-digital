<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Search, Filter, RotateCcw, Activity, User, ShieldAlert } from "lucide-vue-next";
import { useI18n } from "@/useI18n.js";

const props = defineProps({
    logs: Object,
    filters: Object,
    users: Array,
});

const { t } = useI18n();

const search = ref(props.filters?.q ?? "");
const selectedAction = ref(props.filters?.action ?? "");
const selectedUser = ref(props.filters?.user_id ?? "");

function applyFilters() {
    router.get(
        route("activity-log.index"),
        {
            q: search.value || undefined,
            action: selectedAction.value || undefined,
            user_id: selectedUser.value || undefined,
        },
        { preserveScroll: true, preserveState: true }
    );
}

function resetFilters() {
    search.value = "";
    selectedAction.value = "";
    selectedUser.value = "";
    applyFilters();
}

function handleSearchKeydown(e) {
    if (e.key === "Enter") {
        applyFilters();
    }
}

const actionColors = {
    upload: "bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800",
    edit: "bg-amber-100 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800",
    delete: "bg-rose-100 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800",
    restore: "bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800",
    view: "bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-slate-700",
    login: "bg-primary-100 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 border-primary-200 dark:border-primary-800",
    logout: "bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700",
    ocr: "bg-emerald-100 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-200 border-emerald-300 dark:border-emerald-700",
    force_delete: "bg-red-100 dark:bg-red-950/50 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800",
    settings: "bg-primary-100 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 border-primary-200 dark:border-primary-800",
};
</script>

<template>
    <AppLayout>
        <Head :title="t('Riwayat Aktivitas', 'Activity Log')" />

        <div class="space-y-5">
            <!-- Header & Filter card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 flex items-center justify-center">
                            <Activity class="w-5 h-5" />
                        </div>
                        <div>
                            <h1 class="text-base font-bold text-slate-900 dark:text-white">
                                {{ t("Riwayat Aktivitas Pengguna", "User Activity Log") }}
                            </h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ t("Audit jejak digital seluruh kegiatan arsip dan autentikasi", "Audit trail of all archiving and authentication actions") }}
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-primary-50 dark:bg-primary-950/50 text-primary-700 dark:text-primary-300 border border-primary-200 dark:border-primary-800 self-start sm:self-auto">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-500 animate-pulse"></span>
                        {{ logs.total }} {{ t("Total Aktivitas", "Total Activities") }}
                    </span>
                </div>

                <!-- Filters Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-3 border-t border-slate-100 dark:border-slate-700/60">
                    <!-- Search query -->
                    <div class="relative">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="search"
                            @keydown="handleSearchKeydown"
                            type="text"
                            :placeholder="t('Cari deskripsi aktivitas...', 'Search activity description...')"
                            class="w-full pl-9 pr-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                    </div>

                    <!-- Action select -->
                    <div>
                        <select
                            v-model="selectedAction"
                            @change="applyFilters"
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="">{{ t("Semua Jenis Aksi", "All Action Types") }}</option>
                            <option value="upload">{{ t("Unggah Dokumen (Upload)", "Upload Document") }}</option>
                            <option value="edit">{{ t("Edit Dokumen", "Edit Document") }}</option>
                            <option value="delete">{{ t("Pindah ke Sampah (Delete)", "Move to Trash") }}</option>
                            <option value="restore">{{ t("Pemulihan Dokumen (Restore)", "Restore Document") }}</option>
                            <option value="force_delete">{{ t("Hapus Permanen", "Permanent Delete") }}</option>
                            <option value="ocr">{{ t("Proses OCR", "OCR Process") }}</option>
                            <option value="view">{{ t("Melihat Dokumen", "View Document") }}</option>
                            <option value="login">{{ t("Masuk (Login)", "Login") }}</option>
                            <option value="logout">{{ t("Keluar (Logout)", "Logout") }}</option>
                            <option value="settings">{{ t("Pengaturan Website", "Site Settings") }}</option>
                        </select>
                    </div>

                    <!-- User select -->
                    <div>
                        <select
                            v-model="selectedUser"
                            @change="applyFilters"
                            class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="">{{ t("Semua Pengguna", "All Users") }}</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">
                                {{ u.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Action buttons -->
                    <div class="flex items-center gap-2">
                        <button
                            @click="applyFilters"
                            class="flex-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-semibold transition-colors flex items-center justify-center gap-1.5 shadow-sm"
                        >
                            <Filter class="w-3.5 h-3.5" />
                            {{ t("Terapkan", "Apply") }}
                        </button>
                        <button
                            v-if="search || selectedAction || selectedUser"
                            @click="resetFilters"
                            :title="t('Reset filter', 'Reset filter')"
                            class="p-2 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            <RotateCcw class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100 dark:border-slate-700 bg-slate-50/70 dark:bg-slate-900/50">
                                <th class="text-left px-5 py-3.5 font-semibold text-slate-600 dark:text-slate-300 text-xs uppercase tracking-wider">
                                    {{ t("Aksi", "Action") }}
                                </th>
                                <th class="text-left px-5 py-3.5 font-semibold text-slate-600 dark:text-slate-300 text-xs uppercase tracking-wider">
                                    {{ t("Keterangan", "Description") }}
                                </th>
                                <th class="text-left px-5 py-3.5 font-semibold text-slate-600 dark:text-slate-300 text-xs uppercase tracking-wider hidden sm:table-cell">
                                    {{ t("Pengguna", "User") }}
                                </th>
                                <th class="text-left px-5 py-3.5 font-semibold text-slate-600 dark:text-slate-300 text-xs uppercase tracking-wider hidden md:table-cell">
                                    IP Address
                                </th>
                                <th class="text-right px-5 py-3.5 font-semibold text-slate-600 dark:text-slate-300 text-xs uppercase tracking-wider">
                                    {{ t("Waktu", "Time") }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="logs.data.length === 0">
                                <td colspan="5" class="px-5 py-16 text-center text-slate-400">
                                    <div class="max-w-xs mx-auto">
                                        <Activity class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                                        <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                                            {{ t("Tidak ada riwayat aktivitas ditemukan.", "No activity logs found.") }}
                                        </p>
                                        <p class="text-xs text-slate-400 mt-1">
                                            {{ t("Coba ubah kata kunci pencarian atau filter yang dipilih.", "Try changing the search keyword or selected filters.") }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30 transition-colors"
                            >
                                <td class="px-5 py-3.5 whitespace-nowrap">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-lg text-xs font-semibold border inline-flex items-center gap-1 uppercase tracking-wide',
                                            actionColors[log.action] || actionColors.view,
                                        ]"
                                    >
                                        {{ log.action }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-800 dark:text-slate-200 max-w-sm">
                                    <span class="line-clamp-2 font-medium">{{ log.description }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300 hidden sm:table-cell whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-primary-100 dark:bg-primary-950/60 text-primary-700 dark:text-primary-300 flex items-center justify-center text-xs font-bold">
                                            {{ (log.user || 'S').charAt(0).toUpperCase() }}
                                        </div>
                                        <span>{{ log.user }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-400 font-mono text-xs hidden md:table-cell whitespace-nowrap">
                                    {{ log.ip_address || '-' }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 text-right text-xs whitespace-nowrap font-medium">
                                    {{ log.created_at }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div
                    v-if="logs.last_page > 1"
                    class="flex items-center justify-between px-5 py-4 border-t border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30"
                >
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ t("Menampilkan", "Showing") }}
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ logs.from }}</span>–<span class="font-semibold text-slate-700 dark:text-slate-200">{{ logs.to }}</span>
                        {{ t("dari", "of") }}
                        <span class="font-semibold text-slate-700 dark:text-slate-200">{{ logs.total }}</span>
                    </p>
                    <div class="flex gap-1.5">
                        <Link
                            v-if="logs.prev_page_url"
                            :href="logs.prev_page_url"
                            class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-700 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 transition-colors shadow-sm"
                        >
                            ← {{ t("Sebelumnya", "Prev") }}
                        </Link>
                        <Link
                            v-if="logs.next_page_url"
                            :href="logs.next_page_url"
                            class="px-3 py-1.5 text-xs font-medium border border-slate-200 dark:border-slate-700 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-700 transition-colors shadow-sm"
                        >
                            {{ t("Berikutnya", "Next") }} →
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
