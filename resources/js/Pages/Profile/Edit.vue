<script setup>
import { ref, computed, watch } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import AppLayout from "@/Layouts/AppLayout.vue";
import {
    User,
    Lock,
    Globe,
    Sun,
    Moon,
    Save,
    Eye,
    EyeOff,
} from "lucide-vue-next";
import { i18nState } from "@/i18n.js";
import { useI18n, setLocale } from "@/useI18n.js";

const props = defineProps({ user: Object });

const { t } = useI18n();

// Initialize from localStorage (source of truth) — fallback to DB value
const profileForm = useForm({
    name: props.user.name ?? "",
    position: props.user.position ?? props.user.jabatan ?? "",
    jabatan: props.user.position ?? props.user.jabatan ?? "",
    locale: localStorage.getItem("locale") || props.user.locale || "id",
    theme: localStorage.getItem("theme") || props.user.theme || "light",
});

// Sync locale in form when toggled from topbar
watch(
    () => i18nState.locale,
    (v) => {
        profileForm.locale = v;
    },
);

// Sync theme in form when toggled from topbar
// Watch isDark on <html> using MutationObserver (most reliable)
const observer = new MutationObserver(() => {
    const isDark = document.documentElement.classList.contains("dark");
    profileForm.theme = isDark ? "dark" : "light";
});
observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["class"],
});

const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const showCurrentPw = ref(false);
const showNewPw = ref(false);
const tab = ref("profile");

function saveProfile() {
    profileForm.position = profileForm.position || profileForm.jabatan;
    profileForm.jabatan = profileForm.position;
    profileForm.patch(route("profile.update"), {
        preserveScroll: true,
        onSuccess: () => {
            // Apply theme immediately
            document.documentElement.classList.toggle(
                "dark",
                profileForm.theme === "dark",
            );
            localStorage.setItem("theme", profileForm.theme);
            // Apply locale — setLocale saves to localStorage too so it survives navigation
            setLocale(profileForm.locale);
        },
    });
}

function savePassword() {
    passwordForm.patch(route("profile.password"), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <AppLayout>
        <Head :title="t('Profil', 'Profile')" />

        <div class="max-w-2xl mx-auto">
            <div class="mb-6">
                <h1 class="text-xl font-bold text-slate-900 dark:text-white">
                    {{ t("Profil Saya", "My Profile") }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                    {{
                        t(
                            "Kelola informasi akun dan preferensi",
                            "Manage your account information and preferences",
                        )
                    }}
                </p>
            </div>

            <!-- Avatar section -->
            <div
                class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 p-5 mb-5 flex items-center gap-4"
            >
                <div
                    class="w-16 h-16 rounded-2xl bg-primary-100 dark:bg-primary-900 flex items-center justify-center shrink-0"
                >
                    <span
                        class="text-2xl font-bold text-primary-700 dark:text-primary-300"
                    >
                        {{ user.name?.charAt(0).toUpperCase() }}
                    </span>
                </div>
                <div>
                    <p class="font-semibold text-slate-900 dark:text-white">
                        {{ user.name }}
                    </p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ user.position || user.jabatan || "—" }}
                    </p>
                    <p class="text-xs text-slate-400 mt-0.5">
                        {{ user.email }}
                    </p>
                </div>
            </div>

            <!-- Tabs -->
            <div
                class="flex border-b border-slate-200 dark:border-slate-700 mb-5"
            >
                <button
                    @click="tab = 'profile'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                        tab === 'profile'
                            ? 'border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400'
                            : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                    ]"
                >
                    <User class="w-4 h-4" />
                    {{ t("Profil", "Profile") }}
                </button>
                <button
                    @click="tab = 'password'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors',
                        tab === 'password'
                            ? 'border-primary-600 text-primary-600 dark:text-primary-400 dark:border-primary-400'
                            : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white',
                    ]"
                >
                    <Lock class="w-4 h-4" />
                    {{ t("Kata Sandi", "Password") }}
                </button>
            </div>

            <!-- Profile form -->
            <div v-if="tab === 'profile'">
                <form
                    @submit.prevent="saveProfile"
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700"
                >
                    <div
                        class="px-5 py-4 border-b border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("Informasi Pribadi", "Personal Information") }}
                        </h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                                >
                                    {{ t("Nama Lengkap", "Full Name") }}
                                </label>
                                <input
                                    v-model="profileForm.name"
                                    type="text"
                                    class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                />
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                                >
                                    {{ t("Jabatan", "Position") }}
                                </label>
                                <input
                                    v-model="profileForm.position"
                                    @input="profileForm.jabatan = profileForm.position"
                                    type="text"
                                    :placeholder="
                                        t('Contoh: Kaur TU', 'E.g.: TU Staff')
                                    "
                                    class="w-full px-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                />
                            </div>
                        </div>

                        <!-- Language -->
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                <Globe class="w-4 h-4 inline mr-1.5" />
                                {{ t("Bahasa / Language", "Language") }}
                            </label>
                            <div class="flex gap-3">
                                <label
                                    v-for="opt in [
                                        {
                                            value: 'id',
                                            label: '🇮🇩 Bahasa Indonesia',
                                        },
                                        { value: 'en', label: '🇬🇧 English' },
                                    ]"
                                    :key="opt.value"
                                    class="flex items-center gap-2 flex-1 p-3 rounded-xl border-2 cursor-pointer transition-colors"
                                    :class="
                                        profileForm.locale === opt.value
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                            : 'border-slate-200 dark:border-slate-600 hover:border-slate-300'
                                    "
                                >
                                    <input
                                        type="radio"
                                        :value="opt.value"
                                        v-model="profileForm.locale"
                                        class="sr-only"
                                    />
                                    <span
                                        class="text-sm font-medium text-slate-700 dark:text-slate-300"
                                        >{{ opt.label }}</span
                                    >
                                </label>
                            </div>
                        </div>

                        <!-- Theme -->
                        <div>
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2"
                            >
                                {{ t("Tema", "Theme") }}
                            </label>
                            <div class="flex gap-3">
                                <label
                                    v-for="opt in [
                                        {
                                            value: 'light',
                                            label: t('Terang', 'Light'),
                                            icon: Sun,
                                        },
                                        {
                                            value: 'dark',
                                            label: t('Gelap', 'Dark'),
                                            icon: Moon,
                                        },
                                    ]"
                                    :key="opt.value"
                                    class="flex items-center gap-2 flex-1 p-3 rounded-xl border-2 cursor-pointer transition-colors"
                                    :class="
                                        profileForm.theme === opt.value
                                            ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20'
                                            : 'border-slate-200 dark:border-slate-600 hover:border-slate-300'
                                    "
                                >
                                    <input
                                        type="radio"
                                        :value="opt.value"
                                        v-model="profileForm.theme"
                                        class="sr-only"
                                    />
                                    <component
                                        :is="opt.icon"
                                        class="w-4 h-4 text-slate-600 dark:text-slate-400"
                                    />
                                    <span
                                        class="text-sm font-medium text-slate-700 dark:text-slate-300"
                                        >{{ opt.label }}</span
                                    >
                                </label>
                            </div>
                        </div>
                    </div>
                    <div
                        class="px-5 py-4 border-t border-slate-100 dark:border-slate-700 flex justify-end"
                    >
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white text-sm font-semibold rounded-xl transition-colors"
                        >
                            <Save class="w-4 h-4" />
                            {{
                                profileForm.processing
                                    ? t("Menyimpan...", "Saving...")
                                    : t("Simpan Perubahan", "Save Changes")
                            }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password form -->
            <div v-if="tab === 'password'">
                <form
                    @submit.prevent="savePassword"
                    class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700"
                >
                    <div
                        class="px-5 py-4 border-b border-slate-100 dark:border-slate-700"
                    >
                        <h2
                            class="font-semibold text-slate-900 dark:text-white"
                        >
                            {{ t("Ubah Kata Sandi", "Change Password") }}
                        </h2>
                    </div>
                    <div class="p-5 space-y-4">
                        <div
                            v-for="field in [
                                {
                                    key: 'current_password',
                                    label: t(
                                        'Kata Sandi Saat Ini',
                                        'Current Password',
                                    ),
                                    show: showCurrentPw,
                                    toggle: () =>
                                        (showCurrentPw = !showCurrentPw),
                                },
                                {
                                    key: 'password',
                                    label: t('Kata Sandi Baru', 'New Password'),
                                    show: showNewPw,
                                    toggle: () => (showNewPw = !showNewPw),
                                },
                                {
                                    key: 'password_confirmation',
                                    label: t(
                                        'Konfirmasi Kata Sandi',
                                        'Confirm Password',
                                    ),
                                    show: showNewPw,
                                    toggle: () => {},
                                },
                            ]"
                            :key="field.key"
                        >
                            <label
                                class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5"
                            >
                                {{ field.label }}
                            </label>
                            <div class="relative">
                                <input
                                    v-model="passwordForm[field.key]"
                                    :type="field.show ? 'text' : 'password'"
                                    class="w-full px-3.5 py-2.5 pr-10 text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    :class="{
                                        'border-red-500':
                                            passwordForm.errors[field.key],
                                    }"
                                />
                                <button
                                    v-if="field.key !== 'password_confirmation'"
                                    type="button"
                                    @click="field.toggle()"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"
                                >
                                    <EyeOff v-if="field.show" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p
                                v-if="passwordForm.errors[field.key]"
                                class="mt-1 text-xs text-red-500"
                            >
                                {{ passwordForm.errors[field.key] }}
                            </p>
                        </div>
                    </div>
                    <div
                        class="px-5 py-4 border-t border-slate-100 dark:border-slate-700 flex justify-end"
                    >
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="flex items-center gap-2 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400 text-white text-sm font-semibold rounded-xl transition-colors"
                        >
                            <Lock class="w-4 h-4" />
                            {{
                                passwordForm.processing
                                    ? t("Menyimpan...", "Saving...")
                                    : t("Ubah Kata Sandi", "Change Password")
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
