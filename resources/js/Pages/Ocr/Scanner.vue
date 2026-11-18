<script setup>
import { ref, computed, onUnmounted, nextTick } from "vue";
import { Head, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    ScanText,
    Camera,
    Upload,
    RotateCw,
    RotateCcw,
    ZoomIn,
    ZoomOut,
    RefreshCw,
    Search,
    Copy,
    Check,
    Download,
    FileText,
    Image as ImageIcon,
    File,
    Loader2,
    X,
    Sparkles,
    IdCard,
    Calendar,
    Hash,
    Banknote,
    Phone,
    Mail,
    Tag,
    ArrowRight,
    SwitchCamera,
    Info,
    AlertCircle,
} from "lucide-vue-next";
import axios from "axios";
import { parseOcrText, extractEntities } from "@/utils/ocrParser.js";
import { useI18n } from "@/useI18n.js";

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    kemendagri_list: {
        type: Array,
        default: () => [],
    },
});

const { t, locale } = useI18n();
const isId = computed(() => locale.value === "id");

// State Berkas & Pemrosesan
const file = ref(null);
const filePreview = ref(null);
const isDragging = ref(false);
const isLoading = ref(false);
const ocrDone = ref(false);
const errorMessage = ref("");
const driver = ref("Tesseract OCR");

// Visual Controls (Zoom & Rotate)
const zoomLevel = ref(1);
const rotationDeg = ref(0); // 0, 90, 180, 270

// Hasil Ekstraksi
const rawOcrText = ref("");
const editableOcrText = ref("");
const stats = ref({ characters: 0, words: 0, lines: 0 });
const parsedMeta = ref({ nomorSurat: "", tanggal: "", judul: "" });
const detectedEntities = ref({ niks: [], moneyAmounts: [], phoneNumbers: [], emails: [] });
const detectedKemendagri = ref(null);

// In-Text Search State
const searchQuery = ref("");
const searchMatchesCount = ref(0);

// Camera Modal State
const showCameraModal = ref(false);
const videoStream = ref(null);
const videoRef = ref(null);
const canvasRef = ref(null);
const cameraFacingMode = ref("environment"); // "environment" (belakang) atau "user" (depan)
const cameraLoading = ref(false);
const cameraError = ref("");

// Clipboard Toast
const copiedEntity = ref("");
const copySuccess = ref(false);

const isImageFile = computed(() => {
    if (!file.value) return false;
    return file.value.type?.startsWith("image/") || /\.(jpe?g|png|webp|bmp|tiff?)$/i.test(file.value.name);
});

const isPdfFile = computed(() => {
    if (!file.value) return false;
    return file.value.type === "application/pdf" || file.value.name?.toLowerCase().endsWith(".pdf");
});

function handleFileSelect(selectedFile) {
    if (!selectedFile) return;
    file.value = selectedFile;
    ocrDone.value = false;
    errorMessage.value = "";
    rawOcrText.value = "";
    editableOcrText.value = "";
    rotationDeg.value = 0;
    zoomLevel.value = 1;
    searchQuery.value = "";

    if (selectedFile.type?.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = (e) => {
            filePreview.value = e.target.result;
        };
        reader.readAsDataURL(selectedFile);
    } else if (selectedFile.type === "application/pdf" || selectedFile.name?.toLowerCase().endsWith(".pdf")) {
        filePreview.value = URL.createObjectURL(selectedFile);
    } else {
        filePreview.value = null;
    }

    triggerOcrProcess();
}

function onFileInput(e) {
    const files = e.target.files;
    if (files && files.length > 0) {
        handleFileSelect(files[0]);
    }
}

function onDrop(e) {
    isDragging.value = false;
    const files = e.dataTransfer.files;
    if (files && files.length > 0) {
        handleFileSelect(files[0]);
    }
}

async function triggerOcrProcess() {
    if (!file.value) return;
    isLoading.value = true;
    errorMessage.value = "";

    try {
        const formData = new FormData();
        formData.append("file", file.value);
        formData.append("rotate", rotationDeg.value);

        const response = await axios.post(route("documents.ocr-preview"), formData, {
            headers: { "Content-Type": "multipart/form-data" },
        });

        const data = response.data;
        const text = data.text || "";
        rawOcrText.value = text;
        editableOcrText.value = text;
        driver.value = data.driver || "Tesseract OCR";

        // Statistics
        const charCount = text.length;
        const wordCount = text.trim() ? text.trim().split(/\s+/).length : 0;
        const lineCount = text ? text.split(/\r\n|\r|\n/).length : 0;
        stats.value = { characters: charCount, words: wordCount, lines: lineCount };

        // Parse metadata & entities
        const meta = parseOcrText(text);
        parsedMeta.value = meta;

        // Frontend entity extraction fallback if backend didn't supply
        const localEntities = extractEntities(text);
        detectedEntities.value = {
            niks: data.entities?.niks?.length ? data.entities.niks : localEntities.niks,
            moneyAmounts: data.entities?.money_amounts?.length ? data.entities.money_amounts : localEntities.moneyAmounts,
            phoneNumbers: data.entities?.phone_numbers?.length ? data.entities.phoneNumbers : localEntities.phoneNumbers,
            emails: data.entities?.emails?.length ? data.entities.emails : localEntities.emails,
        };

        // Kemendagri Detection
        if (data.kemendagri) {
            detectedKemendagri.value = data.kemendagri;
        } else if (props.kemendagri_list && props.kemendagri_list.length > 0) {
            const codeMatch = text.match(/\b(\d{3}(?:\.\d+)?)\b/);
            if (codeMatch) {
                const found = props.kemendagri_list.find(k => k.kode === codeMatch[1]);
                if (found) {
                    detectedKemendagri.value = found;
                }
            }
        }

        ocrDone.value = true;
    } catch (err) {
        console.error("OCR Error:", err);
        errorMessage.value = err.response?.data?.message || (isId.value ? "Gagal memproses dokumen dengan OCR." : "Failed to process OCR.");
    } finally {
        isLoading.value = false;
    }
}

// Rotation handlers
function rotateClockwise() {
    rotationDeg.value = (rotationDeg.value + 90) % 360;
}

function rotateCounterClockwise() {
    rotationDeg.value = (rotationDeg.value - 90 + 360) % 360;
}

function zoomIn() {
    if (zoomLevel.value < 2.5) zoomLevel.value = Math.min(2.5, +(zoomLevel.value + 0.25).toFixed(2));
}

function zoomOut() {
    if (zoomLevel.value > 0.5) zoomLevel.value = Math.max(0.5, +(zoomLevel.value - 0.25).toFixed(2));
}

function resetZoom() {
    zoomLevel.value = 1;
}

function clearFile() {
    file.value = null;
    filePreview.value = null;
    rawOcrText.value = "";
    editableOcrText.value = "";
    ocrDone.value = false;
    errorMessage.value = "";
    rotationDeg.value = 0;
    zoomLevel.value = 1;
    searchQuery.value = "";
    detectedEntities.value = { niks: [], moneyAmounts: [], phoneNumbers: [], emails: [] };
    detectedKemendagri.value = null;
    parsedMeta.value = { nomorSurat: "", tanggal: "", judul: "" };
}

// In-text Search Highlighting
const highlightedTextHtml = computed(() => {
    const text = editableOcrText.value || "";
    if (!searchQuery.value || !searchQuery.value.trim()) {
        searchMatchesCount.value = 0;
        return escapeHtml(text).replace(/\n/g, "<br/>");
    }

    const q = searchQuery.value.trim();
    const escapedQ = q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
    const regex = new RegExp(`(${escapedQ})`, "gi");

    let count = 0;
    const parts = text.split(regex);
    const htmlParts = parts.map(part => {
        if (regex.test(part)) {
            count++;
            return `<mark class="bg-amber-300 dark:bg-amber-500 text-slate-900 font-semibold px-1 rounded">${escapeHtml(part)}</mark>`;
        }
        return escapeHtml(part);
    });

    searchMatchesCount.value = count;
    return htmlParts.join("").replace(/\n/g, "<br/>");
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

// Copy & Download Actions
async function copyAllText() {
    if (!editableOcrText.value) return;
    try {
        await navigator.clipboard.writeText(editableOcrText.value);
        copySuccess.value = true;
        setTimeout(() => {
            copySuccess.value = false;
        }, 2200);
    } catch {
        const textarea = document.createElement("textarea");
        textarea.value = editableOcrText.value;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
        copySuccess.value = true;
        setTimeout(() => {
            copySuccess.value = false;
        }, 2200);
    }
}

async function copyValue(val, label) {
    if (!val) return;
    try {
        await navigator.clipboard.writeText(val);
        copiedEntity.value = label || val;
        setTimeout(() => {
            copiedEntity.value = "";
        }, 2000);
    } catch (e) {
        console.error("Copy failed", e);
    }
}

function downloadTxtFile() {
    if (!editableOcrText.value) return;
    const blob = new Blob([editableOcrText.value], { type: "text/plain;charset=utf-8" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    const baseName = file.value ? file.value.name.replace(/\.[^/.]+$/, "") : "ocr-transcript";
    link.href = url;
    link.download = `${baseName}-transkrip-ocr.txt`;
    link.click();
    URL.revokeObjectURL(url);
}

// Redirect ke Form Arsip Baru dengan Pre-fill
function createArchiveFromOcr() {
    // Simpan ke sessionStorage agar bisa ditangkap di Documents/Create
    const prefillData = {
        title: parsedMeta.value.judul || (file.value ? file.value.name.replace(/\.[^/.]+$/, "") : ""),
        nomor_surat: parsedMeta.value.nomorSurat || "",
        document_date: parsedMeta.value.tanggal || "",
        kode_klasifikasi: detectedKemendagri.value?.kode || "",
        ocr_text: editableOcrText.value || "",
    };

    try {
        sessionStorage.setItem("ocr_prefill", JSON.stringify(prefillData));
    } catch (e) {
        console.warn("sessionStorage failed", e);
    }

    router.visit(route("documents.create"));
}

// Camera / Webcam Implementation
async function openCameraModal() {
    showCameraModal.value = true;
    cameraError.value = "";
    await startCamera();
}

async function startCamera() {
    stopCamera();
    cameraLoading.value = true;
    cameraError.value = "";

    try {
        const constraints = {
            video: {
                facingMode: cameraFacingMode.value,
                width: { ideal: 1920 },
                height: { ideal: 1080 },
            },
            audio: false,
        };
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        videoStream.value = stream;
        await nextTick();
        if (videoRef.value) {
            videoRef.value.srcObject = stream;
            await videoRef.value.play();
        }
    } catch (err) {
        console.error("Camera access error:", err);
        cameraError.value = isId.value
            ? "Tidak dapat mengakses kamera. Pastikan izin kamera telah diberikan di peramban."
            : "Cannot access camera. Please allow camera permissions in your browser.";
    } finally {
        cameraLoading.value = false;
    }
}

function switchCamera() {
    cameraFacingMode.value = cameraFacingMode.value === "environment" ? "user" : "environment";
    startCamera();
}

function captureSnapshot() {
    if (!videoRef.value || !canvasRef.value) return;
    const video = videoRef.value;
    const canvas = canvasRef.value;

    canvas.width = video.videoWidth || 1280;
    canvas.height = video.videoHeight || 720;
    const ctx = canvas.getContext("2d");
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

    canvas.toBlob(
        (blob) => {
            if (!blob) return;
            const capturedFile = new File([blob], `pindai-kamera-${Date.now()}.jpg`, {
                type: "image/jpeg",
            });
            stopCamera();
            showCameraModal.value = false;
            handleFileSelect(capturedFile);
        },
        "image/jpeg",
        0.95
    );
}

function stopCamera() {
    if (videoStream.value) {
        videoStream.value.getTracks().forEach((track) => track.stop());
        videoStream.value = null;
    }
}

function closeCameraModal() {
    stopCamera();
    showCameraModal.value = false;
}

onUnmounted(() => {
    stopCamera();
});
</script>

<template>
    <Head :title="isId ? 'Pemindai & Studio OCR - Arsip Digital Desa' : 'OCR Scanner Studio - Digital Archive'" />

    <AppLayout>
        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Section -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center ring-1 ring-emerald-500/20">
                            <ScanText class="w-5 h-5" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                                {{ isId ? 'Pemindai & Studio OCR Cepat' : 'Quick OCR Scanner Studio' }}
                            </h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ isId ? 'Pindai berkas fisik lewat Kamera/Webcam atau unggah gambar/PDF untuk ekstraksi teks instan.' : 'Scan physical files via Camera or upload images/PDF for instant text recognition.' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                        <Sparkles class="w-3.5 h-3.5 text-emerald-500" />
                        {{ isId ? 'Mesin OCR:' : 'OCR Engine:' }} {{ driver }}
                    </span>
                    <button
                        @click="openCameraModal"
                        type="button"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl text-white bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 shadow-sm shadow-emerald-500/20 transition-all duration-150 cursor-pointer"
                    >
                        <Camera class="w-4 h-4" />
                        <span>{{ isId ? 'Pindai Kamera' : 'Scan Camera' }}</span>
                    </button>
                </div>
            </div>

            <!-- Toast / Copied Notification -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="copiedEntity || copySuccess"
                    class="fixed bottom-6 right-6 z-50 flex items-center gap-2 px-4 py-3 rounded-xl bg-slate-900 text-white shadow-xl border border-slate-700 text-sm"
                >
                    <Check class="w-4 h-4 text-emerald-400" />
                    <span>
                        {{ copySuccess ? (isId ? 'Semua teks berhasil disalin ke papan klip!' : 'All text copied to clipboard!') : (isId ? `Berhasil menyalin: ${copiedEntity}` : `Copied: ${copiedEntity}`) }}
                    </span>
                </div>
            </transition>

            <!-- Dropzone & Empty State (jika belum ada berkas) -->
            <div
                v-if="!file"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="onDrop"
                class="relative border-2 border-dashed rounded-3xl p-6 sm:p-12 transition-all duration-200 text-center flex flex-col items-center justify-center cursor-pointer min-h-[300px] sm:min-h-[360px]"
                :class="[
                    isDragging
                        ? 'border-emerald-500 bg-emerald-500/5 dark:bg-emerald-500/10 scale-[1.005]'
                        : 'border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-emerald-400 dark:hover:border-emerald-600'
                ]"
            >
                <input
                    type="file"
                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                    accept="image/*,application/pdf,.docx,.txt,.csv"
                    @change="onFileInput"
                />

                <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-gradient-to-tr from-emerald-500/10 to-teal-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-4 sm:mb-5 ring-1 ring-emerald-500/20 shadow-inner">
                    <Upload class="w-8 h-8 sm:w-10 sm:h-10 animate-pulse" />
                </div>

                <h3 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-white mb-2">
                    {{ isId ? 'Tarik & Letakkan Berkas Dokumen di Sini' : 'Drag & Drop Document File Here' }}
                </h3>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-md mb-6 px-2">
                    {{ isId ? 'Mendukung foto/pindaian kamera (.jpg, .png, .webp), dokumen PDF, DOCX, dan file teks. Teks akan otomatis diekstraksi.' : 'Supports camera photos (.jpg, .png), PDF, DOCX, and text files. Text will be automatically extracted.' }}
                </p>

                <div class="flex flex-wrap items-center justify-center gap-2.5 sm:gap-3 relative z-20">
                    <label class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-xs sm:text-sm shadow-md shadow-emerald-500/20 cursor-pointer transition">
                        {{ isId ? 'Pilih Berkas Komputer' : 'Browse Computer Files' }}
                        <input
                            type="file"
                            class="hidden"
                            accept="image/*,application/pdf,.docx,.txt,.csv"
                            @change="onFileInput"
                        />
                    </label>

                    <button
                        type="button"
                        @click.stop="openCameraModal"
                        class="px-4 sm:px-5 py-2 sm:py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-100 font-medium text-xs sm:text-sm transition flex items-center gap-2 cursor-pointer"
                    >
                        <Camera class="w-4 h-4 text-emerald-500" />
                        <span>{{ isId ? 'Gunakan Kamera / HP' : 'Use Camera / Mobile' }}</span>
                    </button>
                </div>
            </div>

            <!-- Workspace Studio Split Viewer (Setelah Berkas Dipilih) -->
            <div v-else class="space-y-5">
                <!-- Toolbar Atas Berkas -->
                <div class="flex flex-wrap items-center justify-between gap-3 bg-white dark:bg-slate-800 px-5 py-3 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="p-2 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                            <ImageIcon v-if="isImageFile" class="w-5 h-5" />
                            <FileText v-else-if="isPdfFile" class="w-5 h-5" />
                            <File v-else class="w-5 h-5" />
                        </div>
                        <div class="truncate">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                {{ file.name }}
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ (file.size / 1024).toFixed(1) }} KB &bull; {{ file.type || 'Document' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <!-- Rotate controls (Hanya aktif untuk Citra) -->
                        <div v-if="isImageFile" class="flex items-center bg-slate-100 dark:bg-slate-700/60 p-1 rounded-xl">
                            <button
                                @click="rotateCounterClockwise"
                                type="button"
                                title="Putar 90° Berlawanan Arah Jarum Jam"
                                class="p-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-600 hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
                            >
                                <RotateCcw class="w-4 h-4" />
                            </button>
                            <span class="text-xs font-mono font-medium px-2 text-slate-600 dark:text-slate-300">
                                {{ rotationDeg }}°
                            </span>
                            <button
                                @click="rotateClockwise"
                                type="button"
                                title="Putar 90° Searah Jarum Jam"
                                class="p-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-600 hover:text-slate-900 dark:hover:text-white transition cursor-pointer"
                            >
                                <RotateCw class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Zoom controls -->
                        <div v-if="isImageFile" class="flex items-center bg-slate-100 dark:bg-slate-700/60 p-1 rounded-xl">
                            <button
                                @click="zoomOut"
                                type="button"
                                title="Perkecil Citra"
                                class="p-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-600 transition cursor-pointer"
                            >
                                <ZoomOut class="w-4 h-4" />
                            </button>
                            <button
                                @click="resetZoom"
                                type="button"
                                class="text-xs font-mono px-2 text-slate-600 dark:text-slate-300 hover:text-emerald-500 cursor-pointer"
                            >
                                {{ Math.round(zoomLevel * 100) }}%
                            </button>
                            <button
                                @click="zoomIn"
                                type="button"
                                title="Perbesar Citra"
                                class="p-1.5 rounded-lg text-slate-600 dark:text-slate-300 hover:bg-white dark:hover:bg-slate-600 transition cursor-pointer"
                            >
                                <ZoomIn class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Re-run OCR button -->
                        <button
                            @click="triggerOcrProcess"
                            :disabled="isLoading"
                            type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 text-xs font-semibold hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition cursor-pointer disabled:opacity-50"
                        >
                            <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isLoading }" />
                            <span>{{ isId ? 'Ekstrak Ulang' : 'Re-extract' }}</span>
                        </button>

                        <!-- Clear File -->
                        <button
                            @click="clearFile"
                            type="button"
                            title="Ganti Berkas"
                            class="p-2 rounded-xl text-slate-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Error Alert jika gagal OCR -->
                <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 flex items-start gap-3 text-sm">
                    <AlertCircle class="w-5 h-5 text-rose-500 shrink-0 mt-0.5" />
                    <div>
                        <p class="font-semibold">{{ isId ? 'Gagal Mengekstrak Teks' : 'Extraction Failed' }}</p>
                        <p class="text-xs opacity-90 mt-0.5">{{ errorMessage }}</p>
                    </div>
                </div>

                <!-- Split Grid Viewer (2 Columns) -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                    <!-- Left: Document Image / PDF Viewer (5 cols) -->
                    <div class="lg:col-span-5 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm overflow-hidden flex flex-col">
                        <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400 bg-slate-50/50 dark:bg-slate-800/50">
                            <span>{{ isId ? 'Pratinjau Citra Dokumen Asli' : 'Original Document Preview' }}</span>
                            <span v-if="isImageFile">{{ rotationDeg }}° &bull; {{ Math.round(zoomLevel * 100) }}%</span>
                        </div>

                        <div class="relative bg-slate-900/5 dark:bg-slate-950 min-h-[260px] sm:min-h-[380px] lg:min-h-[480px] max-h-[640px] overflow-auto flex items-center justify-center p-4">
                            <!-- Image Viewer with dynamic zoom & rotation -->
                            <div
                                v-if="isImageFile && filePreview"
                                class="transition-transform duration-200 ease-out origin-center flex items-center justify-center"
                                :style="{
                                    transform: `scale(${zoomLevel}) rotate(${rotationDeg}deg)`,
                                }"
                            >
                                <img
                                    :src="filePreview"
                                    alt="Pratinjau Berkas"
                                    class="max-w-full max-h-[520px] object-contain rounded-lg shadow-md"
                                />
                            </div>

                            <!-- PDF Viewer -->
                            <iframe
                                v-else-if="isPdfFile && filePreview"
                                :src="filePreview"
                                class="w-full h-[320px] sm:h-[460px] lg:h-[560px] rounded-lg border-0"
                            />

                            <!-- Non-previewable document -->
                            <div v-else class="text-center py-16 text-slate-400">
                                <FileText class="w-16 h-16 mx-auto mb-3 opacity-40" />
                                <p class="text-sm font-medium">{{ file.name }}</p>
                                <p class="text-xs opacity-75 mt-1">{{ isId ? 'Pratinjau visual langsung tidak didukung untuk tipe berkas ini.' : 'Visual preview not available for this file type.' }}</p>
                            </div>

                            <!-- Loading Overlay saat OCR berjalan -->
                            <div
                                v-if="isLoading"
                                class="absolute inset-0 bg-slate-900/60 backdrop-blur-xs flex flex-col items-center justify-center text-white z-20 space-y-3"
                            >
                                <Loader2 class="w-8 h-8 animate-spin text-emerald-400" />
                                <p class="text-sm font-semibold tracking-wide">
                                    {{ isId ? 'Menganalisis Teks Dokumen...' : 'Analyzing Document Text...' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: OCR Transcript Studio (7 cols) -->
                    <div class="lg:col-span-7 bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 shadow-sm flex flex-col space-y-4 p-5">
                        <!-- Studio Header & Action Buttons -->
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-slate-100 dark:border-slate-700/60">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                    <Sparkles class="w-4 h-4 text-emerald-500" />
                                    <span>{{ isId ? 'Transkrip Teks & Hasil OCR' : 'OCR Transcript & Entities' }}</span>
                                </h3>
                                <div class="flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400 mt-1">
                                    <span>{{ stats.characters }} {{ isId ? 'Karakter' : 'Characters' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ stats.words }} {{ isId ? 'Kata' : 'Words' }}</span>
                                    <span>&bull;</span>
                                    <span>{{ stats.lines }} {{ isId ? 'Baris' : 'Lines' }}</span>
                                </div>
                            </div>

                            <!-- Action Toolbar -->
                            <div class="flex items-center gap-2">
                                <button
                                    @click="copyAllText"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer"
                                >
                                    <Check v-if="copySuccess" class="w-3.5 h-3.5 text-emerald-500" />
                                    <Copy v-else class="w-3.5 h-3.5" />
                                    <span>{{ copySuccess ? (isId ? 'Tersalin' : 'Copied') : (isId ? 'Salin Semua' : 'Copy All') }}</span>
                                </button>

                                <button
                                    @click="downloadTxtFile"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold transition cursor-pointer"
                                >
                                    <Download class="w-3.5 h-3.5" />
                                    <span>{{ isId ? 'Unduh .TXT' : 'Download .TXT' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Smart Entity Detection Badges (Chips) -->
                        <div class="space-y-2 bg-slate-50/70 dark:bg-slate-900/50 p-4 rounded-xl border border-slate-100 dark:border-slate-800">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                {{ isId ? 'Entitas Pintar Terdeteksi (Klik untuk menyalin)' : 'Detected Smart Entities (Click to copy)' }}
                            </p>

                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <!-- NIK Badges -->
                                <button
                                    v-for="(nik, idx) in detectedEntities.niks"
                                    :key="'nik-' + idx"
                                    @click="copyValue(nik, 'NIK: ' + nik)"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-mono font-medium bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800 hover:bg-blue-100 transition cursor-pointer"
                                    title="Klik untuk menyalin NIK"
                                >
                                    <IdCard class="w-3.5 h-3.5 text-blue-500" />
                                    <span>NIK: {{ nik }}</span>
                                </button>

                                <!-- Nomor Surat -->
                                <button
                                    v-if="parsedMeta.nomorSurat"
                                    @click="copyValue(parsedMeta.nomorSurat, 'Nomor Surat: ' + parsedMeta.nomorSurat)"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition cursor-pointer"
                                    title="Klik untuk menyalin Nomor Surat"
                                >
                                    <Hash class="w-3.5 h-3.5 text-emerald-500" />
                                    <span>No: {{ parsedMeta.nomorSurat }}</span>
                                </button>

                                <!-- Tanggal Dokumen -->
                                <button
                                    v-if="parsedMeta.tanggal"
                                    @click="copyValue(parsedMeta.tanggal, 'Tanggal: ' + parsedMeta.tanggal)"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800 hover:bg-purple-100 transition cursor-pointer"
                                    title="Klik untuk menyalin Tanggal"
                                >
                                    <Calendar class="w-3.5 h-3.5 text-purple-500" />
                                    <span>Tgl: {{ parsedMeta.tanggal }}</span>
                                </button>

                                <!-- Nominal Rupiah -->
                                <button
                                    v-for="(money, idx) in detectedEntities.moneyAmounts"
                                    :key="'money-' + idx"
                                    @click="copyValue(money, 'Nominal: ' + money)"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition cursor-pointer"
                                    title="Klik untuk menyalin Nominal Uang"
                                >
                                    <Banknote class="w-3.5 h-3.5 text-amber-500" />
                                    <span>{{ money }}</span>
                                </button>

                                <!-- Telepon -->
                                <button
                                    v-for="(phone, idx) in detectedEntities.phoneNumbers"
                                    :key="'phone-' + idx"
                                    @click="copyValue(phone, 'Telepon: ' + phone)"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-teal-50 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800 hover:bg-teal-100 transition cursor-pointer"
                                >
                                    <Phone class="w-3.5 h-3.5 text-teal-500" />
                                    <span>{{ phone }}</span>
                                </button>

                                <!-- Email -->
                                <button
                                    v-for="(email, idx) in detectedEntities.emails"
                                    :key="'email-' + idx"
                                    @click="copyValue(email, 'Email: ' + email)"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition cursor-pointer"
                                >
                                    <Mail class="w-3.5 h-3.5 text-sky-500" />
                                    <span>{{ email }}</span>
                                </button>

                                <!-- Kode Kemendagri -->
                                <button
                                    v-if="detectedKemendagri"
                                    @click="copyValue(detectedKemendagri.kode, 'Kode Kemendagri: ' + detectedKemendagri.kode)"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 transition cursor-pointer"
                                    :title="detectedKemendagri.perihal || detectedKemendagri.uraian"
                                >
                                    <Tag class="w-3.5 h-3.5 text-rose-500" />
                                    <span>Kemendagri: {{ detectedKemendagri.kode }} ({{ detectedKemendagri.perihal || detectedKemendagri.uraian }})</span>
                                </button>

                                <span
                                    v-if="!detectedEntities.niks?.length && !parsedMeta.nomorSurat && !parsedMeta.tanggal && !detectedEntities.moneyAmounts?.length && !detectedKemendagri"
                                    class="text-xs text-slate-400 italic"
                                >
                                    {{ isId ? 'Belum ada entitas khusus yang terdeteksi secara otomatis.' : 'No special entities detected automatically.' }}
                                </span>
                            </div>
                        </div>

                        <!-- In-Text Search Input -->
                        <div class="relative">
                            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="isId ? 'Cari kata kunci dalam transkrip...' : 'Search keyword in transcript...'"
                                class="w-full pl-9 pr-24 py-2 text-xs rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-hidden transition"
                            />
                            <span
                                v-if="searchQuery"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-[11px] font-medium text-slate-400"
                            >
                                {{ searchMatchesCount }} {{ isId ? 'ditemukan' : 'matches' }}
                            </span>
                        </div>

                        <!-- Transcript Viewer / Editor -->
                        <div class="relative">
                            <!-- Highlighting View Mode saat Search Aktif -->
                            <div
                                v-if="searchQuery"
                                class="w-full min-h-[300px] max-h-[380px] overflow-y-auto p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 font-mono text-xs leading-relaxed text-slate-800 dark:text-slate-200 select-text"
                                v-html="highlightedTextHtml"
                            />

                            <!-- Standard Textarea Mode saat tidak ada search -->
                            <textarea
                                v-else
                                v-model="editableOcrText"
                                rows="14"
                                :placeholder="isId ? 'Hasil transkrip teks OCR akan muncul di sini...' : 'OCR transcript will appear here...'"
                                class="w-full p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 font-mono text-xs leading-relaxed text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 outline-hidden resize-y transition"
                            ></textarea>
                        </div>

                        <!-- Footer Action: Simpan sebagai Arsip Baru -->
                        <div class="pt-2 flex flex-col sm:flex-row items-center justify-between gap-3">
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ isId ? 'Teks OCR dan metadata dapat langsung disimpan sebagai arsip resmi desa.' : 'OCR text and metadata can be saved directly as an official document archive.' }}
                            </p>

                            <button
                                @click="createArchiveFromOcr"
                                type="button"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold text-sm shadow-md shadow-emerald-500/20 transition cursor-pointer"
                            >
                                <span>{{ isId ? 'Jadikan Arsip Dokumen Baru' : 'Save as New Archive Document' }}</span>
                                <ArrowRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Kamera / Webcam -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 scale-95"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-95"
            >
                <div
                    v-if="showCameraModal"
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
                >
                    <div class="relative w-full max-w-2xl bg-slate-900 rounded-3xl overflow-hidden shadow-2xl border border-slate-700 text-white flex flex-col">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-800">
                            <div class="flex items-center gap-2">
                                <Camera class="w-5 h-5 text-emerald-400" />
                                <h3 class="font-semibold text-base">
                                    {{ isId ? 'Pindai Kamera Dokumen' : 'Document Camera Scanner' }}
                                </h3>
                            </div>
                            <button
                                @click="closeCameraModal"
                                class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                            >
                                <X class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Camera Viewport with Viewfinder Overlay -->
                        <div class="relative bg-black flex items-center justify-center min-h-[280px] sm:min-h-[380px] max-h-[500px] overflow-hidden">
                            <video
                                ref="videoRef"
                                autoplay
                                playsinline
                                muted
                                class="w-full h-full object-contain"
                            ></video>

                            <!-- Document Guide Viewfinder Grid -->
                            <div class="absolute inset-4 sm:inset-8 border-2 border-emerald-400/60 border-dashed rounded-2xl pointer-events-none flex flex-col justify-between p-3 sm:p-4">
                                <div class="flex justify-between">
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 border-t-4 border-l-4 border-emerald-400 rounded-tl-lg"></div>
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 border-t-4 border-r-4 border-emerald-400 rounded-tr-lg"></div>
                                </div>
                                <div class="text-center">
                                    <span class="px-2.5 sm:px-3 py-1 rounded-full bg-slate-900/80 text-emerald-300 text-[11px] sm:text-xs font-medium tracking-wide">
                                        {{ isId ? 'Arahkan dokumen ke dalam bingkai' : 'Align document inside the frame' }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 border-b-4 border-l-4 border-emerald-400 rounded-bl-lg"></div>
                                    <div class="w-5 h-5 sm:w-6 sm:h-6 border-b-4 border-r-4 border-emerald-400 rounded-br-lg"></div>
                                </div>
                            </div>

                            <!-- Camera Loading / Error -->
                            <div v-if="cameraLoading" class="absolute inset-0 bg-slate-950/80 flex flex-col items-center justify-center gap-3">
                                <Loader2 class="w-8 h-8 animate-spin text-emerald-400" />
                                <p class="text-sm font-medium">{{ isId ? 'Menghubungkan kamera...' : 'Connecting camera...' }}</p>
                            </div>

                            <div v-if="cameraError" class="absolute inset-0 bg-slate-950/90 flex flex-col items-center justify-center p-6 text-center gap-3">
                                <AlertCircle class="w-10 h-10 text-rose-500" />
                                <p class="text-sm text-rose-200 max-w-sm">{{ cameraError }}</p>
                                <button
                                    @click="startCamera"
                                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-white transition cursor-pointer"
                                >
                                    {{ isId ? 'Coba Lagi' : 'Retry' }}
                                </button>
                            </div>
                        </div>

                        <!-- Hidden canvas for capturing frame -->
                        <canvas ref="canvasRef" class="hidden"></canvas>

                        <!-- Modal Camera Actions -->
                        <div class="flex items-center justify-between px-6 py-4 bg-slate-900 border-t border-slate-800">
                            <button
                                @click="switchCamera"
                                type="button"
                                title="Beralih Kamera Depan / Belakang"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-300 transition cursor-pointer"
                            >
                                <SwitchCamera class="w-4 h-4 text-emerald-400" />
                                <span>{{ isId ? 'Ganti Kamera' : 'Switch Camera' }}</span>
                            </button>

                            <!-- Shutter Capture Button -->
                            <button
                                @click="captureSnapshot"
                                type="button"
                                class="w-14 h-14 rounded-full bg-emerald-500 hover:bg-emerald-400 active:scale-95 text-white flex items-center justify-center shadow-lg shadow-emerald-500/30 transition-transform duration-100 cursor-pointer"
                                title="Ambil Foto Dokumen"
                            >
                                <div class="w-10 h-10 rounded-full border-2 border-slate-900 bg-white"></div>
                            </button>

                            <button
                                @click="closeCameraModal"
                                type="button"
                                class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-medium text-slate-300 transition cursor-pointer"
                            >
                                {{ isId ? 'Batal' : 'Cancel' }}
                            </button>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </AppLayout>
</template>
