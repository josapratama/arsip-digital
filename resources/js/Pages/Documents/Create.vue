<script setup>
import { ref, computed, watch, onMounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Upload,
    Image,
    File,
    ScanText,
    Loader2,
    X,
    CheckCircle2,
    AlertCircle,
    AlertTriangle,
    ArrowLeft,
    Sparkles,
} from "lucide-vue-next";
import axios from "axios";
import { parseOcrText } from "@/utils/ocrParser.js";
import { useI18n } from "@/useI18n.js";

const props = defineProps({ categories: Array, kemendagri_list: Array });

const { t } = useI18n();

const form = useForm({
    title: "",
    document_number: "",
    nomor_surat: "",
    classification_code: "",
    kode_klasifikasi: "",
    category_id: "",
    subcategory_id: "",
    document_date: "",
    description: "",
    file: null,
    ocr_text: "",
});

const filePreview = ref(null);
const isDragging = ref(false);
const ocrLoading = ref(false);
const ocrDone = ref(false);

const isImageFile = computed(() => {
    if (!form.file) return false;
    return form.file.type.startsWith("image/");
});

const isPdfFile = computed(() => {
    if (!form.file) return false;
    return form.file.type === "application/pdf" || form.file.name.toLowerCase().endsWith(".pdf");
});

const isWordFile = computed(() => {
    if (!form.file) return false;
    const name = form.file.name.toLowerCase();
    return name.endsWith(".docx") || name.endsWith(".doc") || form.file.type.includes("word");
});

const isTextFile = computed(() => {
    if (!form.file) return false;
    const name = form.file.name.toLowerCase();
    return name.endsWith(".txt") || name.endsWith(".csv") || name.endsWith(".rtf") || form.file.type.startsWith("text/");
});

const selectedCategory = computed(() =>
    props.categories?.find((c) => c.id == form.category_id),
);

watch(
    () => form.category_id,
    () => {
        form.subcategory_id = "";
    },
);

onMounted(() => {
    try {
        const stored = sessionStorage.getItem("ocr_prefill");
        if (stored) {
            const data = JSON.parse(stored);
            sessionStorage.removeItem("ocr_prefill");
            if (data.title) form.title = data.title;
            if (data.nomor_surat) form.nomor_surat = data.nomor_surat;
            if (data.document_date) form.document_date = data.document_date;
            if (data.kode_klasifikasi) form.kode_klasifikasi = data.kode_klasifikasi;
            if (data.ocr_text) {
                form.ocr_text = data.ocr_text;
                ocrDone.value = true;
            }
        }
    } catch (e) {
        console.warn("Could not read ocr_prefill", e);
    }
});

function handleFile(file) {
    if (!file) return;
    form.file = file;
    form.ocr_text = "";
    ocrDone.value = false;
    ocrError.value = "";

    // Reset previous document values so stale data doesn't persist
    form.title = "";
    form.document_number = "";
    form.nomor_surat = "";
    form.classification_code = "";
    form.kode_klasifikasi = "";
    form.document_date = "";
    form.description = "";

    kemendagriDetected.value = null;
    autoFilled.value = {
        title: false,
        nomor_surat: false,
        document_date: false,
        kode_klasifikasi: false,
    };

    if (file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    } else if (file.type === "application/pdf" || file.name.toLowerCase().endsWith(".pdf")) {
        filePreview.value = URL.createObjectURL(file);
    } else if (file.name.toLowerCase().endsWith(".txt") || file.name.toLowerCase().endsWith(".csv") || file.type.startsWith("text/")) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value = e.target.result;
        };
        reader.readAsText(file);
    } else {
        filePreview.value = null;
    }

    // Trigger text extraction / OCR for all supported document types
    processOcr();
}

function onFileChange(e) {
    handleFile(e.target.files[0]);
}

function onDrop(e) {
    isDragging.value = false;
    const file = e.dataTransfer.files[0];
    if (file) {
        handleFile(file);
    }
}

const ocrError = ref("");

function removeFile() {
    form.file = null;
    filePreview.value = null;
    form.ocr_text = "";
    ocrDone.value = false;
    ocrError.value = "";

    // Clear form fields
    form.title = "";
    form.nomor_surat = "";
    form.kode_klasifikasi = "";
    form.document_date = "";
    form.description = "";

    kemendagriDetected.value = null;
    autoFilled.value = {
        title: false,
        nomor_surat: false,
        document_date: false,
        kode_klasifikasi: false,
    };
}

async function processOcr() {
    if (!form.file) return;
    ocrLoading.value = true;
    ocrError.value = "";

    try {
        const data = new FormData();
        data.append("file", form.file);
        const res = await axios.post(route("documents.ocr-preview"), data, {
            headers: {
                "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]")
                    ?.content,
            },
        });
        if (res.data.success) {
            form.ocr_text = res.data.text;
            ocrDone.value = true;

            // Apply backend metadata if available
            if (res.data.metadata) {
                if (res.data.metadata.title && !form.title) {
                    form.title = res.data.metadata.title;
                    autoFilled.value.title = true;
                }
                const docNum = res.data.metadata.document_number || res.data.metadata.nomor_surat;
                if (docNum && !form.document_number && !form.nomor_surat) {
                    form.document_number = docNum;
                    form.nomor_surat = docNum;
                    autoFilled.value.nomor_surat = true;
                }
                if (res.data.metadata.document_date && !form.document_date) {
                    form.document_date = res.data.metadata.document_date;
                    autoFilled.value.document_date = true;
                }
            }

            // Kemendagri auto-suggestion
            if (res.data.kemendagri) {
                kemendagriDetected.value = res.data.kemendagri;
                if (!form.classification_code && !form.kode_klasifikasi && res.data.kemendagri.kode) {
                    form.classification_code = res.data.kemendagri.kode;
                    form.kode_klasifikasi = res.data.kemendagri.kode;
                    autoFilled.value.kode_klasifikasi = true;
                }
                // Auto-map category if not chosen yet
                if (!form.category_id && res.data.kemendagri.category_id) {
                    form.category_id = res.data.kemendagri.category_id;
                    if (res.data.kemendagri.subcategory_id) {
                        form.subcategory_id = res.data.kemendagri.subcategory_id;
                    }
                }
            }

            // Auto-detect & pre-fill fields from OCR text
            autoFillFromOcr(res.data.text, false);
        } else {
            ocrError.value = res.data.error || t("OCR gagal.", "OCR failed.");
        }
    } catch {
        ocrError.value = t(
            "Terjadi kesalahan saat memproses OCR.",
            "An error occurred during OCR.",
        );
    } finally {
        ocrLoading.value = false;
    }
}

// Auto-fill form fields from OCR text using pattern detection
const autoFilled = ref({
    title: false,
    nomor_surat: false,
    document_date: false,
    kode_klasifikasi: false,
});
const kemendagriDetected = ref(null);

function autoFillFromOcr(text, overwrite = true) {
    const detected = parseOcrText(text);
    if (detected.judul && (overwrite || !form.title)) {
        form.title = detected.judul;
        autoFilled.value.title = true;
    }
    const detectedDocNum = detected.documentNumber || detected.nomorSurat;
    if (detectedDocNum && (overwrite || (!form.document_number && !form.nomor_surat))) {
        form.document_number = detectedDocNum;
        form.nomor_surat = detectedDocNum;
        autoFilled.value.nomor_surat = true;
    }
    if (detected.tanggal && (overwrite || !form.document_date)) {
        form.document_date = detected.tanggal;
        autoFilled.value.document_date = true;
    }
}

function submit() {
    form.document_number = form.document_number || form.nomor_surat;
    form.nomor_surat = form.document_number;
    form.classification_code = form.classification_code || form.kode_klasifikasi;
    form.kode_klasifikasi = form.classification_code;
    form.post(route("documents.store"), { forceFormData: true });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('Unggah Dokumen', 'Upload Document')" />

        <form @submit.prevent="submit" class="max-w-4xl mx-auto space-y-5">
            <!-- ── STEP 1: Upload area (always visible) ────────────────────── -->
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden"
            >
                <!-- Drop zone (saat belum ada file) -->
                <div
                    v-if="!form.file"
                    class="flex flex-col items-center justify-center gap-3 p-10 text-center border-2 border-dashed rounded-2xl transition-colors cursor-pointer m-4"
                    :class="
                        isDragging
                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                            : 'border-slate-200 dark:border-slate-600 hover:border-primary-400'
                    "
                    @dragover.prevent="isDragging = true"
                    @dragleave="isDragging = false"
                    @drop.prevent="onDrop"
                >
                    <div
                        class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center"
                    >
                        <Upload
                            class="w-7 h-7 text-slate-400 dark:text-slate-500"
                        />
                    </div>
                    <div>
                        <p
                            class="text-base font-semibold text-slate-700 dark:text-slate-200"
                        >
                            {{
                                t("Seret dokumen ke sini", "Drag document here")
                            }}
                        </p>
                        <p class="text-sm text-slate-400 mt-1">
                            {{ t("atau", "or") }}
                            <label
                                class="text-primary-600 dark:text-primary-400 cursor-pointer hover:underline font-medium"
                            >
                                {{
                                    t(
                                        "pilih file dari perangkat",
                                        "browse from device",
                                    )
                                }}
                                <input
                                    type="file"
                                    class="hidden"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf,.docx,.doc,.txt,.csv,.rtf"
                                    @change="onFileChange"
                                />
                            </label>
                        </p>
                        <p class="text-xs text-slate-400 mt-2">
                            JPG · PNG · WEBP · PDF · DOCX · TXT &mdash;
                            {{ t("Maks. 20MB", "Max 20MB") }}
                        </p>
                    </div>
                    <p v-if="form.errors.file" class="text-xs text-red-500">
                        {{ form.errors.file }}
                    </p>
                </div>

                <!-- Setelah file dipilih: preview + status OCR -->
                <div v-else>
                    <!-- File bar -->
                    <div
                        class="flex items-center gap-3 px-4 py-3 border-b border-slate-100 dark:border-slate-700"
                    >
                        <div
                            class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                            :class="{
                                'bg-primary-100 dark:bg-primary-900/30 text-primary-600': isImageFile,
                                'bg-rose-100 dark:bg-rose-900/30 text-rose-600': isPdfFile,
                                'bg-blue-100 dark:bg-blue-900/30 text-blue-600': isWordFile,
                                'bg-amber-100 dark:bg-amber-900/30 text-amber-600': isTextFile,
                            }"
                        >
                            <Image
                                v-if="isImageFile"
                                class="w-4.5 h-4.5"
                            />
                            <span v-else-if="isWordFile" class="font-bold text-sm">W</span>
                            <File v-else class="w-4.5 h-4.5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                class="text-sm font-medium text-slate-900 dark:text-white truncate"
                            >
                                {{ form.file.name }}
                            </p>
                            <p class="text-xs text-slate-400">
                                {{ (form.file.size / 1024 / 1024).toFixed(2) }}
                                MB
                            </p>
                        </div>

                        <!-- OCR Status badge -->
                        <div
                            class="flex items-center gap-2 shrink-0"
                        >
                            <span
                                v-if="ocrLoading"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 text-xs font-medium"
                            >
                                <Loader2 class="w-3.5 h-3.5 animate-spin" />
                                {{
                                    t(
                                        "Membaca dokumen...",
                                        "Reading document...",
                                    )
                                }}
                            </span>
                            <span
                                v-else-if="ocrDone"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300 text-xs font-medium"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                {{ t("OCR selesai", "OCR complete") }}
                            </span>
                            <span
                                v-else-if="ocrError"
                                class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 text-xs font-medium"
                            >
                                <AlertTriangle class="w-3.5 h-3.5" />
                                {{ t("Input Manual", "Manual Input") }}
                            </span>
                            <!-- Retry OCR -->
                            <button
                                v-if="ocrDone || ocrError"
                                type="button"
                                @click="processOcr"
                                class="flex items-center gap-1 px-2.5 py-1 rounded-lg border border-slate-200 dark:border-slate-600 text-slate-500 dark:text-slate-400 text-xs hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                            >
                                <ScanText class="w-3.5 h-3.5" />
                                {{ t("Coba Lagi", "Retry") }}
                            </button>
                        </div>

                        <button
                            type="button"
                            @click="removeFile"
                            class="p-1.5 text-slate-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors shrink-0"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Dua kolom: preview kiri, form kanan -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
                        <!-- Kolom kiri: preview dokumen -->
                        <div
                            class="p-4 border-b lg:border-b-0 lg:border-r border-slate-100 dark:border-slate-700"
                        >
                            <!-- Image preview -->
                            <!-- Image preview -->
                            <img
                                v-if="isImageFile && filePreview"
                                :src="filePreview"
                                alt="Preview"
                                class="w-full rounded-xl object-contain bg-slate-100 dark:bg-slate-900 max-h-72"
                            />
                            <!-- PDF preview iframe -->
                            <iframe
                                v-else-if="isPdfFile && filePreview"
                                :src="filePreview"
                                class="w-full rounded-xl bg-slate-100 dark:bg-slate-900 h-72 border-0"
                            ></iframe>
                            <!-- Text preview -->
                            <div
                                v-else-if="isTextFile && filePreview"
                                class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl font-mono text-xs whitespace-pre-wrap max-h-72 overflow-y-auto border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200"
                            >
                                {{ filePreview }}
                            </div>
                            <!-- Word Document preview banner -->
                            <div
                                v-else-if="isWordFile"
                                class="flex items-center gap-4 p-5 bg-blue-50/70 dark:bg-blue-950/30 rounded-xl border border-blue-200 dark:border-blue-800/60"
                            >
                                <div class="w-12 h-12 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-lg shrink-0 shadow-sm">
                                    W
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-semibold text-blue-900 dark:text-blue-200">
                                        Microsoft Word Document (.docx)
                                    </h4>
                                    <p class="text-xs text-blue-700 dark:text-blue-300/80 mt-0.5">
                                        {{ t("Teks digital akan otomatis diekstrak langsung tanpa proses OCR gambar.", "Digital text will be extracted natively without image OCR processing.") }}
                                    </p>
                                </div>
                            </div>
                            <!-- Placeholder fallback -->
                            <div
                                v-else
                                class="flex flex-col items-center justify-center py-10 bg-slate-50 dark:bg-slate-900 rounded-xl"
                            >
                                <File
                                    class="w-10 h-10 text-slate-300 dark:text-slate-600 mb-2"
                                />
                                <p class="text-xs text-slate-400">
                                    {{ form.file?.name }}
                                </p>
                            </div>

                            <!-- OCR text (bisa dikoreksi) -->
                            <div
                                class="mt-4"
                            >
                                <div
                                    class="flex items-center justify-between mb-1.5"
                                >
                                    <p
                                        class="text-xs font-medium text-slate-500 dark:text-slate-400 flex items-center gap-1"
                                    >
                                        <ScanText class="w-3.5 h-3.5" />
                                        {{
                                            t(
                                                "Teks hasil OCR",
                                                "OCR extracted text",
                                            )
                                        }}
                                        <span class="text-slate-400"
                                            >({{
                                                t(
                                                    "koreksi jika perlu",
                                                    "edit if needed",
                                                )
                                            }})</span
                                        >
                                    </p>
                                    <button
                                        v-if="form.ocr_text"
                                        type="button"
                                        @click="autoFillFromOcr(form.ocr_text, true)"
                                        class="inline-flex items-center gap-1 text-[11px] font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 hover:underline"
                                        :title="t('Perbarui form dengan data dari teks OCR', 'Update form from OCR text')"
                                    >
                                        <Sparkles class="w-3 h-3" />
                                        {{ t("Terapkan ke Form", "Apply to Form") }}
                                    </button>
                                </div>
                                <div
                                    v-if="ocrLoading"
                                    class="flex items-center justify-center py-6 bg-slate-50 dark:bg-slate-900 rounded-xl"
                                >
                                    <Loader2
                                        class="w-5 h-5 animate-spin text-primary-500 mr-2"
                                    />
                                    <span class="text-sm text-slate-500">{{
                                        t(
                                            "Mengekstrak teks...",
                                            "Extracting text...",
                                        )
                                    }}</span>
                                </div>
                                <textarea
                                    v-else
                                    v-model="form.ocr_text"
                                    rows="7"
                                    :placeholder="
                                        t(
                                            'Teks dari dokumen akan muncul di sini...',
                                            'Document text will appear here...',
                                        )
                                    "
                                    class="w-full text-xs px-3 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-700 dark:text-slate-300 placeholder:text-slate-300 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none font-mono leading-relaxed"
                                >
                                </textarea>
                            </div>
                        </div>

                        <!-- Kolom kanan: form informasi -->
                        <div class="p-4">
                            <!-- Loading state -->
                            <div
                                v-if="ocrLoading"
                                class="flex flex-col items-center justify-center h-full py-12 text-center gap-3"
                            >
                                <div
                                    class="w-12 h-12 rounded-2xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center"
                                >
                                    <Loader2
                                        class="w-6 h-6 animate-spin text-primary-600 dark:text-primary-400"
                                    />
                                </div>
                                <div>
                                    <p
                                        class="text-sm font-semibold text-slate-700 dark:text-slate-200"
                                    >
                                        {{
                                            t(
                                                "Membaca dokumen...",
                                                "Reading document...",
                                            )
                                        }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1">
                                        {{
                                            t(
                                                "Sistem mengekstrak teks dan mendeteksi informasi",
                                                "System is extracting text and detecting fields",
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Form fields (muncul setelah OCR selesai, OCR gagal, atau PDF) -->
                            <div v-else class="space-y-4">
                                <!-- Banner peringatan jika OCR gagal / billing disabled -->
                                <div
                                    v-if="ocrError"
                                    class="p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-900 dark:text-amber-200 text-xs flex items-start gap-2.5"
                                >
                                    <AlertCircle
                                        class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"
                                    />
                                    <div class="flex-1">
                                        <p class="font-semibold text-amber-800 dark:text-amber-300">
                                            {{ t("OCR Otomatis Tidak Tersedia", "Automatic OCR Unavailable") }}
                                        </p>
                                        <p class="mt-0.5 text-amber-700 dark:text-amber-300/80 leading-relaxed">
                                            {{ ocrError }}
                                        </p>
                                        <p class="mt-1.5 font-medium text-slate-600 dark:text-slate-400">
                                            💡 {{ t("File Anda tetap tersimpan. Silakan isi form di bawah ini secara manual.", "Your file is safe. Please fill out the form below manually.") }}
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="ocrError = ''"
                                        class="text-amber-500 hover:text-amber-700 dark:text-amber-400 text-xs shrink-0"
                                    >
                                        ✕
                                    </button>
                                </div>

                                <!-- Auto-fill hint -->
                                <div
                                    v-if="
                                        ocrDone &&
                                        (autoFilled.title ||
                                            autoFilled.nomor_surat ||
                                            autoFilled.document_date ||
                                            autoFilled.kode_klasifikasi)
                                    "
                                    class="flex items-start gap-2 p-3 bg-primary-50 dark:bg-primary-900/20 rounded-xl border border-primary-200 dark:border-primary-800"
                                >
                                    <Sparkles
                                        class="w-4 h-4 text-primary-600 dark:text-primary-400 shrink-0 mt-0.5"
                                    />
                                    <p
                                        class="text-xs text-primary-700 dark:text-primary-300"
                                    >
                                        {{
                                            t(
                                                "Beberapa field dan kode klasifikasi terdeteksi otomatis dari dokumen. Periksa dan koreksi jika diperlukan.",
                                                "Some fields and classification code were auto-detected from the document. Review and correct if needed.",
                                            )
                                        }}
                                    </p>
                                </div>

                                <!-- Rekomendasi Kode Kemendagri -->
                                <div
                                    v-if="kemendagriDetected"
                                    class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800/60 text-xs flex items-center justify-between"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <span class="px-2 py-0.5 rounded font-mono font-bold bg-amber-200 dark:bg-amber-900/60 text-amber-900 dark:text-amber-200 text-xs">
                                            {{ kemendagriDetected.kode }}
                                        </span>
                                        <div>
                                            <p class="font-semibold text-amber-900 dark:text-amber-200">
                                                {{ kemendagriDetected.nama }}
                                            </p>
                                            <p class="text-[11px] text-amber-700 dark:text-amber-300/80">
                                                {{ t("Saran Permendagri terdeteksi dari", "Suggested by Permendagri from") }} {{ kemendagriDetected.alasan }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Judul -->
                                <div>
                                    <label
                                        class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1"
                                    >
                                        {{
                                            t("Judul Dokumen", "Document Title")
                                        }}
                                        <span class="text-red-500">*</span>
                                        <span
                                            v-if="autoFilled.title"
                                            class="ml-1.5 text-violet-500 dark:text-violet-400"
                                            >✦
                                            {{
                                                t("terdeteksi", "detected")
                                            }}</span
                                        >
                                    </label>
                                    <input
                                        v-model="form.title"
                                        type="text"
                                        required
                                        :placeholder="
                                            t(
                                                'Contoh: SK Penetapan BPD',
                                                'E.g.: Village Decree',
                                            )
                                        "
                                        :class="[
                                            'w-full px-3 py-2 text-sm rounded-xl border bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500',
                                            autoFilled.title
                                                ? 'border-violet-400 dark:border-violet-600 bg-violet-50 dark:bg-violet-900/10'
                                                : 'border-slate-200 dark:border-slate-600',
                                            form.errors.title
                                                ? '!border-red-400'
                                                : '',
                                        ]"
                                    />
                                    <p
                                        v-if="form.errors.title"
                                        class="mt-1 text-xs text-red-500"
                                    >
                                        {{ form.errors.title }}
                                    </p>
                                </div>

                                <!-- Nomor + Kode Klasifikasi + Tanggal -->
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1"
                                        >
                                            {{
                                                t("Nomor Surat", "Doc. Number")
                                            }}
                                            <span
                                                v-if="autoFilled.nomor_surat"
                                                class="ml-1 text-violet-500"
                                                >✦</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.nomor_surat"
                                            type="text"
                                            placeholder="001/DS-SKR/I/2026"
                                            :class="[
                                                'w-full px-3 py-2 text-sm rounded-xl border bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500',
                                                autoFilled.nomor_surat
                                                    ? 'border-violet-400 dark:border-violet-600 bg-violet-50 dark:bg-violet-900/10'
                                                    : 'border-slate-200 dark:border-slate-600',
                                            ]"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1"
                                        >
                                            {{
                                                t("Kode Klasifikasi", "Classification Code")
                                            }}
                                            <span
                                                v-if="autoFilled.kode_klasifikasi"
                                                class="ml-1 text-violet-500"
                                                >✦</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.kode_klasifikasi"
                                            list="kemendagri-create-options"
                                            type="text"
                                            placeholder="Contoh: 400.1 / 140"
                                            :class="[
                                                'w-full px-3 py-2 text-sm rounded-xl border bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500',
                                                autoFilled.kode_klasifikasi
                                                    ? 'border-violet-400 dark:border-violet-600 bg-violet-50 dark:bg-violet-900/10'
                                                    : 'border-slate-200 dark:border-slate-600',
                                            ]"
                                        />
                                        <datalist id="kemendagri-create-options">
                                            <option
                                                v-for="k in kemendagri_list"
                                                :key="k.kode"
                                                :value="k.kode"
                                            >
                                                {{ k.kode }} - {{ k.nama }} ({{ k.bidang }})
                                            </option>
                                        </datalist>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1"
                                        >
                                            {{ t("Tanggal", "Date") }}
                                            <span
                                                v-if="autoFilled.document_date"
                                                class="ml-1 text-violet-500"
                                                >✦</span
                                            >
                                        </label>
                                        <input
                                            v-model="form.document_date"
                                            type="date"
                                            :class="[
                                                'w-full px-3 py-2 text-sm rounded-xl border bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500',
                                                autoFilled.document_date
                                                    ? 'border-violet-400 dark:border-violet-600 bg-violet-50 dark:bg-violet-900/10'
                                                    : 'border-slate-200 dark:border-slate-600',
                                            ]"
                                        />
                                    </div>
                                </div>

                                <!-- Kategori + Subkategori -->
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1"
                                        >
                                            {{ t("Kategori", "Category") }}
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="form.category_id"
                                            required
                                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                            :class="{
                                                '!border-red-400':
                                                    form.errors.category_id,
                                            }"
                                        >
                                            <option value="">
                                                {{ t("Pilih...", "Select...") }}
                                            </option>
                                            <option
                                                v-for="cat in categories"
                                                :key="cat.id"
                                                :value="cat.id"
                                            >
                                                {{ cat.name }}
                                            </option>
                                        </select>
                                        <p
                                            v-if="form.errors.category_id"
                                            class="mt-1 text-xs text-red-500"
                                        >
                                            {{ form.errors.category_id }}
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1"
                                        >
                                            {{
                                                t("Subkategori", "Subcategory")
                                            }}
                                        </label>
                                        <select
                                            v-model="form.subcategory_id"
                                            :disabled="
                                                !selectedCategory?.children
                                                    ?.length
                                            "
                                            class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 disabled:opacity-40"
                                        >
                                            <option value="">
                                                {{ t("Pilih...", "Select...") }}
                                            </option>
                                            <option
                                                v-for="sub in selectedCategory?.children"
                                                :key="sub.id"
                                                :value="sub.id"
                                            >
                                                {{ sub.name }}
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Keterangan -->
                                <div>
                                    <label
                                        class="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1"
                                    >
                                        {{ t("Keterangan", "Description") }}
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="2"
                                        :placeholder="
                                            t(
                                                'Keterangan tambahan...',
                                                'Additional notes...',
                                            )
                                        "
                                        class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                                    >
                                    </textarea>
                                </div>

                                <!-- Submit -->
                                <button
                                    type="submit"
                                    :disabled="form.processing || ocrLoading"
                                    class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2"
                                >
                                    <Loader2
                                        v-if="form.processing"
                                        class="w-4 h-4 animate-spin"
                                    />
                                    <Upload v-else class="w-4 h-4" />
                                    {{
                                        form.processing
                                            ? t("Menyimpan...", "Saving...")
                                            : t(
                                                  "Simpan Dokumen",
                                                  "Save Document",
                                              )
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AppLayout>
</template>
