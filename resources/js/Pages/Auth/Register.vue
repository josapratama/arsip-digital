<script setup>
import { useForm, Link, Head } from '@inertiajs/vue3';
import { Eye, EyeOff, UserPlus, Sun, Moon, ArrowLeft, ShieldCheck, CheckCircle2, Globe } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n, setLocale } from '@/useI18n.js';

const { t, locale } = useI18n();

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const isDark = ref(document.documentElement.classList.contains('dark'));

function toggleTheme() {
    isDark.value = !isDark.value;
    document.documentElement.classList.toggle('dark', isDark.value);
    localStorage.setItem('theme', isDark.value ? 'dark' : 'light');
}

function toggleLocale() {
    setLocale(locale.value === 'id' ? 'en' : 'id');
}

const form = useForm({
    name: '',
    email: '',
    jabatan: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
}
</script>

<template>
    <Head :title="t('Daftar Akun Petugas', 'Register Staff Account')" />

    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-emerald-50/40 to-slate-200 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950/20 flex flex-col items-center justify-center p-4 py-8">

        <!-- Top bar navigation -->
        <div class="w-full max-w-lg flex items-center justify-between mb-4">
            <Link
                :href="route('login')"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 bg-white/80 dark:bg-slate-800/80 backdrop-blur px-3 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700 transition-colors shadow-xs"
            >
                <ArrowLeft class="w-3.5 h-3.5" />
                {{ t("Kembali ke Masuk", "Back to Login") }}
            </Link>

            <div class="flex items-center gap-2">
                <!-- Language switcher -->
                <button
                    @click="toggleLocale"
                    :title="t('Ganti Bahasa', 'Change Language')"
                    class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700 bg-white/80 dark:bg-slate-800/80 backdrop-blur text-xs font-bold text-slate-700 dark:text-slate-200 hover:border-primary-500 transition-colors shadow-xs font-mono"
                >
                    <Globe class="w-3.5 h-3.5 text-primary-600 dark:text-primary-400" />
                    <span>{{ locale.toUpperCase() }}</span>
                </button>

                <!-- Theme toggle -->
                <button
                    @click="toggleTheme"
                    :title="t('Ganti tema', 'Toggle theme')"
                    class="p-2 rounded-xl bg-white/80 dark:bg-slate-800/80 backdrop-blur shadow-xs border border-slate-200/80 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:text-primary-600 transition-colors"
                >
                    <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
                    <Moon v-else class="w-4 h-4 text-slate-600" />
                </button>
            </div>
        </div>

        <div class="w-full max-w-lg">
            <!-- Card -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-200/80 dark:border-slate-700 overflow-hidden">

                <!-- Header Banner -->
                <div class="bg-gradient-to-r from-primary-600 via-emerald-600 to-primary-700 px-8 py-7 text-center relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="w-13 h-13 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-inner">
                        <UserPlus class="w-6.5 h-6.5 text-white" />
                    </div>
                    <h1 class="text-xl font-bold text-white tracking-tight">
                        {{ t("Pendaftaran Akun Baru", "New Account Registration") }}
                    </h1>
                    <p class="text-primary-100 text-xs mt-1">
                        Sistem Informasi Kearsipan — Desa Sukarami
                    </p>
                </div>

                <!-- Form -->
                <div class="px-8 py-7">
                    <div class="mb-5">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ t("Lengkapi Data Petugas", "Fill in Staff Details") }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ t("Akun baru akan langsung terhubung sebagai petugas arsip desa", "New account will be registered as village archive staff") }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                                {{ t("Nama Lengkap", "Full Name") }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                autofocus
                                :placeholder="t('Contoh: Ahmad Fauzi, S.Pd', 'Example: Ahmad Fauzi')"
                                class="w-full px-3.5 py-2.5 rounded-xl border text-sm transition-colors
                                    bg-slate-50 dark:bg-slate-900
                                    border-slate-200 dark:border-slate-700
                                    text-slate-900 dark:text-white
                                    placeholder:text-slate-400
                                    focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-xs text-red-500 font-medium">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                                {{ t("Alamat Surel / Email", "Email Address") }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                required
                                :placeholder="t('nama@sukarami-desa.id atau email aktif', 'name@example.com')"
                                class="w-full px-3.5 py-2.5 rounded-xl border text-sm transition-colors
                                    bg-slate-50 dark:bg-slate-900
                                    border-slate-200 dark:border-slate-700
                                    text-slate-900 dark:text-white
                                    placeholder:text-slate-400
                                    focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-xs text-red-500 font-medium">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <!-- Jabatan / Peran -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                                {{ t("Jabatan / Peran Petugas", "Role / Position") }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.jabatan"
                                type="text"
                                required
                                :placeholder="t('Contoh: Staf TU / Operator Arsip / Kasi Pelayanan', 'Example: Archive Operator')"
                                class="w-full px-3.5 py-2.5 rounded-xl border text-sm transition-colors
                                    bg-slate-50 dark:bg-slate-900
                                    border-slate-200 dark:border-slate-700
                                    text-slate-900 dark:text-white
                                    placeholder:text-slate-400
                                    focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.jabatan }"
                            />
                            <p v-if="form.errors.jabatan" class="mt-1 text-xs text-red-500 font-medium">
                                {{ form.errors.jabatan }}
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                                {{ t("Kata Sandi", "Password") }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    placeholder="••••••••"
                                    class="w-full px-3.5 py-2.5 pr-10 rounded-xl border text-sm transition-colors
                                        bg-slate-50 dark:bg-slate-900
                                        border-slate-200 dark:border-slate-700
                                        text-slate-900 dark:text-white
                                        placeholder:text-slate-400
                                        focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500 focus:ring-red-500': form.errors.password }"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                >
                                    <EyeOff v-if="showPassword" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1 text-xs text-red-500 font-medium">
                                {{ form.errors.password }}
                            </p>
                        </div>

                        <!-- Konfirmasi Password -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                                {{ t("Konfirmasi Kata Sandi", "Confirm Password") }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.password_confirmation"
                                    :type="showPasswordConfirmation ? 'text' : 'password'"
                                    autocomplete="new-password"
                                    required
                                    placeholder="••••••••"
                                    class="w-full px-3.5 py-2.5 pr-10 rounded-xl border text-sm transition-colors
                                        bg-slate-50 dark:bg-slate-900
                                        border-slate-200 dark:border-slate-700
                                        text-slate-900 dark:text-white
                                        placeholder:text-slate-400
                                        focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500 focus:ring-red-500': form.errors.password_confirmation }"
                                />
                                <button
                                    type="button"
                                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300"
                                >
                                    <EyeOff v-if="showPasswordConfirmation" class="w-4 h-4" />
                                    <Eye v-else class="w-4 h-4" />
                                </button>
                            </div>
                            <p v-if="form.errors.password_confirmation" class="mt-1 text-xs text-red-500 font-medium">
                                {{ form.errors.password_confirmation }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full py-2.5 px-4 bg-primary-600 hover:bg-primary-700 active:bg-primary-800 disabled:opacity-50 text-white font-semibold text-sm rounded-xl transition-all shadow-md shadow-primary-500/20 flex items-center justify-center gap-2 cursor-pointer"
                            >
                                <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                <UserPlus v-else class="w-4 h-4" />
                                <span>{{ form.processing ? t('Mendaftarkan...', 'Registering...') : t('Daftar Akun Sekarang', 'Register Account Now') }}</span>
                            </button>
                        </div>
                    </form>

                    <!-- Footer Link back to Login -->
                    <div class="mt-6 pt-5 border-t border-slate-100 dark:border-slate-700/60 text-center">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ t("Sudah memiliki akun resmi?", "Already have an official account?") }}
                            <Link
                                :href="route('login')"
                                class="font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 hover:underline ml-1"
                            >
                                {{ t("Masuk di Sini", "Sign In Here") }}
                            </Link>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Security Footer Note -->
            <p class="text-center text-xs text-slate-500 dark:text-slate-400 mt-5 flex items-center justify-center gap-1.5">
                <ShieldCheck class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                <span>{{ t("Sistem Arsip Resmi Desa Sukarami — Keamanan Terjamin", "Official Digital Archive System of Sukarami Village") }}</span>
            </p>
        </div>
    </div>
</template>
