<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Trash2, RotateCcw, AlertTriangle, FileText } from "lucide-vue-next";
import { useI18n } from "@/useI18n.js";

const props = defineProps({ documents: Object });

const { t } = useI18n();

const confirmId = ref(null);

function restore(id) {
    router.post(route("recycle-bin.restore", id), {}, { preserveScroll: true });
}

function forceDelete(id) {
    router.delete(route("recycle-bin.force-delete", id), {
        preserveScroll: true,
        onSuccess: () => {
            confirmId.value = null;
        },
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('Recycle Bin', 'Recycle Bin')" />

        <!-- Recycle Bin: hanya tampilkan info jumlah -->
        <p class="text-sm text-slate-500 dark:text-slate-400 mb-4">
            <span class="font-semibold text-slate-700 dark:text-slate-200">{{
                documents.total
            }}</span>
            {{ t("dokumen di sampah", "documents in bin") }}
        </p>

        <!-- Empty state -->
        <div
            v-if="documents.data.length === 0"
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 py-16 text-center"
        >
            <Trash2
                class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3"
            />
            <p class="text-slate-500 dark:text-slate-400 font-medium">
                {{ t("Recycle bin kosong.", "Recycle bin is empty.") }}
            </p>
        </div>

        <!-- List -->
        <div
            v-else
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 divide-y divide-slate-100 dark:divide-slate-700"
        >
            <div
                v-for="doc in documents.data"
                :key="doc.id"
                class="flex items-center gap-4 px-5 py-4"
            >
                <div
                    class="w-9 h-9 rounded-lg bg-slate-100 dark:bg-slate-700 flex items-center justify-center shrink-0"
                >
                    <FileText class="w-4.5 h-4.5 text-slate-400" />
                </div>
                <div class="flex-1 min-w-0">
                    <p
                        class="text-sm font-medium text-slate-900 dark:text-white truncate"
                    >
                        {{ doc.title }}
                    </p>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ doc.category }} · {{ t("Dihapus", "Deleted") }}
                        {{ doc.deleted_at }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        @click="restore(doc.id)"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 rounded-lg text-xs font-medium hover:bg-emerald-100 dark:hover:bg-emerald-900/40 transition-colors"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                        {{ t("Pulihkan", "Restore") }}
                    </button>
                    <button
                        @click="confirmId = doc.id"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg text-xs font-medium hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">{{
                            t("Hapus Permanen", "Delete Permanently")
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Confirm modal -->
        <Teleport to="body">
            <div
                v-if="confirmId"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            >
                <div
                    class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-sm p-6"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center"
                        >
                            <AlertTriangle
                                class="w-5 h-5 text-red-600 dark:text-red-400"
                            />
                        </div>
                        <h3
                            class="text-base font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("Hapus Permanen?", "Delete Permanently?") }}
                        </h3>
                    </div>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">
                        {{
                            t(
                                "Tindakan ini tidak dapat dibatalkan. File akan dihapus selamanya.",
                                "This action cannot be undone. The file will be deleted forever.",
                            )
                        }}
                    </p>
                    <div class="flex gap-3 justify-end">
                        <button
                            @click="confirmId = null"
                            class="px-4 py-2 text-sm font-medium border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            {{ t("Batal", "Cancel") }}
                        </button>
                        <button
                            @click="forceDelete(confirmId)"
                            class="px-4 py-2 text-sm font-medium bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors"
                        >
                            {{ t("Ya, Hapus", "Yes, Delete") }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
