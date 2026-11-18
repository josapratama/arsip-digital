<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Search,
    SlidersHorizontal,
    Upload,
    Layers,
    FileText,
    Image,
    File,
    X,
    ChevronLeft,
    ChevronRight,
    Download,
    Printer,
    ScanText,
    CheckSquare,
    Square,
    Loader2,
    CheckCircle2,
    Sparkles,
} from "lucide-vue-next";
import axios from "axios";
import { useI18n } from "@/useI18n.js";

const props = defineProps({
    documents: Object,
    filters: Object,
    categories: Array,
    can_create: Boolean,
});

const { t, locale } = useI18n();

const search = ref(props.filters?.q ?? "");
const selectedCategory = ref(props.filters?.category ?? "");
const selectedSubcategory = ref(props.filters?.subcategory ?? "");
const dateFrom = ref(props.filters?.date_from ?? "");
const dateTo = ref(props.filters?.date_to ?? "");
const ocrStatus = ref(props.filters?.ocr_status ?? "");
const showFilters = ref(false);

// Batch OCR Selection
const selectedDocIds = ref([]);
const isBatchOcrLoading = ref(false);
const batchMessage = ref("");

const allCurrentPageSelected = computed(() => {
    if (!props.documents.data || props.documents.data.length === 0) return false;
    return props.documents.data.every((doc) => selectedDocIds.value.includes(doc.id));
});

function toggleSelectAll() {
    if (allCurrentPageSelected.value) {
        selectedDocIds.value = [];
    } else {
        selectedDocIds.value = props.documents.data.map((doc) => doc.id);
    }
}

function toggleSelectDoc(id, e) {
    if (e) e.stopPropagation();
    if (selectedDocIds.value.includes(id)) {
        selectedDocIds.value = selectedDocIds.value.filter((item) => item !== id);
    } else {
        selectedDocIds.value.push(id);
    }
}

async function runBatchOcr() {
    if (selectedDocIds.value.length === 0) return;
    isBatchOcrLoading.value = true;
    batchMessage.value = "";
    try {
        const res = await axios.post(route("documents.batch-ocr"), {
            ids: selectedDocIds.value,
        });
        batchMessage.value =
            res.data?.message ||
            (locale.value === "id"
                ? "Proses OCR berhasil dijadwalkan!"
                : "Batch OCR scheduled successfully!");
        selectedDocIds.value = [];
        router.reload({ preserveScroll: true });
        setTimeout(() => {
            batchMessage.value = "";
        }, 3500);
    } catch (err) {
        console.error("Batch OCR error:", err);
        batchMessage.value =
            err.response?.data?.message ||
            (locale.value === "id"
                ? "Gagal memproses batch OCR."
                : "Failed batch OCR.");
        setTimeout(() => {
            batchMessage.value = "";
        }, 3500);
    } finally {
        isBatchOcrLoading.value = false;
    }
}

const selectedCategoryData = computed(() =>
    props.categories?.find((c) => c.id == selectedCategory.value),
);

const exportUrl = computed(() => {
    return route("documents.export", {
        q: search.value || undefined,
        category: selectedCategory.value || undefined,
        subcategory: selectedSubcategory.value || undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        ocr_status: ocrStatus.value || undefined,
    });
});

function printTable() {
    window.print();
}

function applyFilters() {
    router.get(
        route("documents.index"),
        {
            q: search.value || undefined,
            category: selectedCategory.value || undefined,
            subcategory: selectedSubcategory.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
            ocr_status: ocrStatus.value || undefined,
        },
        { preserveScroll: true },
    );
}

function resetFilters() {
    search.value = "";
    selectedCategory.value = "";
    selectedSubcategory.value = "";
    dateFrom.value = "";
    dateTo.value = "";
    ocrStatus.value = "";
    applyFilters();
}

function handleSearchKeydown(e) {
    if (e.key === "Enter") applyFilters();
}

const colorVariants = {
    blue: "bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300",
    green: "bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300",
    purple: "bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300",
    orange: "bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300",
    teal: "bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300",
    yellow: "bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300",
};
</script>

<template>
    <AppLayout>
        <Head :title="t('Dokumen', 'Documents')" />

        <!-- Stat strip + Action buttons -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                <span
                    class="font-semibold text-slate-700 dark:text-slate-200"
                    >{{ documents.total }}</span
                >
                {{ t("dokumen tersedia", "documents available") }}
                <span
                    v-if="filters.q || filters.category || filters.subcategory || filters.date_from || filters.date_to"
                    class="ml-2 inline-flex items-center gap-1 text-xs text-emerald-600 dark:text-emerald-400"
                >
                    · {{ t("terfilter", "filtered") }}
                </span>
            </p>
            <div class="flex items-center gap-2">
                <a
                    :href="exportUrl"
                    class="flex items-center gap-1.5 px-3 py-2 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-xs sm:text-sm font-medium transition-colors shadow-sm"
                    :title="t('Unduh rekap data CSV / Excel', 'Download CSV / Excel report')"
                >
                    <Download class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    <span>{{ t("Ekspor Rekap", "Export Report") }}</span>
                </a>
                <button
                    type="button"
                    @click="printTable"
                    class="hidden sm:flex items-center gap-1.5 px-3 py-2 border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-xl text-sm font-medium transition-colors shadow-sm"
                    :title="t('Cetak daftar dokumen', 'Print document list')"
                >
                    <Printer class="w-4 h-4 text-slate-500" />
                    <span>{{ t("Cetak", "Print") }}</span>
                </button>
                <Link
                    v-if="can_create !== false"
                    :href="route('documents.batch.create')"
                    class="flex items-center gap-1.5 px-3 py-2 border border-emerald-300 dark:border-emerald-700/60 bg-emerald-50 dark:bg-emerald-950/40 hover:bg-emerald-100 dark:hover:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 rounded-xl text-xs sm:text-sm font-semibold transition-colors shadow-sm"
                    :title="t('Unggah banyak berkas sekaligus', 'Upload multiple documents at once')"
                >
                    <Layers class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    <span>{{ t("Unggah Masal", "Batch Upload") }}</span>
                </Link>
                <Link
                    v-if="can_create !== false"
                    :href="route('documents.create')"
                    class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs sm:text-sm font-semibold transition-colors shadow-sm"
                >
                    <Upload class="w-4 h-4" />
                    {{ t("Unggah Dokumen", "Upload Document") }}
                </Link>
            </div>
        </div>

        <!-- Search & Filter -->
        <div
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 mb-5 overflow-hidden"
        >
            <div
                class="flex items-center gap-2 px-4 py-3 border-b border-slate-100 dark:border-slate-700"
            >
                <Search class="w-4.5 h-4.5 text-slate-400 shrink-0" />
                <input
                    v-model="search"
                    @keydown="handleSearchKeydown"
                    type="text"
                    :placeholder="
                        t(
                            'Cari isi dokumen, judul, nomor surat...',
                            'Search document content, title, number...',
                        )
                    "
                    class="flex-1 text-sm bg-transparent text-slate-900 dark:text-white placeholder:text-slate-400 outline-none"
                />
                <button
                    v-if="search"
                    @click="
                        search = '';
                        applyFilters();
                    "
                    class="text-slate-400 hover:text-slate-600"
                >
                    <X class="w-4 h-4" />
                </button>
                <button
                    @click="applyFilters"
                    class="px-3.5 py-1.5 bg-primary-600 hover:bg-primary-700 text-white text-xs font-medium rounded-lg transition-colors"
                >
                    {{ t("Cari", "Search") }}
                </button>
                <button
                    @click="showFilters = !showFilters"
                    :class="[
                        'flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                        showFilters
                            ? 'bg-slate-900 dark:bg-white text-white dark:text-slate-900'
                            : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300',
                    ]"
                >
                    <SlidersHorizontal class="w-3.5 h-3.5" />
                    <span class="hidden sm:inline">{{
                        t("Filter", "Filter")
                    }}</span>
                </button>
            </div>

            <!-- Expandable filters -->
            <div
                v-if="showFilters"
                class="px-4 py-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3"
            >
                <div>
                    <label
                        class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5"
                    >
                        {{ t("Kategori", "Category") }}
                    </label>
                    <select
                        v-model="selectedCategory"
                        @change="selectedSubcategory = ''"
                        class="w-full text-sm px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">
                            {{ t("Semua Kategori", "All Categories") }}
                        </option>
                        <option
                            v-for="cat in categories"
                            :key="cat.id"
                            :value="cat.id"
                        >
                            {{ cat.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label
                        class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5"
                    >
                        {{ t("Subkategori", "Subcategory") }}
                    </label>
                    <select
                        v-model="selectedSubcategory"
                        class="w-full text-sm px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                        :disabled="!selectedCategoryData?.children?.length"
                    >
                        <option value="">{{ t("Semua", "All") }}</option>
                        <option
                            v-for="sub in selectedCategoryData?.children"
                            :key="sub.id"
                            :value="sub.id"
                        >
                            {{ sub.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label
                        class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5"
                    >
                        {{ t("Status OCR", "OCR Status") }}
                    </label>
                    <select
                        v-model="ocrStatus"
                        class="w-full text-sm px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">{{ t("Semua Status OCR", "All OCR Status") }}</option>
                        <option value="completed">{{ t("Sudah Diekstrak OCR", "OCR Completed") }}</option>
                        <option value="pending">{{ t("Belum Diekstrak OCR", "OCR Pending") }}</option>
                    </select>
                </div>
                <div>
                    <label
                        class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5"
                    >
                        {{ t("Dari Tanggal", "Date From") }}
                    </label>
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="w-full text-sm px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                </div>
                <div>
                    <label
                        class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5"
                    >
                        {{ t("Sampai Tanggal", "Date To") }}
                    </label>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="w-full text-sm px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                </div>
                <div class="sm:col-span-2 lg:col-span-5 flex gap-2 justify-end">
                    <button
                        @click="resetFilters"
                        class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                    >
                        {{ t("Reset", "Reset") }}
                    </button>
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 text-sm bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors cursor-pointer"
                    >
                        {{ t("Terapkan", "Apply") }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Toast Feedback Batch OCR -->
        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="batchMessage"
                class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl bg-slate-900 text-white shadow-xl border border-slate-700 text-sm"
            >
                <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                <span>{{ batchMessage }}</span>
            </div>
        </transition>

        <!-- Batch Action Toolbar (Muncul saat dokumen dipilih) -->
        <div
            v-if="documents.data.length > 0"
            class="flex flex-wrap items-center justify-between gap-3 mb-4 px-4 py-2.5 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xs"
        >
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    @click="toggleSelectAll"
                    class="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:text-emerald-600 dark:hover:text-emerald-400 transition cursor-pointer"
                >
                    <component
                        :is="allCurrentPageSelected ? CheckSquare : Square"
                        class="w-4 h-4 text-emerald-600 dark:text-emerald-400"
                    />
                    <span>{{ allCurrentPageSelected ? t('Batalkan Semua', 'Deselect All') : t('Pilih Semua Dokumen', 'Select All') }}</span>
                </button>

                <span
                    v-if="selectedDocIds.length > 0"
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300"
                >
                    {{ selectedDocIds.length }} {{ t('terpilih', 'selected') }}
                </span>
            </div>

            <div v-if="selectedDocIds.length > 0" class="flex items-center gap-2">
                <button
                    type="button"
                    @click="runBatchOcr"
                    :disabled="isBatchOcrLoading"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold shadow-xs transition cursor-pointer disabled:opacity-50"
                >
                    <Loader2 v-if="isBatchOcrLoading" class="w-3.5 h-3.5 animate-spin" />
                    <ScanText v-else class="w-3.5 h-3.5" />
                    <span>{{ t('Jadwalkan OCR Ulang Terpilih', 'Batch Reprocess OCR') }} ({{ selectedDocIds.length }})</span>
                </button>

                <button
                    type="button"
                    @click="selectedDocIds = []"
                    class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 px-2 py-1 cursor-pointer"
                >
                    {{ t('Batal', 'Clear') }}
                </button>
            </div>
        </div>

        <!-- Document grid -->
        <div
            v-if="documents.data.length === 0"
            class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 py-16 text-center"
        >
            <FileText
                class="w-12 h-12 text-slate-300 dark:text-slate-600 mx-auto mb-3"
            />
            <p class="text-slate-500 dark:text-slate-400 font-medium">
                {{ t("Dokumen tidak ditemukan.", "No documents found.") }}
            </p>
            <p class="text-sm text-slate-400 mt-1">
                {{
                    t(
                        "Coba ubah kata kunci pencarian atau filter.",
                        "Try adjusting your search or filters.",
                    )
                }}
            </p>
        </div>

        <div
            v-else
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
        >
            <div
                v-for="doc in documents.data"
                :key="doc.id"
                class="relative group bg-white dark:bg-slate-800 rounded-2xl border transition-all overflow-hidden flex flex-col justify-between"
                :class="[
                    selectedDocIds.includes(doc.id)
                        ? 'border-emerald-500 ring-2 ring-emerald-500/20 shadow-md'
                        : 'border-slate-200 dark:border-slate-700 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md'
                ]"
            >
                <Link
                    :href="route('documents.show', doc.id)"
                    class="flex-1 block"
                >
                    <!-- File type indicator -->
                    <div
                        class="h-2 w-full"
                        :class="{
                            'bg-primary-500': doc.is_image,
                            'bg-rose-500': doc.is_pdf,
                            'bg-blue-600': doc.is_word,
                            'bg-amber-500': doc.is_text,
                            'bg-slate-400': !doc.is_image && !doc.is_pdf && !doc.is_word && !doc.is_text,
                        }"
                    ></div>
                    <div class="p-4">
                        <div class="flex items-start gap-3 mb-3">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                :class="{
                                    'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400': doc.is_image,
                                    'bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400': doc.is_pdf,
                                    'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400': doc.is_word,
                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400': doc.is_text,
                                    'bg-slate-100 dark:bg-slate-700 text-slate-500': !doc.is_image && !doc.is_pdf && !doc.is_word && !doc.is_text,
                                }"
                            >
                                <Image
                                    v-if="doc.is_image"
                                    class="w-5 h-5"
                                />
                                <span v-else-if="doc.is_word" class="font-bold text-sm">W</span>
                                <FileText
                                    v-else-if="doc.is_text"
                                    class="w-5 h-5"
                                />
                                <File
                                    v-else
                                    class="w-5 h-5 text-rose-600 dark:text-rose-400"
                                />
                            </div>
                            <div class="flex-1 min-w-0 pr-6">
                                <p
                                    class="text-sm font-semibold text-slate-900 dark:text-white line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 leading-snug"
                                >
                                    {{ doc.title }}
                                </p>
                                <p
                                    v-if="doc.document_number || doc.nomor_surat"
                                    class="text-xs text-slate-400 mt-0.5 truncate"
                                >
                                    {{ doc.document_number || doc.nomor_surat }}
                                </p>
                            </div>
                        </div>

                        <!-- OCR Snippet Match Card (jika sedang dicari) -->
                        <div
                            v-if="doc.ocr_snippet"
                            class="mb-3 p-2.5 rounded-xl bg-amber-50/90 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-[11px] leading-relaxed text-slate-700 dark:text-slate-300"
                        >
                            <span class="font-bold text-amber-700 dark:text-amber-400 uppercase text-[10px] tracking-wide block mb-0.5">
                                {{ t("Kutipan Teks Dokumen:", "Document Text Match:") }}
                            </span>
                            <span class="italic font-mono">"...{{ doc.ocr_snippet }}..."</span>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span
                                    v-if="doc.classification_code || doc.kode_klasifikasi"
                                    class="inline-block px-1.5 py-0.5 rounded text-[11px] font-mono font-bold bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300 border border-amber-300 dark:border-amber-700/60"
                                    :title="'Klasifikasi Permendagri: ' + (doc.classification_code || doc.kode_klasifikasi)"
                                >
                                    {{ doc.classification_code || doc.kode_klasifikasi }}
                                </span>
                                <span
                                    :class="[
                                        'inline-block px-2 py-0.5 rounded-md text-xs font-medium',
                                        colorVariants[doc.category?.color] ||
                                            colorVariants.blue,
                                    ]"
                                >
                                    {{ doc.category?.name }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between text-xs text-slate-400 pt-1"
                            >
                                <span>{{
                                    doc.document_date || doc.created_at
                                }}</span>
                                <div class="flex items-center gap-2">
                                    <span
                                        v-if="doc.ocr_processed"
                                        class="inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                                        :title="t('Sudah diproses OCR', 'OCR Processed')"
                                    >
                                        <CheckCircle2 class="w-3 h-3 text-emerald-500" />
                                        <span>OCR</span>
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-[10px] font-medium px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700/60 text-slate-500"
                                        :title="t('Belum diproses OCR', 'Not OCR Processed')"
                                    >
                                        <span>Pending</span>
                                    </span>
                                    <span>{{ doc.file_size_formatted }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </Link>

                <!-- Checkbox Seleksi Batch (Top Right Card) -->
                <button
                    type="button"
                    @click="(e) => toggleSelectDoc(doc.id, e)"
                    class="absolute top-4 right-3 p-1 rounded-md text-slate-400 hover:text-emerald-600 transition cursor-pointer z-10"
                    :title="t('Pilih dokumen untuk proses masal', 'Select document for batch action')"
                >
                    <component
                        :is="selectedDocIds.includes(doc.id) ? CheckSquare : Square"
                        class="w-4.5 h-4.5"
                        :class="selectedDocIds.includes(doc.id) ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-300 dark:text-slate-600'"
                    />
                </button>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="documents.last_page > 1"
            class="flex items-center justify-between mt-6"
        >
            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ t("Menampilkan", "Showing") }} {{ documents.from }}–{{
                    documents.to
                }}
                {{ t("dari", "of") }} {{ documents.total }}
            </p>
            <div class="flex items-center gap-1">
                <Link
                    v-if="documents.prev_page_url"
                    :href="documents.prev_page_url"
                    class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                >
                    <ChevronLeft class="w-4 h-4" />
                </Link>
                <span
                    class="px-3 py-1.5 text-sm font-medium text-slate-700 dark:text-slate-300"
                >
                    {{ documents.current_page }} / {{ documents.last_page }}
                </span>
                <Link
                    v-if="documents.next_page_url"
                    :href="documents.next_page_url"
                    class="p-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                >
                    <ChevronRight class="w-4 h-4" />
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
