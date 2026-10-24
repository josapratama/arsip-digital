<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Save,
    Upload,
    Plus,
    Trash2,
    Loader2,
    Image as ImageIcon,
    ExternalLink,
    Eye,
} from "lucide-vue-next";
import { useI18n } from "@/useI18n.js";
import axios from "axios";

const props = defineProps({
    settings: Object, // flat { key: { id, key, value, type, group, label } }
});

const { t } = useI18n();

// ── Group config ──────────────────────────────────────────────────────────────
const groups = [
    { key: "hero", label: "🖼️  Hero / Banner" },
    { key: "general", label: "🏘️  Informasi Umum" },
    { key: "wilayah", label: "🗺️  Batas Wilayah" },
    { key: "visi_misi", label: "🎯  Visi & Misi" },
    { key: "sejarah", label: "📜  Sejarah Desa" },
    { key: "struktur", label: "🏛️  Struktur Organisasi" },
    { key: "system", label: "⚙️  Sistem & Registrasi" },
];

const activeGroup = ref("hero");
const saving = ref(false);
const uploadingKey = ref(null);

// ── Local editable state — initialized from props ─────────────────────────────
// We keep a simple { key: value } map for text/textarea fields
const localValues = ref({});

// Initialize localValues from props.settings
watch(
    () => props.settings,
    (newSettings) => {
        if (!newSettings) return;
        Object.entries(newSettings).forEach(([key, s]) => {
            if (s.type !== "json") {
                localValues.value[key] = s.value ?? "";
            }
        });
    },
    { immediate: true },
);

// Misi list (json)
const misiList = ref([]);
// Struktur list (json)
const strukturList = ref([]);

watch(
    () => props.settings,
    (newSettings) => {
        if (!newSettings) return;
        if (newSettings["misi"]) {
            try {
                const parsed = JSON.parse(newSettings["misi"].value);
                misiList.value = Array.isArray(parsed) ? parsed : [];
            } catch {
                misiList.value = [];
            }
        }
        if (newSettings["struktur_pemerintah"]) {
            try {
                const parsed = JSON.parse(
                    newSettings["struktur_pemerintah"].value,
                );
                strukturList.value = Array.isArray(parsed) ? parsed : [];
            } catch {
                strukturList.value = [];
            }
        }
    },
    { immediate: true },
);

// ── Settings in current group ─────────────────────────────────────────────────
const currentSettings = computed(() => {
    if (!props.settings) return [];
    return Object.values(props.settings).filter(
        (s) => s.group === activeGroup.value,
    );
});

// ── Save text/textarea/image fields ──────────────────────────────────────────
async function saveGroup() {
    saving.value = true;
    // Build updates array for current group (text/textarea only)
    const updates = currentSettings.value
        .filter((s) => s.type !== "json")
        .map((s) => ({ key: s.key, value: localValues.value[s.key] ?? "" }));

    try {
        const res = await axios.post(
            route("admin.site-settings.update"),
            { updates },
            {
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        "meta[name=csrf-token]",
                    ).content,
                },
            },
        );
        showToast("Perubahan berhasil disimpan!", "success");
    } catch (e) {
        showToast("Gagal menyimpan. Coba lagi.", "error");
    } finally {
        saving.value = false;
    }
}

// ── Save misi ─────────────────────────────────────────────────────────────────
async function saveMisi() {
    saving.value = true;
    try {
        await axios.post(
            route("admin.site-settings.json"),
            {
                key: "misi",
                value: misiList.value.filter((m) => m.trim() !== ""),
            },
            {
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        "meta[name=csrf-token]",
                    ).content,
                },
            },
        );
        showToast("Misi berhasil disimpan!", "success");
    } catch {
        showToast("Gagal menyimpan.", "error");
    } finally {
        saving.value = false;
    }
}

// ── Save visi (text) ──────────────────────────────────────────────────────────
async function saveVisi() {
    saving.value = true;
    try {
        await axios.post(
            route("admin.site-settings.update"),
            {
                updates: [
                    { key: "visi", value: localValues.value["visi"] ?? "" },
                ],
            },
            {
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        "meta[name=csrf-token]",
                    ).content,
                },
            },
        );
        showToast("Visi berhasil disimpan!", "success");
    } catch {
        showToast("Gagal menyimpan.", "error");
    } finally {
        saving.value = false;
    }
}

// ── Save struktur ─────────────────────────────────────────────────────────────
async function saveStruktur() {
    saving.value = true;
    try {
        await axios.post(
            route("admin.site-settings.json"),
            {
                key: "struktur_pemerintah",
                value: strukturList.value,
            },
            {
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        "meta[name=csrf-token]",
                    ).content,
                },
            },
        );
        showToast("Struktur berhasil disimpan!", "success");
    } catch {
        showToast("Gagal menyimpan.", "error");
    } finally {
        saving.value = false;
    }
}

// ── Upload image ──────────────────────────────────────────────────────────────
async function uploadImage(key, event) {
    const file = event.target.files[0];
    if (!file) return;
    uploadingKey.value = key;
    const form = new FormData();
    form.append("image", file);
    form.append("key", key);
    try {
        const res = await axios.post(route("admin.site-settings.image"), form, {
            headers: {
                "X-CSRF-TOKEN": document.querySelector("meta[name=csrf-token]")
                    .content,
            },
        });
        localValues.value[key] = res.data.path;
        showToast("Foto berhasil diupload!", "success");
    } catch {
        showToast("Gagal upload foto.", "error");
    } finally {
        uploadingKey.value = null;
    }
}

// ── Toast ─────────────────────────────────────────────────────────────────────
const toast = ref({ show: false, msg: "", type: "success" });
function showToast(msg, type = "success") {
    toast.value = { show: true, msg, type };
    setTimeout(() => {
        toast.value.show = false;
    }, 3000);
}

// ── Misi helpers ──────────────────────────────────────────────────────────────
function addMisi() {
    misiList.value.push("");
}
function removeMisi(i) {
    misiList.value.splice(i, 1);
}

// ── Struktur helpers ──────────────────────────────────────────────────────────
const warnaOptions = ["blue", "cyan", "green", "orange", "pink"];
function addStruktur() {
    strukturList.value.push({
        jabatan: "",
        nama: "",
        level: 3,
        warna: "green",
    });
}
function removeStruktur(i) {
    strukturList.value.splice(i, 1);
}

// Get image preview URL
function imageUrl(path) {
    if (!path) return "";
    return path.startsWith("http") ? path : `/storage/${path}`;
}
</script>

<template>
    <AppLayout>
        <Head :title="t('Pengaturan Halaman', 'Page Settings')" />

        <!-- Toast -->
        <Transition
            enter-active-class="transition duration-300"
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
        >
            <div
                v-if="toast.show"
                :class="[
                    'fixed top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-3 rounded-xl shadow-lg text-sm font-medium flex items-center gap-2',
                    toast.type === 'success'
                        ? 'bg-emerald-600 text-white'
                        : 'bg-red-600 text-white',
                ]"
            >
                {{ toast.msg }}
            </div>
        </Transition>

        <div class="max-w-5xl mx-auto">
            <!-- Header -->
            <div class="flex items-center justify-between mb-5">
                <p class="text-sm text-slate-500 dark:text-slate-400">
                    {{
                        t(
                            "Kelola konten halaman publik desa",
                            "Manage public village page content",
                        )
                    }}
                </p>
                <a
                    :href="route('landing')"
                    target="_blank"
                    class="flex items-center gap-1.5 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-medium transition-colors"
                >
                    <ExternalLink class="w-4 h-4" />
                    {{ t("Lihat Halaman", "View Page") }}
                </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                <!-- Sidebar -->
                <div class="md:col-span-1">
                    <div
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden"
                    >
                        <div
                            class="px-4 py-3 border-b border-slate-100 dark:border-slate-700"
                        >
                            <p
                                class="text-xs font-semibold text-slate-500 uppercase tracking-wide"
                            >
                                {{ t("Bagian", "Sections") }}
                            </p>
                        </div>
                        <nav class="p-2 space-y-0.5">
                            <button
                                v-for="group in groups"
                                :key="group.key"
                                @click="activeGroup = group.key"
                                :class="[
                                    'w-full text-left px-3 py-2.5 rounded-xl text-sm font-medium transition-colors',
                                    activeGroup === group.key
                                        ? 'bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400'
                                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700',
                                ]"
                            >
                                {{ group.label }}
                            </button>
                        </nav>
                    </div>
                </div>

                <!-- Content -->
                <div class="md:col-span-3">
                    <div
                        class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden"
                    >
                        <div
                            class="px-5 py-4 border-b border-slate-100 dark:border-slate-700"
                        >
                            <h2
                                class="font-semibold text-slate-900 dark:text-white"
                            >
                                {{
                                    groups.find((g) => g.key === activeGroup)
                                        ?.label
                                }}
                            </h2>
                        </div>

                        <div class="p-5 space-y-5">
                            <!-- ── VISI & MISI ── -->
                            <template v-if="activeGroup === 'visi_misi'">
                                <!-- Visi -->
                                <div>
                                    <label
                                        class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide mb-1.5"
                                        >Visi Kepala Desa</label
                                    >
                                    <textarea
                                        v-model="localValues['visi']"
                                        rows="3"
                                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                                    />
                                    <button
                                        @click="saveVisi"
                                        :disabled="saving"
                                        class="mt-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white text-sm font-medium rounded-xl transition-colors flex items-center gap-2"
                                    >
                                        <Save class="w-3.5 h-3.5" />
                                        Simpan Visi
                                    </button>
                                </div>

                                <!-- Misi -->
                                <div>
                                    <div
                                        class="flex items-center justify-between mb-2"
                                    >
                                        <label
                                            class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide"
                                            >Misi ({{
                                                misiList.length
                                            }}
                                            poin)</label
                                        >
                                        <button
                                            @click="addMisi"
                                            class="flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 rounded-lg text-xs font-medium hover:bg-emerald-100 transition-colors"
                                        >
                                            <Plus class="w-3.5 h-3.5" /> Tambah
                                            Misi
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        <div
                                            v-for="(_, i) in misiList"
                                            :key="i"
                                            class="flex items-start gap-2"
                                        >
                                            <span
                                                class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400 text-xs font-bold flex items-center justify-center shrink-0 mt-2"
                                                >{{ i + 1 }}</span
                                            >
                                            <textarea
                                                v-model="misiList[i]"
                                                rows="2"
                                                class="flex-1 px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                                            />
                                            <button
                                                @click="removeMisi(i)"
                                                class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors mt-1 shrink-0"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                    <button
                                        @click="saveMisi"
                                        :disabled="saving"
                                        class="mt-3 w-full py-2.5 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2"
                                    >
                                        <Loader2
                                            v-if="saving"
                                            class="w-4 h-4 animate-spin"
                                        />
                                        <Save v-else class="w-4 h-4" />
                                        Simpan Semua Misi
                                    </button>
                                </div>
                            </template>

                            <!-- ── STRUKTUR ORGANISASI ── -->
                            <template v-else-if="activeGroup === 'struktur'">
                                <div
                                    class="flex items-center justify-between mb-1"
                                >
                                    <label
                                        class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide"
                                        >Anggota ({{
                                            strukturList.length
                                        }})</label
                                    >
                                    <button
                                        @click="addStruktur"
                                        class="flex items-center gap-1 px-2.5 py-1.5 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 rounded-lg text-xs font-medium hover:bg-emerald-100 transition-colors"
                                    >
                                        <Plus class="w-3.5 h-3.5" /> Tambah
                                    </button>
                                </div>
                                <div
                                    class="space-y-2 max-h-96 overflow-y-auto pr-1"
                                >
                                    <div
                                        v-for="(item, i) in strukturList"
                                        :key="i"
                                        class="grid grid-cols-2 gap-2 p-3 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700"
                                    >
                                        <input
                                            v-model="item.jabatan"
                                            placeholder="Jabatan"
                                            class="px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                        />
                                        <input
                                            v-model="item.nama"
                                            placeholder="Nama"
                                            class="px-3 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                        />
                                        <select
                                            v-model.number="item.level"
                                            class="px-2 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none"
                                        >
                                            <option :value="1">
                                                Level 1 - Kepala
                                            </option>
                                            <option :value="2">
                                                Level 2 - Sekdes
                                            </option>
                                            <option :value="3">
                                                Level 3 - Kaur/Kasi
                                            </option>
                                            <option :value="4">
                                                Level 4 - Kadus
                                            </option>
                                        </select>
                                        <div class="flex gap-2">
                                            <select
                                                v-model="item.warna"
                                                class="flex-1 px-2 py-2 text-xs rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:outline-none"
                                            >
                                                <option
                                                    v-for="w in warnaOptions"
                                                    :key="w"
                                                    :value="w"
                                                >
                                                    {{ w }}
                                                </option>
                                            </select>
                                            <button
                                                @click="removeStruktur(i)"
                                                class="p-2 text-red-400 hover:text-red-600 rounded-lg transition-colors"
                                            >
                                                <Trash2 class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button
                                    @click="saveStruktur"
                                    :disabled="saving"
                                    class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2"
                                >
                                    <Loader2
                                        v-if="saving"
                                        class="w-4 h-4 animate-spin"
                                    />
                                    <Save v-else class="w-4 h-4" />
                                    Simpan Struktur
                                </button>
                            </template>

                            <!-- ── TEXT / TEXTAREA / IMAGE fields ── -->
                            <template v-else>
                                <div
                                    v-if="currentSettings.length === 0"
                                    class="py-8 text-center text-slate-400 text-sm"
                                >
                                    Tidak ada pengaturan untuk bagian ini.
                                </div>

                                <div
                                    v-for="s in currentSettings"
                                    :key="s.key"
                                    class="space-y-1.5"
                                >
                                    <label
                                        class="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wide"
                                    >
                                        {{ s.label }}
                                    </label>

                                    <!-- IMAGE -->
                                    <div
                                        v-if="s.type === 'image'"
                                        class="space-y-2"
                                    >
                                        <div
                                            v-if="localValues[s.key]"
                                            class="relative w-full h-44 rounded-xl overflow-hidden bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700"
                                        >
                                            <img
                                                :src="
                                                    imageUrl(localValues[s.key])
                                                "
                                                class="w-full h-full object-cover"
                                            />
                                            <div
                                                class="absolute inset-0 bg-black/20 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity"
                                            >
                                                <Eye
                                                    class="w-6 h-6 text-white"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="w-full h-32 rounded-xl bg-slate-100 dark:bg-slate-900 border-2 border-dashed border-slate-200 dark:border-slate-700 flex flex-col items-center justify-center gap-2"
                                        >
                                            <ImageIcon
                                                class="w-8 h-8 text-slate-300"
                                            />
                                            <span class="text-xs text-slate-400"
                                                >Belum ada foto</span
                                            >
                                        </div>
                                        <label
                                            class="flex items-center justify-center gap-2 px-4 py-2.5 border-2 border-dashed border-slate-300 dark:border-slate-600 rounded-xl cursor-pointer hover:border-primary-400 hover:bg-primary-50 dark:hover:bg-primary-900/10 transition-colors text-sm text-slate-600 dark:text-slate-400"
                                        >
                                            <Loader2
                                                v-if="uploadingKey === s.key"
                                                class="w-4 h-4 animate-spin text-primary-600"
                                            />
                                            <Upload v-else class="w-4 h-4" />
                                            {{
                                                uploadingKey === s.key
                                                    ? "Mengunggah..."
                                                    : "Pilih Foto (JPG/PNG/WebP, maks 5MB)"
                                            }}
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="hidden"
                                                @change="
                                                    uploadImage(s.key, $event)
                                                "
                                            />
                                        </label>
                                    </div>

                                    <!-- BOOLEAN / SWITCH -->
                                    <div
                                        v-else-if="s.type === 'boolean'"
                                        class="flex items-center justify-between p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700"
                                    >
                                        <div class="pr-4">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-block w-2.5 h-2.5 rounded-full"
                                                    :class="localValues[s.key] == '1' || localValues[s.key] === true ? 'bg-primary-500' : 'bg-slate-400'"
                                                ></span>
                                                <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                                    {{ localValues[s.key] == '1' || localValues[s.key] === true ? t("Pendaftaran Aktif (Dibuka)", "Registration Active (Open)") : t("Pendaftaran Nonaktif (Ditutup)", "Registration Disabled (Closed)") }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                                {{
                                                    localValues[s.key] == '1' || localValues[s.key] === true
                                                        ? t("Pengguna/staf baru dapat mengakses halaman /register dan melihat tombol pendaftaran di halaman login & beranda.", "New users can access /register and see registration buttons on login & landing pages.")
                                                        : t("Halaman /register tidak dapat diakses (404) dan seluruh tombol pendaftaran disembunyikan dari pengunjung.", "Page /register will be inaccessible (404) and all register buttons will be hidden.")
                                                }}
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            @click="localValues[s.key] = (localValues[s.key] == '1' || localValues[s.key] === true) ? '0' : '1'"
                                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                                            :class="(localValues[s.key] == '1' || localValues[s.key] === true) ? 'bg-primary-600' : 'bg-slate-300 dark:bg-slate-700'"
                                        >
                                            <span
                                                class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                                :class="(localValues[s.key] == '1' || localValues[s.key] === true) ? 'translate-x-5' : 'translate-x-0'"
                                            />
                                        </button>
                                    </div>

                                    <!-- OCR DRIVER SELECTOR -->
                                    <div
                                        v-else-if="s.key === 'ocr_driver'"
                                        class="space-y-3"
                                    >
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <!-- Option: Local (Tesseract) -->
                                            <div
                                                @click="localValues['ocr_driver'] = 'local'"
                                                class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between"
                                                :class="localValues['ocr_driver'] === 'local' || !localValues['ocr_driver']
                                                    ? 'border-primary-500 bg-primary-50/60 dark:bg-primary-950/40 shadow-sm'
                                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-slate-300'"
                                            >
                                                <div>
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-300 text-[11px] font-bold">
                                                            {{ t("Rekomendasi (Gratis)", "Recommended (Free)") }}
                                                        </span>
                                                        <span
                                                            class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                                            :class="localValues['ocr_driver'] === 'local' || !localValues['ocr_driver'] ? 'border-primary-600 bg-primary-600' : 'border-slate-300'"
                                                        >
                                                            <span v-if="localValues['ocr_driver'] === 'local' || !localValues['ocr_driver']" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                                        </span>
                                                    </div>
                                                    <h4 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                                                        🖥️ OCR Lokal (Tesseract)
                                                    </h4>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                                        {{ t("100% Gratis, berjalan offline di server/container tanpa biaya dan tanpa perlu kartu kredit/billing Google. Mendukung Bahasa Indonesia & Inggris.", "100% Free, runs offline on server without Google billing.") }}
                                                    </p>
                                                </div>
                                                <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-700/50 text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold flex items-center gap-1">
                                                    ✓ {{ t("Siap Digunakan (Tesseract ind+eng)", "Ready (Tesseract ind+eng)") }}
                                                </div>
                                            </div>

                                            <!-- Option: Google Cloud Vision -->
                                            <div
                                                @click="localValues['ocr_driver'] = 'google'"
                                                class="p-4 rounded-2xl border-2 cursor-pointer transition-all flex flex-col justify-between"
                                                :class="localValues['ocr_driver'] === 'google'
                                                    ? 'border-primary-500 bg-primary-50/60 dark:bg-primary-950/40 shadow-sm'
                                                    : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:border-slate-300'"
                                            >
                                                <div>
                                                    <div class="flex items-center justify-between mb-2">
                                                        <span class="px-2 py-0.5 rounded-md bg-sky-100 dark:bg-sky-900/50 text-sky-800 dark:text-sky-300 text-[11px] font-bold">
                                                            Cloud AI
                                                        </span>
                                                        <span
                                                            class="w-4 h-4 rounded-full border-2 flex items-center justify-center"
                                                            :class="localValues['ocr_driver'] === 'google' ? 'border-primary-600 bg-primary-600' : 'border-slate-300'"
                                                        >
                                                            <span v-if="localValues['ocr_driver'] === 'google'" class="w-1.5 h-1.5 rounded-full bg-white"></span>
                                                        </span>
                                                    </div>
                                                    <h4 class="font-bold text-sm text-slate-900 dark:text-white flex items-center gap-1.5">
                                                        ☁️ Google Cloud Vision
                                                    </h4>
                                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                                                        {{ t("Layanan pengenalan teks berbasis Cloud dari Google. Memerlukan API Key dan status Billing aktif di Google Cloud Console.", "Cloud OCR service from Google. Requires API Key and active Billing Account.") }}
                                                    </p>
                                                </div>
                                                <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-700/50 text-[11px] text-amber-600 dark:text-amber-400 font-medium">
                                                    ⚠️ {{ t("Memerlukan Akun Billing Google Aktif", "Requires Active Google Billing") }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- GOOGLE VISION API KEY INPUT -->
                                    <div
                                        v-else-if="s.key === 'google_vision_api_key'"
                                        class="space-y-1.5"
                                        :class="{ 'opacity-60': localValues['ocr_driver'] !== 'google' }"
                                    >
                                        <p v-if="localValues['ocr_driver'] !== 'google'" class="text-[11px] text-slate-400 italic mb-1">
                                            ({{ t("Pengaturan ini hanya digunakan jika Anda memilih Google Cloud Vision di atas", "This setting is only used if you select Google Cloud Vision above") }})
                                        </p>
                                        <input
                                            v-model="localValues[s.key]"
                                            type="text"
                                            placeholder="AIzaSy..."
                                            class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 font-mono"
                                        />
                                    </div>

                                    <!-- TEXTAREA -->
                                    <textarea
                                        v-else-if="s.type === 'textarea'"
                                        v-model="localValues[s.key]"
                                        rows="4"
                                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                                    />

                                    <!-- TEXT -->
                                    <input
                                        v-else-if="s.type === 'text'"
                                        v-model="localValues[s.key]"
                                        type="text"
                                        class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    />
                                </div>

                                <!-- Save button (for non-json groups) -->
                                <button
                                    v-if="
                                        currentSettings.some(
                                            (s) =>
                                                s.type !== 'image' &&
                                                s.type !== 'json',
                                        )
                                    "
                                    @click="saveGroup"
                                    :disabled="saving"
                                    class="w-full py-2.5 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2"
                                >
                                    <Loader2
                                        v-if="saving"
                                        class="w-4 h-4 animate-spin"
                                    />
                                    <Save v-else class="w-4 h-4" />
                                    {{
                                        saving
                                            ? "Menyimpan..."
                                            : "Simpan Perubahan"
                                    }}
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
