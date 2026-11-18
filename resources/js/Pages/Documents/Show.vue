<script setup>
import { ref, computed, onMounted } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    ArrowLeft,
    Edit2,
    Trash2,
    Download,
    ScanText,
    Loader2,
    CheckCircle2,
    AlertCircle,
    Image,
    File,
    Calendar,
    User,
    HardDrive,
    Printer,
    QrCode as QrIcon,
    ShieldCheck,
    Building2,
    ExternalLink,
    Search,
    Copy,
    Check,
    IdCard,
    Hash,
    Banknote,
    Phone,
    Mail,
    FileText,
} from "lucide-vue-next";
import axios from "axios";
import QRCode from "qrcode";
import { useI18n } from "@/useI18n.js";
import { extractEntities } from "@/utils/ocrParser.js";

const props = defineProps({
    document: Object,
    can: Object,
    enable_document_qr: Boolean,
    desa: Object,
});

const { t } = useI18n();

const ocrText = ref(props.document.ocr_text ?? "");
const ocrLoading = ref(false);
const ocrEdited = ref(false);
const ocrError = ref("");
const showDeleteConfirm = ref(false);
const qrDataUrl = ref("");

const ocrSearchQuery = ref("");
const ocrCopySuccess = ref(false);
const copiedEntityLabel = ref("");
const searchMatchesCount = ref(0);

const ocrStats = computed(() => {
    const text = ocrText.value || "";
    return {
        characters: text.length,
        words: text.trim() ? text.trim().split(/\s+/).length : 0,
        lines: text ? text.split(/\r\n|\r|\n/).length : 0,
    };
});

const extractedEntities = computed(() => {
    return extractEntities(ocrText.value || "");
});

function escapeHtml(str) {
    if (!str) return "";
    return str
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

const highlightedOcrHtml = computed(() => {
    const text = ocrText.value || "";
    if (!ocrSearchQuery.value || !ocrSearchQuery.value.trim()) {
        searchMatchesCount.value = 0;
        return escapeHtml(text).replace(/\n/g, "<br/>");
    }

    const q = ocrSearchQuery.value.trim();
    const escapedQ = q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(${escapedQ})`, "gi");

    let count = 0;
    const parts = text.split(regex);
    const htmlParts = parts.map((part) => {
        if (regex.test(part)) {
            count++;
            return `<mark class="bg-amber-300 dark:bg-amber-500 text-slate-900 font-semibold px-1 rounded">${escapeHtml(part)}</mark>`;
        }
        return escapeHtml(part);
    });

    searchMatchesCount.value = count;
    return htmlParts.join("").replace(/\n/g, "<br/>");
});

async function copyOcrText() {
    if (!ocrText.value) return;
    try {
        await navigator.clipboard.writeText(ocrText.value);
        ocrCopySuccess.value = true;
        setTimeout(() => { ocrCopySuccess.value = false; }, 2000);
    } catch {
        const textarea = document.createElement("textarea");
        textarea.value = ocrText.value;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
        ocrCopySuccess.value = true;
        setTimeout(() => { ocrCopySuccess.value = false; }, 2000);
    }
}

async function copyEntity(val, label) {
    if (!val) return;
    try {
        await navigator.clipboard.writeText(val);
        copiedEntityLabel.value = label || val;
        setTimeout(() => { copiedEntityLabel.value = ""; }, 2000);
    } catch (e) {
        console.error("Copy failed", e);
    }
}

function downloadOcrTxt() {
    if (!ocrText.value) return;
    const blob = new Blob([ocrText.value], { type: "text/plain;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    const docName = props.document?.title || "dokumen";
    link.href = url;
    link.download = `${docName.replace(/\s+/g, "_")}-transkrip-ocr.txt`;
    link.click();
    URL.revokeObjectURL(url);
}

onMounted(async () => {
    if (props.document?.verification_url) {
        try {
            qrDataUrl.value = await QRCode.toDataURL(props.document.verification_url, {
                width: 256,
                margin: 2,
                color: {
                    dark: "#0f172a",
                    light: "#ffffff",
                },
            });
        } catch (e) {
            console.error("QR Code generation error:", e);
        }
    }
});

function printDocument() {
    window.print();
}

async function processOcr() {
    ocrLoading.value = true;
    ocrError.value = "";
    try {
        const res = await axios.post(
            route("documents.process-ocr", props.document.id),
            {},
            {
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        "meta[name=csrf-token]",
                    ).content,
                },
            },
        );
        if (res.data.success) {
            ocrText.value = res.data.text;
            ocrEdited.value = true;
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

function saveOcr() {
    router.post(
        route("documents.ocr", props.document.id),
        { ocr_text: ocrText.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                ocrEdited.value = false;
            },
        },
    );
}

function deleteDocument() {
    router.delete(route("documents.destroy", props.document.id), {
        onSuccess: () => router.visit(route("documents.index")),
    });
}

const colorBadge = {
    blue: "bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300",
    green: "bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300",
    purple: "bg-violet-100 dark:bg-violet-900/30 text-violet-700 dark:text-violet-300",
    orange: "bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300",
};
</script>

<template>
    <AppLayout>
        <Head :title="document.title" />

        <div class="max-w-4xl mx-auto print:hidden">
            <!-- Header -->
            <div class="flex flex-wrap items-center gap-3 mb-6">
                <Link
                    :href="route('documents.index')"
                    class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                >
                    <ArrowLeft class="w-4.5 h-4.5" />
                </Link>
                <div class="flex-1 min-w-0">
                    <h1
                        class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white truncate"
                    >
                        {{ document.title }}
                    </h1>
                    <p
                        v-if="document.document_number || document.nomor_surat"
                        class="text-sm text-slate-500 dark:text-slate-400"
                    >
                        {{ document.document_number || document.nomor_surat }}
                    </p>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button
                        type="button"
                        @click="printDocument"
                        class="flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors shadow-sm"
                        :title="t('Cetak dokumen atau lembar verifikasi', 'Print document or verification sheet')"
                    >
                        <Printer class="w-4 h-4 text-slate-500" />
                        <span class="hidden sm:inline">{{
                            t("Cetak", "Print")
                        }}</span>
                    </button>
                    <a
                        :href="route('documents.file', document.id)"
                        target="_blank"
                        class="flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        <Download class="w-4 h-4" />
                        <span class="hidden sm:inline">{{
                            t("Unduh", "Download")
                        }}</span>
                    </a>
                    <Link
                        v-if="can?.update !== false"
                        :href="route('documents.edit', document.id)"
                        class="flex items-center gap-1.5 px-3.5 py-2 border border-slate-200 dark:border-slate-700 rounded-xl text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        <Edit2 class="w-4 h-4" />
                        <span class="hidden sm:inline">{{
                            t("Edit", "Edit")
                        }}</span>
                    </Link>
                    <button
                        v-if="can?.delete !== false"
                        @click="showDeleteConfirm = true"
                        class="flex items-center gap-1.5 px-3.5 py-2 border border-red-200 dark:border-red-800 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                    >
                        <Trash2 class="w-4 h-4" />
                        <span class="hidden sm:inline">{{
                            t("Hapus", "Delete")
                        }}</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">
                <!-- Left: file + OCR -->
                <div class="lg:col-span-3 space-y-5">
                    <!-- File preview -->
                    <div
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden"
                    >
                        <div
                            class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center gap-2"
                        >
                            <component
                                :is="document.is_image ? Image : File"
                                class="w-4.5 h-4.5 text-slate-500"
                            />
                            <span
                                class="font-medium text-slate-900 dark:text-white text-sm"
                            >
                                {{ document.file_original_name }}
                            </span>
                        </div>
                        <div class="p-5">
                            <img
                                v-if="document.is_image"
                                :src="route('documents.file', document.id)"
                                :alt="document.title"
                                class="w-full rounded-xl object-contain bg-slate-100 dark:bg-slate-900 max-h-80"
                            />
                            <div
                                v-else-if="document.is_pdf"
                                class="space-y-3"
                            >
                                <iframe
                                    :src="route('documents.file', document.id)"
                                    class="w-full h-96 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900"
                                ></iframe>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-400">PDF Document</span>
                                    <a
                                        :href="route('documents.file', document.id)"
                                        target="_blank"
                                        class="text-primary-600 dark:text-primary-400 hover:underline font-medium"
                                    >
                                        {{ t("Buka di Tab Baru", "Open in New Tab") }} ↗
                                    </a>
                                </div>
                            </div>
                            <!-- Plain Text preview -->
                            <div
                                v-else-if="document.is_text && document.text_preview"
                                class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl font-mono text-xs whitespace-pre-wrap max-h-96 overflow-y-auto border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200"
                            >
                                {{ document.text_preview }}
                            </div>
                            <!-- Microsoft Word preview card -->
                            <div
                                v-else-if="document.is_word"
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
                                        {{ t("File dokumen Microsoft Word. Teks digital otomatis diekstrak pada bagian bawah.", "Microsoft Word document. Digital text is extracted in the section below.") }}
                                    </p>
                                </div>
                                <a
                                    :href="route('documents.file', document.id)"
                                    class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-colors shrink-0 shadow-sm"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    {{ t("Unduh", "Download") }}
                                </a>
                            </div>
                            <!-- Fallback download card -->
                            <div
                                v-else
                                class="flex items-center justify-between p-5 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700"
                            >
                                <div class="flex items-center gap-3">
                                    <File class="w-8 h-8 text-slate-400" />
                                    <div>
                                        <p class="text-sm font-medium text-slate-900 dark:text-white">{{ document.file_original_name }}</p>
                                        <p class="text-xs text-slate-400">{{ document.file_size_formatted }}</p>
                                    </div>
                                </div>
                                <a
                                    :href="route('documents.file', document.id)"
                                    class="px-3.5 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-colors shadow-sm"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    {{ t("Unduh", "Download") }}
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- OCR / Extracted Text Section -->
                    <div
                        v-if="document.can_extract_text || document.is_image || document.is_pdf || document.is_word || document.is_text || document.ocr_text"
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-b border-slate-100 dark:border-slate-700"
                        >
                            <div class="flex items-center gap-2">
                                <ScanText
                                    class="w-4.5 h-4.5 text-primary-600 dark:text-primary-400"
                                />
                                <span
                                    class="font-medium text-slate-900 dark:text-white text-sm"
                                >
                                    {{ t("Hasil Teks Dokumen (OCR / Ekstraksi)", "Document Text Result (OCR / Extraction)") }}
                                </span>
                                <span
                                    v-if="document.ocr_processed"
                                    class="flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400"
                                >
                                    <CheckCircle2 class="w-3.5 h-3.5" />
                                    {{ t("Sudah diproses", "Processed") }}
                                </span>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    v-if="ocrText"
                                    @click="copyOcrText"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-medium transition cursor-pointer"
                                    :title="t('Salin teks ke papan klip', 'Copy text to clipboard')"
                                >
                                    <Check v-if="ocrCopySuccess" class="w-3.5 h-3.5 text-emerald-500" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ ocrCopySuccess ? t("Tersalin", "Copied") : t("Salin", "Copy") }}</span>
                                </button>

                                <button
                                    v-if="ocrText"
                                    @click="downloadOcrTxt"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-medium transition cursor-pointer"
                                    :title="t('Unduh transkrip TXT', 'Download TXT transcript')"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    <span>.TXT</span>
                                </button>

                                <button
                                    @click="processOcr"
                                    :disabled="ocrLoading"
                                    class="flex items-center gap-1.5 px-3 py-1.5 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white text-xs font-medium rounded-lg transition-colors shadow-sm cursor-pointer"
                                >
                                    <Loader2
                                        v-if="ocrLoading"
                                        class="w-3.5 h-3.5 animate-spin"
                                    />
                                    <ScanText v-else class="w-3.5 h-3.5" />
                                    {{
                                        ocrLoading
                                            ? t("Memproses...", "Processing...")
                                            : t("Ekstrak / Proses Ulang", "Reprocess Extraction")
                                    }}
                                </button>
                            </div>
                        </div>

                        <div class="p-5 space-y-4">
                            <!-- Stats bar -->
                            <div v-if="ocrText" class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pb-1">
                                <div class="flex items-center gap-3">
                                    <span>{{ ocrStats.characters }} {{ t("Karakter", "Chars") }}</span>
                                    <span>&bull;</span>
                                    <span>{{ ocrStats.words }} {{ t("Kata", "Words") }}</span>
                                    <span>&bull;</span>
                                    <span>{{ ocrStats.lines }} {{ t("Baris", "Lines") }}</span>
                                </div>
                                <span v-if="copiedEntityLabel" class="text-emerald-600 dark:text-emerald-400 font-medium">
                                    {{ t("Tersalin:", "Copied:") }} {{ copiedEntityLabel }}
                                </span>
                            </div>

                            <!-- Smart Entity Detection Chips -->
                            <div
                                v-if="ocrText && (extractedEntities.niks.length || extractedEntities.moneyAmounts.length || extractedEntities.phoneNumbers.length || extractedEntities.emails.length)"
                                class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-100 dark:border-slate-800 space-y-2"
                            >
                                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                    {{ t("Entitas Terdeteksi (Klik untuk salin)", "Detected Entities (Click to copy)") }}
                                </p>
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button
                                        v-for="(nik, idx) in extractedEntities.niks"
                                        :key="'nik-' + idx"
                                        @click="copyEntity(nik, 'NIK: ' + nik)"
                                        type="button"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-mono font-medium bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 transition cursor-pointer"
                                    >
                                        <IdCard class="w-3 h-3 text-blue-500" />
                                        <span>NIK: {{ nik }}</span>
                                    </button>

                                    <button
                                        v-for="(money, idx) in extractedEntities.moneyAmounts"
                                        :key="'money-' + idx"
                                        @click="copyEntity(money, 'Nominal: ' + money)"
                                        type="button"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition cursor-pointer"
                                    >
                                        <Banknote class="w-3 h-3 text-amber-500" />
                                        <span>{{ money }}</span>
                                    </button>

                                    <button
                                        v-for="(phone, idx) in extractedEntities.phoneNumbers"
                                        :key="'phone-' + idx"
                                        @click="copyEntity(phone, 'Telepon: ' + phone)"
                                        type="button"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800 hover:bg-teal-100 transition cursor-pointer"
                                    >
                                        <Phone class="w-3 h-3 text-teal-500" />
                                        <span>{{ phone }}</span>
                                    </button>

                                    <button
                                        v-for="(email, idx) in extractedEntities.emails"
                                        :key="'email-' + idx"
                                        @click="copyEntity(email, 'Email: ' + email)"
                                        type="button"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition cursor-pointer"
                                    >
                                        <Mail class="w-3 h-3 text-sky-500" />
                                        <span>{{ email }}</span>
                                    </button>
                                </div>
                            </div>

                            <!-- In-Text Search Input -->
                            <div v-if="ocrText" class="relative">
                                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                                <input
                                    v-model="ocrSearchQuery"
                                    type="text"
                                    :placeholder="t('Cari kata kunci dalam teks OCR...', 'Search keyword in OCR text...')"
                                    class="w-full pl-9 pr-24 py-1.5 text-xs rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-hidden transition"
                                />
                                <span
                                    v-if="ocrSearchQuery"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-medium text-slate-400"
                                >
                                    {{ searchMatchesCount }} {{ t('cocok', 'matches') }}
                                </span>
                            </div>

                            <div
                                v-if="ocrError"
                                class="mb-4 p-3.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-900 dark:text-amber-200 text-xs flex items-start gap-2.5"
                            >
                                <AlertCircle
                                    class="w-4 h-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"
                                />
                                <div class="flex-1">
                                    <p class="font-semibold text-amber-800 dark:text-amber-300">
                                        {{ t("OCR Gagal Diproses", "OCR Processing Failed") }}
                                    </p>
                                    <p class="mt-0.5 text-amber-700 dark:text-amber-300/80 leading-relaxed">
                                        {{ ocrError }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="ocrError = ''"
                                    class="text-amber-500 hover:text-amber-700 dark:text-amber-400 text-xs shrink-0 cursor-pointer"
                                >
                                    ✕
                                </button>
                            </div>

                            <!-- Search Highlight Display or Editable Textarea -->
                            <div
                                v-if="ocrSearchQuery"
                                class="w-full min-h-[200px] max-h-[360px] overflow-y-auto p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 font-mono text-xs leading-relaxed text-slate-800 dark:text-slate-200 select-text"
                                v-html="highlightedOcrHtml"
                            />

                            <textarea
                                v-else
                                v-model="ocrText"
                                @input="ocrEdited = true"
                                rows="8"
                                :placeholder="
                                    t(
                                        'Belum ada teks OCR. Klik Proses OCR untuk mengekstrak teks.',
                                        'No OCR text yet. Click Process OCR to extract text.',
                                    )
                                "
                                class="w-full text-xs px-3.5 py-3 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-y font-mono leading-relaxed"
                            ></textarea>

                            <div v-if="ocrEdited" class="flex justify-end">
                                <button
                                    @click="saveOcr"
                                    class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-xs font-semibold rounded-xl transition-colors shadow-sm cursor-pointer"
                                >
                                    {{ t("Simpan Perubahan Teks OCR", "Save OCR Text Changes") }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: details -->
                <div class="lg:col-span-2 space-y-5">
                    <!-- Info card -->
                    <div
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700"
                    >
                        <div
                            class="px-5 py-4 border-b border-slate-100 dark:border-slate-700"
                        >
                            <h2
                                class="font-semibold text-slate-900 dark:text-white"
                            >
                                {{
                                    t(
                                        "Informasi Dokumen",
                                        "Document Information",
                                    )
                                }}
                            </h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <!-- Category -->
                            <div>
                                <p
                                    class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5"
                                >
                                    {{ t("Kategori", "Category") }}
                                </p>
                                <span
                                    :class="[
                                        'inline-block px-2.5 py-1 rounded-lg text-xs font-medium',
                                        colorBadge[document.category?.color] ||
                                            colorBadge.blue,
                                    ]"
                                >
                                    {{ document.category?.name }}
                                </span>
                                <span
                                    v-if="document.subcategory"
                                    class="ml-2 text-xs text-slate-500 dark:text-slate-400"
                                >
                                    › {{ document.subcategory?.name }}
                                </span>
                            </div>

                            <!-- Kode Klasifikasi Kemendagri -->
                            <div v-if="document.classification_code || document.kode_klasifikasi">
                                <p
                                    class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5"
                                >
                                    {{ t("Kode Klasifikasi Kemendagri", "Kemendagri Code") }}
                                </p>
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-blue-100 dark:bg-blue-900/40 text-blue-800 dark:text-blue-300"
                                >
                                    <Building2 class="w-3.5 h-3.5" />
                                    {{ document.classification_code || document.kode_klasifikasi }}
                                </span>
                            </div>

                            <!-- Date -->
                            <div
                                v-if="document.document_date"
                                class="flex items-center gap-3"
                            >
                                <Calendar
                                    class="w-4 h-4 text-slate-400 shrink-0"
                                />
                                <div>
                                    <p
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {{
                                            t(
                                                "Tanggal Dokumen",
                                                "Document Date",
                                            )
                                        }}
                                    </p>
                                    <p
                                        class="text-sm font-medium text-slate-900 dark:text-white"
                                    >
                                        {{ document.document_date }}
                                    </p>
                                </div>
                            </div>

                            <!-- File size -->
                            <div class="flex items-center gap-3">
                                <HardDrive
                                    class="w-4 h-4 text-slate-400 shrink-0"
                                />
                                <div>
                                    <p
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {{ t("Ukuran File", "File Size") }}
                                    </p>
                                    <p
                                        class="text-sm font-medium text-slate-900 dark:text-white"
                                    >
                                        {{ document.file_size_formatted }}
                                    </p>
                                </div>
                            </div>

                            <!-- Uploader -->
                            <div class="flex items-center gap-3">
                                <User class="w-4 h-4 text-slate-400 shrink-0" />
                                <div>
                                    <p
                                        class="text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        {{ t("Diunggah oleh", "Uploaded by") }}
                                    </p>
                                    <p
                                        class="text-sm font-medium text-slate-900 dark:text-white"
                                    >
                                        {{ document.uploader }}
                                    </p>
                                    <p class="text-xs text-slate-400">
                                        {{ document.created_at }}
                                    </p>
                                </div>
                            </div>

                            <!-- Description -->
                            <div v-if="document.description">
                                <p
                                    class="text-xs font-medium text-slate-500 dark:text-slate-400 mb-1.5"
                                >
                                    {{ t("Keterangan", "Description") }}
                                </p>
                                <p
                                    class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed"
                                >
                                    {{ document.description }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Verification & QR Code Card -->
                    <div
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden"
                    >
                        <div
                            class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between"
                        >
                            <h2
                                class="font-semibold text-slate-900 dark:text-white text-sm flex items-center gap-2"
                            >
                                <ShieldCheck class="w-4.5 h-4.5 text-emerald-600 dark:text-emerald-400" />
                                {{ t("Verifikasi Keaslian", "Authenticity Verification") }}
                            </h2>
                            <span
                                v-if="enable_document_qr"
                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300"
                            >
                                QR Aktif
                            </span>
                            <span
                                v-else
                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300"
                            >
                                QR Nonaktif
                            </span>
                        </div>
                        <div class="p-5">
                            <!-- QR Code Active Mode -->
                            <div v-if="enable_document_qr && qrDataUrl" class="text-center space-y-3">
                                <div class="p-3 bg-white rounded-2xl border border-slate-200 shadow-sm inline-block mx-auto">
                                    <img
                                        :src="qrDataUrl"
                                        alt="QR Code Verifikasi"
                                        class="w-36 h-36 mx-auto rounded-lg"
                                    />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200">
                                        {{ t("Pindai untuk Verifikasi Keaslian", "Scan to Verify Authenticity") }}
                                    </p>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        {{ t("QR Code resmi tersemat saat dokumen dicetak.", "Official QR Code is embedded when document is printed.") }}
                                    </p>
                                </div>
                                <a
                                    :href="document.verification_url"
                                    target="_blank"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs font-medium hover:underline"
                                >
                                    <ExternalLink class="w-3.5 h-3.5" />
                                    {{ t("Buka Halaman Verifikasi Publik", "Open Public Verification Page") }}
                                </a>
                            </div>

                            <!-- QR Code Disabled by Admin Mode -->
                            <div v-else class="space-y-3">
                                <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-900 dark:text-amber-200 text-xs">
                                    <p class="font-semibold text-amber-800 dark:text-amber-300 flex items-center gap-1.5">
                                        <QrIcon class="w-4 h-4" />
                                        {{ t("QR Code Dinonaktifkan oleh Admin", "QR Code Disabled by Admin") }}
                                    </p>
                                    <p class="mt-1 text-[11px] text-amber-700 dark:text-amber-300/80 leading-relaxed">
                                        {{ t("QR Code verifikasi tidak akan dicetak atau ditampilkan pada file unduhan/cetakan sesuai pengaturan sistem. Namun kode identitas verifikasi dokumen tetap tersimpan di database.", "QR code will not be printed or stamped per system settings. The verification token remains safely stored in database.") }}
                                    </p>
                                </div>
                            </div>

                            <!-- Kode Token (Selalu tersimpan di DB) -->
                            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between text-xs">
                                <span class="text-slate-400 font-medium">Token ID:</span>
                                <span class="font-mono font-bold text-slate-700 dark:text-slate-300 select-all">
                                    {{ document.verification_code || "-" }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PRINT ONLY SHEET (Format Resmi Kedinasan Desa) -->
        <div class="hidden print:block p-8 bg-white text-slate-900 font-sans">
            <div class="text-center pb-4 border-b-2 border-black mb-6">
                <h2 class="text-base font-bold uppercase tracking-wider">Pemerintah Kabupaten Ogan Komering Ulu</h2>
                <h3 class="text-sm font-semibold uppercase">Kecamatan Peninjauan — Desa Sukarami</h3>
                <p class="text-xs text-slate-600">Sistem Informasi Pengarsipan Digital & Manajemen Dokumen Kedinasan</p>
            </div>

            <div class="mb-6 flex justify-between items-start gap-6">
                <div class="space-y-1.5 flex-1">
                    <h1 class="text-lg font-bold">{{ document.title }}</h1>
                    <p v-if="document.document_number || document.nomor_surat" class="text-sm font-semibold text-slate-700">
                        Nomor Surat: {{ document.document_number || document.nomor_surat }}
                    </p>
                    <p v-if="document.classification_code || document.kode_klasifikasi" class="text-xs font-mono font-bold text-slate-700">
                        Kode Klasifikasi Kemendagri: {{ document.classification_code || document.kode_klasifikasi }}
                    </p>
                    <p class="text-xs text-slate-600">
                        Kategori: {{ document.category?.name }} <span v-if="document.subcategory">/ {{ document.subcategory?.name }}</span>
                    </p>
                    <p class="text-xs text-slate-600">
                        Tanggal Dokumen: {{ document.document_date || document.created_at }}
                    </p>
                    <p class="text-xs text-slate-600">
                        Token Arsip Resmi: <span class="font-mono font-bold">{{ document.verification_code }}</span>
                    </p>
                </div>

                <!-- QR Code in Print Sheet: ONLY SHOWN IF enable_document_qr IS TRUE -->
                <div v-if="enable_document_qr && qrDataUrl" class="text-center p-2 border border-slate-300 rounded-lg shrink-0">
                    <img :src="qrDataUrl" alt="QR Verifikasi" class="w-28 h-28 mx-auto" />
                    <p class="text-[9px] font-bold uppercase mt-1">Pindai Verifikasi Keaslian</p>
                    <p class="text-[8px] text-slate-500 font-mono">{{ document.verification_code }}</p>
                </div>
            </div>

            <!-- Document Content / OCR -->
            <div v-if="ocrText" class="mt-4 pt-4 border-t border-slate-200">
                <h4 class="text-xs font-bold uppercase tracking-wider mb-2 text-slate-700">Transkrip Teks Digital (OCR):</h4>
                <div class="text-xs whitespace-pre-wrap font-mono leading-relaxed bg-slate-50 p-4 border border-slate-200 rounded">
                    {{ ocrText }}
                </div>
            </div>
        </div>

        <!-- Delete confirmation modal -->
        <Teleport to="body">
            <div
                v-if="showDeleteConfirm"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            >
                <div
                    class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 w-full max-w-sm p-6"
                >
                    <h3
                        class="text-base font-semibold text-slate-900 dark:text-white mb-2"
                    >
                        {{ t("Hapus Dokumen?", "Delete Document?") }}
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mb-5">
                        {{
                            t(
                                "Dokumen akan dipindahkan ke Recycle Bin. Anda bisa memulihkannya nanti.",
                                "Document will be moved to Recycle Bin. You can restore it later.",
                            )
                        }}
                    </p>
                    <div class="flex gap-3 justify-end">
                        <button
                            @click="showDeleteConfirm = false"
                            class="px-4 py-2 text-sm font-medium border border-slate-200 dark:border-slate-700 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            {{ t("Batal", "Cancel") }}
                        </button>
                        <button
                            @click="deleteDocument"
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
