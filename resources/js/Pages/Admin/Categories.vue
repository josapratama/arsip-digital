<script setup>
import { ref, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    FolderArchive,
    Plus,
    Edit2,
    Trash2,
    CheckCircle2,
    X,
    FileText,
    FolderPlus,
    Save,
    ChevronRight,
    Search,
} from "lucide-vue-next";
import { useI18n } from "@/useI18n.js";

const props = defineProps({
    categories: Array,
});

const { t } = useI18n();

const search = ref("");
const showModal = ref(false);
const editingCategory = ref(null);
const activeParent = ref(null);

const form = useForm({
    name: "",
    name_en: "",
    parent_id: null,
    color: "emerald",
    icon: "folder",
    description: "",
});

const colorList = [
    { label: "Emerald (Utama)", value: "emerald" },
    { label: "Blue", value: "blue" },
    { label: "Teal", value: "teal" },
    { label: "Orange", value: "orange" },
    { label: "Purple", value: "purple" },
    { label: "Yellow", value: "yellow" },
];

const colorBadge = {
    emerald: "bg-emerald-100 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800",
    blue: "bg-blue-100 dark:bg-blue-950/50 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800",
    teal: "bg-teal-100 dark:bg-teal-950/50 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800",
    orange: "bg-orange-100 dark:bg-orange-950/50 text-orange-700 dark:text-orange-300 border-orange-200 dark:border-orange-800",
    purple: "bg-purple-100 dark:bg-purple-950/50 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800",
    yellow: "bg-yellow-100 dark:bg-yellow-950/50 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800",
};

const filteredCategories = computed(() => {
    if (!search.value.trim()) return props.categories || [];
    const q = search.value.toLowerCase();
    return (props.categories || []).filter(
        (c) =>
            c.name.toLowerCase().includes(q) ||
            (c.name_en && c.name_en.toLowerCase().includes(q)) ||
            (c.children &&
                c.children.some(
                    (sub) =>
                        sub.name.toLowerCase().includes(q) ||
                        (sub.name_en && sub.name_en.toLowerCase().includes(q))
                ))
    );
});

function openCreateModal(parentId = null) {
    editingCategory.value = null;
    activeParent.value = parentId
        ? props.categories.find((c) => c.id === parentId)
        : null;
    form.reset();
    form.parent_id = parentId;
    form.color = activeParent.value ? activeParent.value.color : "emerald";
    showModal.value = true;
}

function openEditModal(category) {
    editingCategory.value = category;
    activeParent.value = category.parent_id
        ? props.categories.find((c) => c.id === category.parent_id)
        : null;
    form.name = category.name;
    form.name_en = category.name_en || "";
    form.parent_id = category.parent_id;
    form.color = category.color || "emerald";
    form.icon = category.icon || "folder";
    form.description = category.description || "";
    showModal.value = true;
}

function submit() {
    if (editingCategory.value) {
        form.put(route("admin.categories.update", editingCategory.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route("admin.categories.store"), {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
}

function deleteCategory(cat) {
    const isSub = !!cat.parent_id;
    const confirmMsg = isSub
        ? t(
              `Yakin ingin menghapus subkategori "${cat.name}"?`,
              `Are you sure you want to delete subcategory "${cat.name}"?`
          )
        : t(
              `Yakin ingin menghapus kategori "${cat.name}" beserta subkategorinya?`,
              `Are you sure you want to delete category "${cat.name}" and all its subcategories?`
          );

    if (confirm(confirmMsg)) {
        router.delete(route("admin.categories.destroy", cat.id), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <AppLayout>
        <Head :title="t('Kelola Kategori Arsip', 'Manage Categories')" />

        <div class="space-y-5">
            <!-- Header Card -->
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 shadow-sm"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 flex items-center justify-center shrink-0"
                        >
                            <FolderArchive class="w-5 h-5" />
                        </div>
                        <div>
                            <h1
                                class="text-base font-bold text-slate-900 dark:text-white"
                            >
                                {{
                                    t(
                                        "Klasifikasi & Kategori Dokumen",
                                        "Document Categories & Classification"
                                    )
                                }}
                            </h1>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{
                                    t(
                                        "Atur kategori utama dan subkategori untuk pengelompokan arsip desa",
                                        "Organize primary categories and subcategories for village digital archiving"
                                    )
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="openCreateModal(null)"
                            class="flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm"
                        >
                            <Plus class="w-4 h-4" />
                            {{ t("Tambah Kategori Utama", "Add Primary Category") }}
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700/60">
                    <div class="relative max-w-md">
                        <Search
                            class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"
                        />
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="
                                t(
                                    'Cari kategori atau subkategori...',
                                    'Search categories or subcategories...'
                                )
                            "
                            class="w-full pl-9 pr-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                    </div>
                </div>
            </div>

            <!-- Categories Tree List -->
            <div class="space-y-3">
                <div
                    v-if="filteredCategories.length === 0"
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-12 text-center text-slate-400"
                >
                    <FolderArchive class="w-12 h-12 mx-auto text-slate-300 dark:text-slate-600 mb-2" />
                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">
                        {{ t("Tidak ada kategori yang cocok.", "No categories matched.") }}
                    </p>
                </div>

                <div
                    v-for="cat in filteredCategories"
                    :key="cat.id"
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4 shadow-sm space-y-3 transition-all hover:border-primary-200 dark:hover:border-primary-900"
                >
                    <!-- Parent Category Row -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                :class="[
                                    'px-2.5 py-1 rounded-lg text-xs font-semibold border uppercase tracking-wide',
                                    colorBadge[cat.color] || colorBadge.emerald,
                                ]"
                            >
                                {{ cat.color || "emerald" }}
                            </span>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3
                                        class="text-sm font-bold text-slate-900 dark:text-white"
                                    >
                                        {{ cat.name }}
                                    </h3>
                                    <span
                                        v-if="cat.name_en"
                                        class="text-xs text-slate-400 italic"
                                    >
                                        ({{ cat.name_en }})
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ cat.documents_count || 0 }} {{ t("dokumen terkait", "linked documents") }}
                                    <span v-if="cat.children?.length">
                                        · {{ cat.children.length }} {{ t("subkategori", "subcategories") }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Actions for Parent -->
                        <div class="flex items-center gap-1.5 self-end sm:self-auto">
                            <button
                                @click="openCreateModal(cat.id)"
                                class="flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-950/40 hover:bg-primary-100 rounded-lg transition-colors"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                <span>{{ t("Tambah Sub", "Add Sub") }}</span>
                            </button>
                            <button
                                @click="openEditModal(cat)"
                                class="p-1.5 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 rounded-lg border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors"
                                :title="t('Edit', 'Edit')"
                            >
                                <Edit2 class="w-3.5 h-3.5" />
                            </button>
                            <button
                                @click="deleteCategory(cat)"
                                class="p-1.5 text-rose-500 hover:text-rose-700 rounded-lg border border-rose-200 dark:border-rose-900/40 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors"
                                :title="t('Hapus', 'Delete')"
                            >
                                <Trash2 class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Subcategories List -->
                    <div
                        v-if="cat.children && cat.children.length > 0"
                        class="pl-4 sm:pl-6 border-l-2 border-slate-100 dark:border-slate-700 ml-2 space-y-1.5 pt-1"
                    >
                        <div
                            v-for="sub in cat.children"
                            :key="sub.id"
                            class="flex items-center justify-between py-1.5 px-3 rounded-xl bg-slate-50 dark:bg-slate-900/40 border border-slate-100 dark:border-slate-800/80 text-xs"
                        >
                            <div class="flex items-center gap-2">
                                <ChevronRight class="w-3.5 h-3.5 text-slate-400" />
                                <span class="font-medium text-slate-800 dark:text-slate-200">
                                    {{ sub.name }}
                                </span>
                                <span
                                    v-if="sub.name_en"
                                    class="text-slate-400 italic hidden sm:inline"
                                >
                                    ({{ sub.name_en }})
                                </span>
                                <span
                                    class="text-slate-400 text-[11px] bg-white dark:bg-slate-800 px-2 py-0.5 rounded-full border border-slate-200 dark:border-slate-700"
                                >
                                    {{ sub.documents_count || 0 }} {{ t("dok", "docs") }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1">
                                <button
                                    @click="openEditModal(sub)"
                                    class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                                    :title="t('Edit', 'Edit')"
                                >
                                    <Edit2 class="w-3 h-3" />
                                </button>
                                <button
                                    @click="deleteCategory(sub)"
                                    class="p-1 text-rose-400 hover:text-rose-600"
                                    :title="t('Hapus', 'Delete')"
                                >
                                    <Trash2 class="w-3 h-3" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Dialog -->
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/50 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 max-w-md w-full p-6 shadow-xl space-y-4"
            >
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">
                        {{
                            editingCategory
                                ? t("Edit Kategori", "Edit Category")
                                : activeParent
                                ? t(
                                      `Tambah Subkategori ke "${activeParent.name}"`,
                                      `Add Subcategory to "${activeParent.name}"`
                                  )
                                : t("Tambah Kategori Utama", "Add Primary Category")
                        }}
                    </h3>
                    <button
                        @click="showModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4 text-sm">
                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t("Nama Kategori (Indonesia)", "Category Name (Indonesian)") }} <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="Contoh: Surat Perjanjian"
                        />
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t("Nama Bahasa Inggris (Opsional)", "English Name (Optional)") }}
                        </label>
                        <input
                            v-model="form.name_en"
                            type="text"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="Example: Agreement Letters"
                        />
                    </div>

                    <div v-if="!form.parent_id">
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t("Warna Tema Badge", "Theme Color Badge") }}
                        </label>
                        <select
                            v-model="form.color"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                        >
                            <option v-for="c in colorList" :key="c.value" :value="c.value">
                                {{ c.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">
                            {{ t("Keterangan", "Description") }}
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="w-full px-3.5 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                            placeholder="Deskripsi singkat..."
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="px-4 py-2 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-600 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-700"
                        >
                            {{ t("Batal", "Cancel") }}
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-xl font-semibold shadow-sm transition-colors flex items-center gap-1.5"
                        >
                            <Save class="w-4 h-4" />
                            {{ t("Simpan", "Save") }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
