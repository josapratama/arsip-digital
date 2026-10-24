<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Search,
    SlidersHorizontal,
    Upload,
    FileText,
    Image,
    File,
    X,
    ChevronLeft,
    ChevronRight,
    Download,
    Printer,
} from "lucide-vue-next";
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
const showFilters = ref(false);

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
                class="px-4 py-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3"
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
                <div class="sm:col-span-2 lg:col-span-4 flex gap-2 justify-end">
                    <button
                        @click="resetFilters"
                        class="px-4 py-2 text-sm text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-600 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        {{ t("Reset", "Reset") }}
                    </button>
                    <button
                        @click="applyFilters"
                        class="px-4 py-2 text-sm bg-primary-600 hover:bg-primary-700 text-white rounded-lg font-medium transition-colors"
                    >
                        {{ t("Terapkan", "Apply") }}
                    </button>
                </div>
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
            <Link
                v-for="doc in documents.data"
                :key="doc.id"
                :href="route('documents.show', doc.id)"
                class="group bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 hover:border-primary-300 dark:hover:border-primary-600 hover:shadow-md transition-all overflow-hidden"
            >
                <!-- File type indicator -->
                <div
                    class="h-2 w-full"
                    :class="{
                        'bg-primary-500': doc.is_image,
                        'bg-rose-500': !doc.is_image,
                    }"
                ></div>
                <div class="p-4">
                    <div class="flex items-start gap-3 mb-3">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                            :class="
                                doc.is_image
                                    ? 'bg-primary-100 dark:bg-primary-900/30'
                                    : 'bg-rose-100 dark:bg-rose-900/30'
                            "
                        >
                            <Image
                                v-if="doc.is_image"
                                class="w-5 h-5 text-primary-600 dark:text-primary-400"
                            />
                            <File
                                v-else
                                class="w-5 h-5 text-rose-600 dark:text-rose-400"
                            />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm font-semibold text-slate-900 dark:text-white line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 leading-snug"
                            >
                                {{ doc.title }}
                            </p>
                            <p
                                v-if="doc.nomor_surat"
                                class="text-xs text-slate-400 mt-0.5 truncate"
                            >
                                {{ doc.nomor_surat }}
                            </p>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <span
                            :class="[
                                'inline-block px-2 py-0.5 rounded-md text-xs font-medium',
                                colorVariants[doc.category?.color] ||
                                    colorVariants.blue,
                            ]"
                        >
                            {{ doc.category?.name }}
                        </span>
                        <div
                            class="flex items-center justify-between text-xs text-slate-400"
                        >
                            <span>{{
                                doc.document_date || doc.created_at
                            }}</span>
                            <span class="flex items-center gap-1">
                                <span
                                    v-if="doc.ocr_processed"
                                    class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"
                                ></span>
                                {{ doc.file_size_formatted }}
                            </span>
                        </div>
                    </div>
                </div>
            </Link>
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
