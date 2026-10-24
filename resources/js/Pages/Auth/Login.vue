<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { Eye, EyeOff, FolderArchive, Sun, Moon, ArrowLeft, ShieldCheck, Sparkles, Globe } from 'lucide-vue-next';
import { ref } from 'vue';
import { useI18n, setLocale } from '@/useI18n.js';

const { t, locale } = useI18n();

const showPassword = ref(false);
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
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'), { onFinish: () => form.reset('password') });
}

function fillCredentials(email, pwd) {
    form.email = email;
    form.password = pwd;
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-100 via-emerald-50/40 to-slate-200 dark:from-slate-950 dark:via-slate-900 dark:to-emerald-950/20 flex flex-col items-center justify-center p-4">

        <!-- Top bar navigation -->
        <div class="w-full max-w-md flex items-center justify-between mb-4">
            <Link
                :href="route('landing')"
                class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 bg-white/80 dark:bg-slate-800/80 backdrop-blur px-3 py-1.5 rounded-xl border border-slate-200/80 dark:border-slate-700 transition-colors shadow-xs"
            >
                <ArrowLeft class="w-3.5 h-3.5" />
                {{ t("Beranda", "Home") }}
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

                <!-- Register link in topbar if enabled by admin -->
                <Link
                    v-if="$page.props.can_register"
                    :href="route('register')"
                    class="text-xs font-semibold text-primary-700 dark:text-primary-300 hover:bg-primary-50 dark:hover:bg-primary-950/40 bg-white/80 dark:bg-slate-800/80 backdrop-blur px-3 py-1.5 rounded-xl border border-primary-200 dark:border-primary-800 transition-colors shadow-xs"
                >
                    {{ t("Daftar", "Register") }}
                </Link>

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

        <div class="w-full max-w-md">
            <!-- Card -->
            <div class="bg-white dark:bg-slate-800 rounded-3xl shadow-xl border border-slate-200/80 dark:border-slate-700 overflow-hidden">

                <!-- Header Banner -->
                <div class="bg-gradient-to-r from-primary-600 via-emerald-600 to-primary-700 px-8 py-8 text-center relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
                    <div class="w-14 h-14 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mx-auto mb-3 shadow-inner">
                        <FolderArchive class="w-7 h-7 text-white" />
                    </div>
                    <h1 class="text-xl font-bold text-white tracking-tight">Arsip Digital Desa</h1>
                    <p class="text-primary-100 text-xs mt-1">Pemerintah Desa Sukarami, Kec. Rambang</p>
                </div>

                <!-- Form -->
                <div class="px-8 py-7">
                    <div class="mb-5">
                        <h2 class="text-base font-bold text-slate-900 dark:text-white">
                            {{ t("Masuk ke Sistem", "Sign In to System") }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            {{ t("Gunakan akun resmi petugas atau administrator desa", "Use your official staff or administrator account") }}
                        </p>
                    </div>

                    <form @submit.prevent="submit" class="space-y-4">
                        <!-- Email -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                                {{ t("Surel / Email", "Email Address") }}
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                autocomplete="email"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border text-sm transition-colors
                                    bg-slate-50 dark:bg-slate-900
                                    border-slate-200 dark:border-slate-700
                                    text-slate-900 dark:text-white
                                    placeholder:text-slate-400
                                    focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                :class="{ 'border-red-500 focus:ring-red-500': form.errors.email }"
                                placeholder="nama@sukarami-desa.id"
                            />
                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-red-500 font-medium">{{ form.errors.email }}</p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5 uppercase tracking-wider">
                                {{ t("Kata Sandi", "Password") }}
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    required
                                    class="w-full px-3.5 py-2.5 pr-10 rounded-xl border text-sm transition-colors
                                        bg-slate-50 dark:bg-slate-900
                                        border-slate-200 dark:border-slate-700
                                        text-slate-900 dark:text-white
                                        placeholder:text-slate-400
                                        focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                    :class="{ 'border-red-500 focus:ring-red-500': form.errors.password }"
                                    placeholder="••••••••"
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
                            <p v-if="form.errors.password" class="mt-1.5 text-xs text-red-500 font-medium">{{ form.errors.password }}</p>
                        </div>

                        <!-- Remember -->
                        <div class="flex items-center justify-between text-xs">
                            <label class="flex items-center gap-2 text-slate-600 dark:text-slate-400 cursor-pointer">
                                <input
                                    v-model="form.remember"
                                    type="checkbox"
                                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 text-primary-600 focus:ring-primary-500"
                                />
                                <span>{{ t("Ingat Saya", "Remember Me") }}</span>
                            </label>
                            <span class="text-slate-400 flex items-center gap-1">
                                <ShieldCheck class="w-3.5 h-3.5 text-primary-600" />
                                {{ t("Aman & Terenkripsi", "Secure & Encrypted") }}
                            </span>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-2.5 px-4 bg-primary-600 hover:bg-primary-700 disabled:bg-primary-400
                                text-white text-sm font-semibold rounded-xl transition-all shadow-md hover:shadow-primary-600/20
                                focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        >
                            <span v-if="form.processing">{{ t("Memproses Masuk...", "Signing in...") }}</span>
                            <span v-else>{{ t("Masuk ke Dashboard", "Sign In to Dashboard") }}</span>
                        </button>
                    </form>

                    <!-- Register link if enabled by admin -->
                    <div v-if="$page.props.can_register" class="mt-5 text-center">
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            {{ t("Belum memiliki akun petugas?", "Don't have a staff account?") }}
                            <Link
                                :href="route('register')"
                                class="font-semibold text-primary-600 dark:text-primary-400 hover:text-primary-700 hover:underline ml-1"
                            >
                                {{ t("Daftar di Sini", "Register Here") }}
                            </Link>
                        </p>
                    </div>

                    <!-- Quick Credentials Hint for staff/demo -->
                    <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-700/60">
                        <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-2 text-center">
                            {{ t("Pilih Akun Cepat:", "Quick Switch Account:") }}
                        </p>
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <button
                                type="button"
                                @click="fillCredentials('sekdes@sukarami-desa.id', 'password')"
                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 hover:border-primary-400 text-left transition-colors"
                            >
                                <p class="font-bold text-slate-800 dark:text-slate-200">Sekdes</p>
                                <p class="text-[10px] text-slate-400 truncate">sekdes@sukarami-desa.id</p>
                            </button>
                            <button
                                type="button"
                                @click="fillCredentials('admin@sukarami-desa.id', 'password')"
                                class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/50 hover:border-primary-400 text-left transition-colors"
                            >
                                <p class="font-bold text-slate-800 dark:text-slate-200">Admin</p>
                                <p class="text-[10px] text-slate-400 truncate">admin@sukarami-desa.id</p>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 mt-5">
                Sistem Pengarsipan Digital — Kab. Muara Enim © {{ new Date().getFullYear() }}
            </p>
        </div>
    </div>
</template>
