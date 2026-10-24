<script setup>
import { ref, computed } from "vue";
import { Link, router, useForm } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    Users,
    UserPlus,
    Search,
    Shield,
    FileText,
    Edit2,
    Trash2,
    CheckCircle2,
    XCircle,
    X,
    Loader2,
    KeyRound,
} from "lucide-vue-next";
import { useI18n } from "@/useI18n.js";

const props = defineProps({
    users: Object,
    filters: Object,
    roles: Array,
});

const { t } = useI18n();

// ── Search & Filter ──────────────────────────────────────────────────────────
const search = ref(props.filters?.q ?? "");
const selectedRole = ref(props.filters?.role ?? "");
const selectedStatus = ref(props.filters?.status ?? "");

function applyFilters() {
    router.get(
        route("admin.users.index"),
        {
            q: search.value || undefined,
            role: selectedRole.value || undefined,
            status: selectedStatus.value || undefined,
        },
        { preserveState: true, preserveScroll: true },
    );
}

function resetFilters() {
    search.value = "";
    selectedRole.value = "";
    selectedStatus.value = "";
    applyFilters();
}

// ── Add/Edit Modal ───────────────────────────────────────────────────────────
const showModal = ref(false);
const isEditing = ref(false);
const editingUserId = ref(null);

const form = useForm({
    name: "",
    email: "",
    jabatan: "",
    role: "sekdes-operator",
    password: "",
    is_active: true,
});

function openAddModal() {
    isEditing.value = false;
    editingUserId.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.role = "sekdes-operator";
    showModal.value = true;
}

function openEditModal(user) {
    isEditing.value = true;
    editingUserId.value = user.id;
    form.clearErrors();
    form.name = user.name;
    form.email = user.email;
    form.jabatan = user.jabatan;
    form.role = user.role;
    form.password = "";
    form.is_active = user.is_active;
    showModal.value = true;
}

function submitForm() {
    if (isEditing.value) {
        form.put(route("admin.users.update", editingUserId.value), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    } else {
        form.post(route("admin.users.store"), {
            onSuccess: () => {
                showModal.value = false;
                form.reset();
            },
        });
    }
}

// ── Delete Confirmation ──────────────────────────────────────────────────────
const showDeleteModal = ref(false);
const deletingUser = ref(null);

function confirmDelete(user) {
    deletingUser.value = user;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!deletingUser.value) return;
    router.delete(route("admin.users.destroy", deletingUser.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingUser.value = null;
        },
    });
}

const roleBadgeColor = {
    admin: "bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800",
    "kepala-desa": "bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800",
    "sekdes-operator": "bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800",
    "petugas-kesejahteraan": "bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800",
    "petugas-pemerintahan": "bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 border-teal-200 dark:border-teal-800",
};
</script>

<template>
    <AppLayout>
        <Head :title="t('Manajemen Pengguna', 'User Management')" />

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header bar -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                        {{ t("Manajemen Pengguna & Staf", "User & Staff Management") }}
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                        {{ t("Kelola akun staf pemerintah desa, hak akses, dan status akun aktif.", "Manage village staff accounts, access permissions, and active status.") }}
                    </p>
                </div>
                <button
                    @click="openAddModal"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm"
                >
                    <UserPlus class="w-4 h-4" />
                    {{ t("Tambah Pengguna", "Add User") }}
                </button>
            </div>

            <!-- Search & Filters -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="sm:col-span-2 relative">
                        <Search class="w-4.5 h-4.5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="search"
                            @keydown.enter="applyFilters"
                            type="text"
                            :placeholder="t('Cari nama, email, atau jabatan staf...', 'Search name, email, or job title...')"
                            class="w-full pl-10 pr-3.5 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>
                    <div>
                        <select
                            v-model="selectedRole"
                            @change="applyFilters"
                            class="w-full py-2 px-3 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="">{{ t("Semua Peran / Role", "All Roles") }}</option>
                            <option v-for="r in roles" :key="r.name" :value="r.name">
                                {{ r.label }}
                            </option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <select
                            v-model="selectedStatus"
                            @change="applyFilters"
                            class="w-full py-2 px-3 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        >
                            <option value="">{{ t("Semua Status", "All Status") }}</option>
                            <option value="active">{{ t("Aktif", "Active") }}</option>
                            <option value="inactive">{{ t("Nonaktif", "Inactive") }}</option>
                        </select>
                        <button
                            v-if="search || selectedRole || selectedStatus"
                            @click="resetFilters"
                            class="p-2 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                            :title="t('Reset filter', 'Reset filters')"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Users Table -->
            <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 dark:bg-slate-900/60 border-b border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-500 dark:text-slate-400">
                            <tr>
                                <th class="px-5 py-3.5">{{ t("Nama & Kontak", "Name & Contact") }}</th>
                                <th class="px-5 py-3.5">{{ t("Jabatan", "Position") }}</th>
                                <th class="px-5 py-3.5">{{ t("Peran Akses", "Role") }}</th>
                                <th class="px-5 py-3.5 text-center">{{ t("Arsip Diunggah", "Uploaded Docs") }}</th>
                                <th class="px-5 py-3.5 text-center">{{ t("Status Akun", "Status") }}</th>
                                <th class="px-5 py-3.5 text-right">{{ t("Aksi", "Actions") }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-if="users.data.length === 0">
                                <td colspan="6" class="px-5 py-12 text-center text-slate-400">
                                    {{ t("Tidak ada data pengguna yang sesuai.", "No matching users found.") }}
                                </td>
                            </tr>
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-700/30 transition-colors"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300 font-bold flex items-center justify-center shrink-0 text-sm border border-emerald-200 dark:border-emerald-800">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-semibold text-slate-900 dark:text-white truncate">
                                                {{ user.name }}
                                            </p>
                                            <p class="text-xs text-slate-400 truncate">
                                                {{ user.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-slate-600 dark:text-slate-300">
                                    {{ user.jabatan }}
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border',
                                            roleBadgeColor[user.role] || roleBadgeColor['sekdes-operator']
                                        ]"
                                    >
                                        <Shield class="w-3 h-3" />
                                        {{ user.role_label }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                        <FileText class="w-3 h-3" />
                                        {{ user.documents_count }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span
                                        v-if="user.is_active"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300"
                                    >
                                        <CheckCircle2 class="w-3 h-3" />
                                        {{ t("Aktif", "Active") }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400"
                                    >
                                        <XCircle class="w-3 h-3" />
                                        {{ t("Nonaktif", "Inactive") }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            @click="openEditModal(user)"
                                            class="p-1.5 text-slate-500 hover:text-emerald-600 rounded-lg hover:bg-emerald-50 dark:hover:bg-emerald-900/20 transition-colors"
                                            :title="t('Edit pengguna', 'Edit user')"
                                        >
                                            <Edit2 class="w-4 h-4" />
                                        </button>
                                        <button
                                            @click="confirmDelete(user)"
                                            class="p-1.5 text-slate-500 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors"
                                            :title="t('Hapus pengguna', 'Delete user')"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="flex items-center justify-between px-5 py-3.5 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/30">
                    <p class="text-xs text-slate-500">
                        {{ t("Menampilkan", "Showing") }} {{ users.from }}–{{ users.to }} {{ t("dari", "of") }} {{ users.total }}
                    </p>
                    <div class="flex gap-1">
                        <Link
                            v-if="users.prev_page_url"
                            :href="users.prev_page_url"
                            class="px-2.5 py-1 rounded border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-100"
                        >
                            {{ t("Sebelumnya", "Prev") }}
                        </Link>
                        <Link
                            v-if="users.next_page_url"
                            :href="users.next_page_url"
                            class="px-2.5 py-1 rounded border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-100"
                        >
                            {{ t("Berikutnya", "Next") }}
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Modal Add / Edit User ── -->
        <Teleport to="body">
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
                @click.self="showModal = false"
            >
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-lg p-6 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-3">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <UserPlus v-if="!isEditing" class="w-5 h-5 text-emerald-600" />
                            <Edit2 v-else class="w-5 h-5 text-emerald-600" />
                            {{ isEditing ? t("Edit Data Staf", "Edit Staff Account") : t("Tambah Akun Staf Baru", "Add New Staff Account") }}
                        </h3>
                        <button @click="showModal = false" class="text-slate-400 hover:text-slate-600">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                {{ t("Nama Lengkap", "Full Name") }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.name" class="text-xs text-red-500 mt-1">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                    {{ t("Email", "Email") }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                />
                                <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                    {{ t("Jabatan", "Job Title / Position") }} <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.jabatan"
                                    type="text"
                                    required
                                    placeholder="Contoh: Kasi Pemerintahan"
                                    class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                />
                                <p v-if="form.errors.jabatan" class="text-xs text-red-500 mt-1">{{ form.errors.jabatan }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">
                                {{ t("Peran Akses (Role)", "Access Role") }} <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.role"
                                required
                                class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            >
                                <option v-for="r in roles" :key="r.name" :value="r.name">
                                    {{ r.label }}
                                </option>
                            </select>
                            <p v-if="form.errors.role" class="text-xs text-red-500 mt-1">{{ form.errors.role }}</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1 flex items-center justify-between">
                                <span>{{ t("Kata Sandi (Password)", "Password") }} <span v-if="!isEditing" class="text-red-500">*</span></span>
                                <span v-if="isEditing" class="text-slate-400 font-normal">{{ t("Kosongkan jika tidak diubah", "Leave blank to keep unchanged") }}</span>
                            </label>
                            <input
                                v-model="form.password"
                                type="password"
                                :required="!isEditing"
                                placeholder="Minimal 8 karakter"
                                class="w-full px-3 py-2 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
                        </div>

                        <div class="flex items-center gap-2 pt-2">
                            <input
                                id="is_active_toggle"
                                v-model="form.is_active"
                                type="checkbox"
                                class="w-4 h-4 text-emerald-600 border-slate-300 rounded focus:ring-emerald-500"
                            />
                            <label for="is_active_toggle" class="text-sm font-medium text-slate-700 dark:text-slate-300 cursor-pointer">
                                {{ t("Akun Aktif (Dapat masuk ke dalam sistem)", "Active Account (Can log into system)") }}
                            </label>
                        </div>

                        <div class="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-slate-700">
                            <button
                                type="button"
                                @click="showModal = false"
                                class="px-4 py-2 text-sm font-medium border border-slate-200 dark:border-slate-600 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700"
                            >
                                {{ t("Batal", "Cancel") }}
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2 text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 disabled:bg-emerald-400 text-white rounded-xl transition-colors flex items-center gap-2"
                            >
                                <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                                {{ isEditing ? t("Simpan Perubahan", "Save Changes") : t("Simpan Staf", "Save Staff") }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>

        <!-- ── Delete Confirmation Modal ── -->
        <Teleport to="body">
            <div
                v-if="showDeleteModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
            >
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-sm p-6 space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">
                        {{ t("Hapus Akun Staf?", "Delete Staff Account?") }}
                    </h3>
                    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed">
                        {{ t("Apakah Anda yakin ingin menghapus akun staf", "Are you sure you want to delete staff account") }}
                        <strong>{{ deletingUser?.name }}</strong>?
                        {{ t("Tindakan ini tidak dapat dibatalkan.", "This action cannot be undone.") }}
                    </p>
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 text-sm font-medium border border-slate-200 dark:border-slate-600 rounded-xl text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700"
                        >
                            {{ t("Batal", "Cancel") }}
                        </button>
                        <button
                            @click="executeDelete"
                            class="px-4 py-2 text-sm font-semibold bg-rose-600 hover:bg-rose-700 text-white rounded-xl transition-colors"
                        >
                            {{ t("Ya, Hapus", "Yes, Delete") }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
