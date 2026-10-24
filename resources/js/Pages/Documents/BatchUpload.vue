<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Upload,
    Layers,
    FileText,
    Image,
    File,
    Trash2,
    CheckCircle2,
    AlertCircle,
    Loader2,
    ArrowLeft,
    Sparkles,
    Calendar,
    Folder,
    Building2,
    RotateCw,
    X,
} from "lucide-vue-next";
import axios from "axios";
import { parseOcrText } from "@/utils/ocrParser.js";
import { useI18n } from "@/useI18n.js";

const props = defineProps({
    categories: Array,
    kemendagri_list: Array,
});

const { t } = useI18n();

// Global Batch Settings (apply to all)
const batchCategory = ref("");
const batchSubcategory = ref("");
const batchDate = ref("");
const batchKlasifikasi = ref("");

// Items in queue
// Each item: {
//   id: string,
//   file: File,
//   title: string,
//   nomor_surat: string,
//   kode_klasifikasi: string,
//   category_id: string|number,
//   subcategory_id: string|number,
//   document_date: string,
//   description: string,
//   ocr_text: string,
//   ocr_loading: boolean,
//   ocr_done: boolean,
//   ocr_error: string,
//   detected_reason: string,
// }
const items = ref([]);
const isDragging = ref(false);
const isSubmitting = ref(false);
const submitProgress = ref(0);
const submitError = ref("");

const activeBatchCategory = computed(() =>
    props.categories?.find((c) => c.id == batchCategory.value),
);

function getFileCategoryIcon(item) {
    const name = item.file.name.toLowerCase();
    const type = item.file.type || "";
    if (type.startsWith("image/")) return "image";
    if (type === "application/pdf" || name.endsWith(".pdf")) return "pdf";
    if (name.endsWith(".docx") || name.endsWith(".doc")) return "word";
    return "text";
}

function formatBytes(bytes) {
    if (!bytes || bytes === 0) return "0 B";
    const k = 1024;
    const sizes = ["B", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + " " + sizes[i];
}

function cleanFilenameTitle(filename) {
    return filename
        .replace(/\.[^/.]+$/, "") // strip extension
        .replace(/[-_]+/g, " ")   // replace dashes/underscores with space
        .replace(/\s+/g, " ")      // collapse spaces
        .trim();
}

function handleFilesAdded(fileList) {
    const files = Array.from(fileList || []);
    if (!files.length) return;

    files.forEach((file) => {
        // Enforce max 50 items
        if (items.value.length >= 50) return;

        // Skip if same file is already in queue
        if (items.value.some((it) => it.file.name === file.name && it.file.size === file.size)) {
            return;
        }

        const id = "item_" + Math.random().toString(36).substr(2, 9);
        const newItem = {
            id,
            file,
            title: cleanFilenameTitle(file.name),
            nomor_surat: "",
            kode_klasifikasi: batchKlasifikasi.value || "",
            category_id: batchCategory.value || "",
            subcategory_id: batchSubcategory.value || "",
            document_date: batchDate.value || "",
            description: "",
            ocr_text: "",
            ocr_loading: false,
            ocr_done: false,
            ocr_error: "",
            detected_reason: "",
        };

        items.value.push(newItem);

        // Run OCR in background
        processItemOcr(newItem);
    });
}

function onFileSelect(e) {
    handleFilesAdded(e.target.files);
    e.target.value = ""; // reset input
}

function onDrop(e) {
    isDragging.value = false;
    handleFilesAdded(e.dataTransfer.files);
}

function removeItem(index) {
    items.value.splice(index, 1);
}

function clearAll() {
    if (confirm(t("Kosongkan semua berkas dari antrean?", "Clear all documents from the queue?"))) {
        items.value = [];
    }
}

// Background OCR processor for each item
async function processItemOcr(item) {
    item.ocr_loading = true;
    item.ocr_error = "";

    try {
        const formData = new FormData();
        formData.append("file", item.file);

        const res = await axios.post(route("documents.ocr-preview"), formData, {
            headers: {
                "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]")?.content,
            },
        });

        if (res.data.success) {
            item.ocr_text = res.data.text || "";
            item.ocr_done = true;

            // 1. Metadata from OCR service
            if (res.data.metadata) {
                if (res.data.metadata.title && item.title === cleanFilenameTitle(item.file.name)) {
                    item.title = res.data.metadata.title;
                }
                if (res.data.metadata.nomor_surat && !item.nomor_surat) {
                    item.nomor_surat = res.data.metadata.nomor_surat;
                }
                if (res.data.metadata.document_date && !item.document_date) {
                    item.document_date = res.data.metadata.document_date;
                }
            }

            // 2. Parser fallback
            const parsed = parseOcrText(item.ocr_text);
            if (parsed.judul && item.title === cleanFilenameTitle(item.file.name)) {
                item.title = parsed.judul;
            }
            if (parsed.nomorSurat && !item.nomor_surat) {
                item.nomor_surat = parsed.nomorSurat;
            }
            if (parsed.tanggal && !item.document_date) {
                item.document_date = parsed.tanggal;
            }

            // 3. Kemendagri suggestion & Category auto-mapping
            if (res.data.kemendagri) {
                if (!item.kode_klasifikasi) {
                    item.kode_klasifikasi = res.data.kemendagri.kode;
                }
                if (!item.category_id && res.data.kemendagri.category_id) {
                    item.category_id = res.data.kemendagri.category_id;
                    if (res.data.kemendagri.subcategory_id) {
                        item.subcategory_id = res.data.kemendagri.subcategory_id;
                    }
                }
                item.detected_reason = `${res.data.kemendagri.kode} (${res.data.kemendagri.nama})`;
            }
        } else {
            item.ocr_error = res.data.error || "OCR gagal";
        }
    } catch (err) {
        item.ocr_error = "Gagal memproses OCR";
    } finally {
        item.ocr_loading = false;
    }
}

// Batch apply
function applyBatchSettings() {
    items.value.forEach((it) => {
        if (batchCategory.value) {
            it.category_id = batchCategory.value;
            it.subcategory_id = batchSubcategory.value || "";
        }
        if (batchDate.value) {
            it.document_date = batchDate.value;
        }
        if (batchKlasifikasi.value) {
            it.kode_klasifikasi = batchKlasifikasi.value;
        }
    });
}

// Validation before submit
const isValid = computed(() => {
    if (!items.value.length) return false;
    return items.value.every((it) => it.title.trim() && it.category_id);
});

const invalidCount = computed(() => {
    return items.value.filter((it) => !it.title.trim() || !it.category_id).length;
});

// Submit all items
async function submitBatch() {
    if (!isValid.value || isSubmitting.value) return;

    isSubmitting.value = true;
    submitError.value = "";
    submitProgress.value = 0;

    const formData = new FormData();

    items.value.forEach((item, index) => {
        formData.append(`items[${index}][title]`, item.title.trim());
        if (item.nomor_surat) {
            formData.append(`items[${index}][nomor_surat]`, item.nomor_surat.trim());
        }
        if (item.kode_klasifikasi) {
            formData.append(`items[${index}][kode_klasifikasi]`, item.kode_klasifikasi.trim());
        }
        formData.append(`items[${index}][category_id]`, item.category_id);
        if (item.subcategory_id) {
            formData.append(`items[${index}][subcategory_id]`, item.subcategory_id);
        }
        if (item.document_date) {
            formData.append(`items[${index}][document_date]`, item.document_date);
        }
        if (item.description) {
            formData.append(`items[${index}][description]`, item.description.trim());
        }
        if (item.ocr_text) {
            formData.append(`items[${index}][ocr_text]`, item.ocr_text);
        }
        formData.append(`files[${index}]`, item.file);
    });

    try {
        await router.post(route("documents.batch.store"), formData, {
            forceFormData: true,
            onProgress: (p) => {
                if (p.percentage) {
                    submitProgress.value = p.percentage;
                }
            },
            onError: (errors) => {
                submitError.value = errors.error || Object.values(errors)[0] || "Gagal mengunggah dokumen masal.";
                isSubmitting.value = false;
            },
        });
    } catch (err) {
        submitError.value = "Terjadi gangguan koneksi saat menyimpan dokumen masal.";
        isSubmitting.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <Head :title="t('Unggah Masal Dokumen', 'Batch Upload Documents')" />

        <div class="max-w-6xl mx-auto space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('documents.index')"
                        class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        <ArrowLeft class="w-4.5 h-4.5" />
                    </Link>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <Layers class="w-6 h-6 text-emerald-600 dark:text-emerald-400" />
                            {{ t("Unggah Dokumen Masal", "Batch Document Upload") }}
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ t("Unggah hingga 50 file sekaligus dengan pembacaan teks otomatis (OCR) dan saran Klasifikasi Permendagri.", "Upload up to 50 files with automated OCR and Permendagri suggestions.") }}
                        </p>
                    </div>
                </div>

                <div v-if="items.length > 0" class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="clearAll"
                        class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/30 hover:bg-red-100 rounded-xl transition-colors"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                        {{ t("Kosongkan Antrean", "Clear Queue") }}
                    </button>
                </div>
            </div>

            <!-- Drag & Drop Zone -->
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl border-2 border-dashed transition-all p-8 text-center cursor-pointer relative"
                :class="
                    isDragging
                        ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/20'
                        : 'border-slate-300 dark:border-slate-700 hover:border-emerald-400 dark:hover:border-emerald-500 bg-slate-50/40 dark:bg-slate-800/40'
                "
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
            >
                <input
                    type="file"
                    multiple
                    accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.doc,.txt,.csv,.rtf"
                    @change="onFileSelect"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                />
                <div class="flex flex-col items-center justify-center gap-3">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-inner">
                        <Upload class="w-7 h-7" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {{ t("Tarik & lepas banyak berkas ke sini, atau klik untuk memilih", "Drag & drop multiple files here, or click to browse") }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">
                            {{ t("Mendukung format PDF, Gambar (JPG, PNG, WebP), Word (.docx), dan Teks. Maksimal 50 berkas sekaligus (hingga 20 MB/berkas).", "Supports PDF, Images (JPG, PNG, WebP), Word (.docx), and Text. Up to 50 files (max 20 MB/file).") }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Global Batch Action Toolbar (muncul jika ada items) -->
            <div
                v-if="items.length > 0"
                class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm"
            >
                <div class="flex items-center gap-2 mb-3 pb-2 border-b border-slate-100 dark:border-slate-700">
                    <Sparkles class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        {{ t("Aksi Cepat: Terapkan Metadata Serentak", "Quick Action: Apply Metadata Simultaneously") }}
                    </h3>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <!-- Kategori Default -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1">
                            {{ t("Kategori Default", "Default Category") }}
                        </label>
                        <select
                            v-model="batchCategory"
                            @change="batchSubcategory = ''"
                            class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="">{{ t("Pilih Kategori...", "Select Category...") }}</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Subkategori Default -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1">
                            {{ t("Subkategori Default", "Default Subcategory") }}
                        </label>
                        <select
                            v-model="batchSubcategory"
                            :disabled="!activeBatchCategory?.children?.length"
                            class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500 disabled:opacity-40"
                        >
                            <option value="">{{ t("Pilih Subkategori...", "Select Subcategory...") }}</option>
                            <option v-for="sub in activeBatchCategory?.children" :key="sub.id" :value="sub.id">
                                {{ sub.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Kode Klasifikasi Default -->
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1">
                            {{ t("Kode Klasifikasi (Kemendagri)", "Kemendagri Code") }}
                        </label>
                        <input
                            v-model="batchKlasifikasi"
                            list="batch-kemendagri-options"
                            type="text"
                            placeholder="Contoh: 400.1 / 140"
                            class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500"
                        />
                        <datalist id="batch-kemendagri-options">
                            <option v-for="k in kemendagri_list" :key="k.kode" :value="k.kode">
                                {{ k.kode }} - {{ k.nama }} ({{ k.bidang }})
                            </option>
                        </datalist>
                    </div>

                    <!-- Tanggal Default & Tombol Terapkan -->
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-[11px] font-medium text-slate-500 dark:text-slate-400 mb-1">
                                {{ t("Tanggal Dokumen", "Document Date") }}
                            </label>
                            <input
                                v-model="batchDate"
                                type="date"
                                class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                        <button
                            type="button"
                            @click="applyBatchSettings"
                            class="px-3.5 py-2 bg-slate-900 dark:bg-white text-white dark:text-slate-900 rounded-lg text-xs font-semibold hover:bg-slate-800 dark:hover:bg-slate-100 transition-colors shrink-0"
                            :title="t('Terapkan nilai di atas ke semua berkas yang belum diset', 'Apply values above to all files in queue')"
                        >
                            {{ t("Terapkan ke Semua", "Apply to All") }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- List of Documents in Queue -->
            <div v-if="items.length > 0" class="space-y-4">
                <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 px-1">
                    <p>
                        {{ t("Menampilkan", "Showing") }}
                        <span class="font-bold text-slate-800 dark:text-slate-200">{{ items.length }}</span>
                        {{ t("berkas dalam antrean", "files in queue") }}
                    </p>
                    <span
                        v-if="invalidCount > 0"
                        class="text-amber-600 dark:text-amber-400 font-medium flex items-center gap-1"
                    >
                        <AlertCircle class="w-3.5 h-3.5" />
                        {{ invalidCount }} {{ t("dokumen belum lengkap (Judul / Kategori wajib diisi)", "documents incomplete (Title / Category required)") }}
                    </span>
                    <span v-else class="text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                        <CheckCircle2 class="w-3.5 h-3.5" />
                        {{ t("Semua dokumen siap disimpan", "All documents ready to save") }}
                    </span>
                </div>

                <!-- Items Cards -->
                <div class="space-y-3">
                    <div
                        v-for="(item, index) in items"
                        :key="item.id"
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 transition-all shadow-sm"
                        :class="{'!border-amber-400 dark:!border-amber-600': !item.title.trim() || !item.category_id}"
                    >
                        <div class="flex flex-col lg:flex-row items-start gap-4">
                            <!-- File Icon + Index -->
                            <div class="flex items-center gap-3 shrink-0">
                                <span class="text-xs font-mono font-bold text-slate-400 w-5 text-right">
                                    #{{ index + 1 }}
                                </span>
                                <div
                                    class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                    :class="{
                                        'bg-primary-100 dark:bg-primary-900/30 text-primary-600': getFileCategoryIcon(item) === 'image',
                                        'bg-rose-100 dark:bg-rose-900/30 text-rose-600': getFileCategoryIcon(item) === 'pdf',
                                        'bg-blue-100 dark:bg-blue-900/30 text-blue-600': getFileCategoryIcon(item) === 'word',
                                        'bg-amber-100 dark:bg-amber-900/30 text-amber-600': getFileCategoryIcon(item) === 'text',
                                    }"
                                >
                                    <Image v-if="getFileCategoryIcon(item) === 'image'" class="w-5 h-5" />
                                    <span v-else-if="getFileCategoryIcon(item) === 'word'" class="font-bold text-sm">W</span>
                                    <FileText v-else-if="getFileCategoryIcon(item) === 'text'" class="w-5 h-5" />
                                    <File v-else class="w-5 h-5" />
                                </div>
                                <div class="min-w-0 max-w-[180px] sm:max-w-xs">
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate" :title="item.file.name">
                                        {{ item.file.name }}
                                    </p>
                                    <p class="text-[11px] text-slate-400">
                                        {{ formatBytes(item.file.size) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Form Inputs Grid -->
                            <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 w-full">
                                <!-- Judul Dokumen -->
                                <div class="lg:col-span-2">
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                        {{ t("Judul Dokumen", "Document Title") }} <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="item.title"
                                        type="text"
                                        required
                                        placeholder="Judul dokumen..."
                                        class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                        :class="{'!border-red-400': !item.title.trim()}"
                                    />
                                </div>

                                <!-- Nomor Surat -->
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                        {{ t("Nomor Surat", "Document Number") }}
                                    </label>
                                    <input
                                        v-model="item.nomor_surat"
                                        type="text"
                                        placeholder="001/DS-SKR/I/2026"
                                        class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>

                                <!-- Kode Klasifikasi Kemendagri -->
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1 flex items-center justify-between">
                                        <span>{{ t("Kode Permendagri", "Permendagri Code") }}</span>
                                    </label>
                                    <input
                                        v-model="item.kode_klasifikasi"
                                        list="item-kemendagri-options"
                                        type="text"
                                        placeholder="Contoh: 400.1"
                                        class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 font-mono"
                                    />
                                    <datalist id="item-kemendagri-options">
                                        <option v-for="k in kemendagri_list" :key="k.kode" :value="k.kode">
                                            {{ k.kode }} - {{ k.nama }} ({{ k.bidang }})
                                        </option>
                                    </datalist>
                                </div>

                                <!-- Kategori -->
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                        {{ t("Kategori", "Category") }} <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="item.category_id"
                                        @change="item.subcategory_id = ''"
                                        required
                                        class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                        :class="{'!border-red-400': !item.category_id}"
                                    >
                                        <option value="">{{ t("Pilih Kategori...", "Select...") }}</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Subkategori -->
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                        {{ t("Subkategori", "Subcategory") }}
                                    </label>
                                    <select
                                        v-model="item.subcategory_id"
                                        :disabled="!categories?.find(c => c.id == item.category_id)?.children?.length"
                                        class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500 disabled:opacity-40"
                                    >
                                        <option value="">{{ t("Pilih Subkategori...", "Select...") }}</option>
                                        <option
                                            v-for="sub in categories?.find(c => c.id == item.category_id)?.children"
                                            :key="sub.id"
                                            :value="sub.id"
                                        >
                                            {{ sub.name }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Tanggal Dokumen -->
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                        {{ t("Tanggal Dokumen", "Document Date") }}
                                    </label>
                                    <input
                                        v-model="item.document_date"
                                        type="date"
                                        class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>

                                <!-- Keterangan Singkat -->
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-600 dark:text-slate-400 mb-1">
                                        {{ t("Keterangan", "Notes") }}
                                    </label>
                                    <input
                                        v-model="item.description"
                                        type="text"
                                        placeholder="Catatan..."
                                        class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                    />
                                </div>
                            </div>

                            <!-- Status Badge & Action -->
                            <div class="flex lg:flex-col items-center justify-between lg:justify-center gap-2 shrink-0 w-full lg:w-auto pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100 dark:border-slate-700">
                                <div class="text-right lg:text-center">
                                    <span
                                        v-if="item.ocr_loading"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300"
                                    >
                                        <Loader2 class="w-3 h-3 animate-spin" />
                                        OCR...
                                    </span>
                                    <span
                                        v-else-if="item.ocr_done"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300"
                                        :title="item.detected_reason ? 'Saran: ' + item.detected_reason : 'OCR Selesai'"
                                    >
                                        <CheckCircle2 class="w-3 h-3" />
                                        OCR OK
                                    </span>
                                    <button
                                        v-else-if="item.ocr_error"
                                        type="button"
                                        @click="processItemOcr(item)"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 hover:bg-amber-100"
                                        :title="item.ocr_error + ' - Klik untuk coba lagi'"
                                    >
                                        <RotateCw class="w-3 h-3" />
                                        Retry
                                    </button>
                                </div>

                                <button
                                    type="button"
                                    @click="removeItem(index)"
                                    class="p-2 text-slate-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                                    :title="t('Hapus berkas ini dari antrean', 'Remove from queue')"
                                >
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- OCR Suggestion banner if available -->
                        <div
                            v-if="item.detected_reason"
                            class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-[11px] text-amber-700 dark:text-amber-300/90"
                        >
                            <span class="flex items-center gap-1.5">
                                <Sparkles class="w-3 h-3 text-amber-500" />
                                {{ t("Terdeteksi otomatis:", "Auto-detected:") }}
                                <strong class="font-mono">{{ item.detected_reason }}</strong>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Submit Action Box -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm space-y-3">
                    <div v-if="submitError" class="p-3 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800/60 rounded-xl text-xs text-red-700 dark:text-red-300 flex items-center gap-2">
                        <AlertCircle class="w-4 h-4 shrink-0" />
                        <span>{{ submitError }}</span>
                    </div>

                    <div v-if="isSubmitting && submitProgress > 0" class="space-y-1">
                        <div class="flex justify-between text-xs text-slate-500">
                            <span>{{ t("Mengunggah berkas ke server...", "Uploading files to server...") }}</span>
                            <span class="font-bold">{{ submitProgress }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 dark:bg-slate-700 h-2 rounded-full overflow-hidden">
                            <div class="bg-emerald-600 h-full transition-all duration-200" :style="{ width: submitProgress + '%' }"></div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            <p class="font-medium text-slate-700 dark:text-slate-300">
                                {{ t("Siap mengunggah", "Ready to upload") }} {{ items.length }} {{ t("dokumen arsip.", "archive documents.") }}
                            </p>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                {{ t("Setiap dokumen akan otomatis memiliki token verifikasi resmi dan diproses ke arsip desa.", "Each document will automatically receive an official verification token.") }}
                            </p>
                        </div>

                        <div class="flex items-center gap-3 w-full sm:w-auto">
                            <Link
                                :href="route('documents.index')"
                                class="px-4 py-2.5 text-xs sm:text-sm font-medium text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 rounded-xl transition-colors text-center w-full sm:w-auto"
                            >
                                {{ t("Batal", "Cancel") }}
                            </Link>

                            <button
                                type="button"
                                @click="submitBatch"
                                :disabled="!isValid || isSubmitting"
                                class="flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 disabled:bg-slate-300 dark:disabled:bg-slate-700 text-white rounded-xl text-xs sm:text-sm font-semibold transition-colors shadow-sm w-full sm:w-auto disabled:cursor-not-allowed"
                            >
                                <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                                <Upload v-else class="w-4 h-4" />
                                <span>
                                    {{ isSubmitting ? t("Menyimpan Dokumen...", "Saving Documents...") : t(`Simpan Semua (${items.length}) Dokumen`, `Save All (${items.length}) Documents`) }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
