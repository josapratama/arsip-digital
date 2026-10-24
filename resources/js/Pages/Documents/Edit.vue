<script setup>
import { ref, computed, watch } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import { Save, ArrowLeft, Loader2 } from "lucide-vue-next";
import { useI18n } from "@/useI18n.js";

const props = defineProps({ document: Object, categories: Array });

const { t } = useI18n();

const form = useForm({
    title: props.document.title ?? "",
    nomor_surat: props.document.nomor_surat ?? "",
    category_id: props.document.category_id ?? "",
    subcategory_id: props.document.subcategory_id ?? "",
    document_date: props.document.document_date
        ? String(props.document.document_date).substring(0, 10)
        : "",
    description: props.document.description ?? "",
    ocr_text: props.document.ocr_text ?? "",
});

const selectedCategory = computed(() =>
    props.categories?.find((c) => c.id == form.category_id),
);

watch(
    () => form.category_id,
    (newVal, oldVal) => {
        if (newVal !== oldVal) form.subcategory_id = "";
    },
);

function submit() {
    form.patch(route("documents.update", props.document.id));
}
</script>

<template>
    <AppLayout>
        <Head :title="t('Edit Dokumen', 'Edit Document')" />

        <div class="max-w-3xl mx-auto">
            <div class="flex items-center gap-3 mb-6">
                <Link
                    :href="route('documents.show', document.id)"
                    class="p-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                >
                    <ArrowLeft class="w-4.5 h-4.5" />
                </Link>
                <div>
                    <h1
                        class="text-xl font-bold text-slate-900 dark:text-white"
                    >
                        {{ t("Edit Dokumen", "Edit Document") }}
                    </h1>
                    <p
                        class="text-sm text-slate-500 dark:text-slate-400 mt-0.5 truncate max-w-md"
                    >
                        {{ document.title }}
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700"
                >
                    <div
                        class="px-5 py-4 border-b border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("Informasi Dokumen", "Document Information") }}
                        </h2>
                    </div>
                    <div class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                            >
                                {{ t("Judul Dokumen", "Document Title") }}
                                <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.title"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                :class="{ 'border-red-500': form.errors.title }"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                                >{{
                                    t("Nomor Surat", "Document Number")
                                }}</label
                            >
                            <input
                                v-model="form.nomor_surat"
                                type="text"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                                >{{
                                    t("Tanggal Dokumen", "Document Date")
                                }}</label
                            >
                            <input
                                v-model="form.document_date"
                                type="date"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                                >{{ t("Kategori", "Category") }}
                                <span class="text-red-500">*</span></label
                            >
                            <select
                                v-model="form.category_id"
                                required
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            >
                                <option value="">
                                    {{
                                        t(
                                            "Pilih kategori...",
                                            "Select category...",
                                        )
                                    }}
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
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                                >{{ t("Subkategori", "Subcategory") }}</label
                            >
                            <select
                                v-model="form.subcategory_id"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                :disabled="!selectedCategory?.children?.length"
                            >
                                <option value="">
                                    {{
                                        t(
                                            "Pilih subkategori...",
                                            "Select subcategory...",
                                        )
                                    }}
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
                        <div class="sm:col-span-2">
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                                >{{ t("Keterangan", "Description") }}</label
                            >
                            <textarea
                                v-model="form.description"
                                rows="3"
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                            ></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                                >{{ t("Teks OCR", "OCR Text") }}</label
                            >
                            <textarea
                                v-model="form.ocr_text"
                                rows="6"
                                :placeholder="
                                    t(
                                        'Teks hasil OCR...',
                                        'OCR extracted text...',
                                    )
                                "
                                class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none font-mono"
                            ></textarea>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pb-6">
                    <Link
                        :href="route('documents.show', document.id)"
                        class="px-5 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-600 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        {{ t("Batal", "Cancel") }}
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="px-5 py-2.5 text-sm font-semibold bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white rounded-xl transition-colors flex items-center gap-2"
                    >
                        <Loader2
                            v-if="form.processing"
                            class="w-4 h-4 animate-spin"
                        />
                        <Save v-else class="w-4 h-4" />
                        {{
                            form.processing
                                ? t("Menyimpan...", "Saving...")
                                : t("Simpan Perubahan", "Save Changes")
                        }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
