import { reactive } from "vue";
import id from "./locales/id.json";
import en from "./locales/en.json";
import ja from "./locales/ja.json";
import phrases from "./locales/phrases.json";

// ── Single source of truth for locale ────────────────────────────────────────
// Priority: localStorage > user DB preference > default 'id'
const storedLocale = typeof localStorage !== "undefined" ? localStorage.getItem("locale") : null;
const initialLocale =
    storedLocale && ["id", "en", "ja"].includes(storedLocale) ? storedLocale : "id";

export const i18nState = reactive({ locale: initialLocale });

// ── Translations from Polyglot Hub build artifacts ────────────────────────────
export const messages = { id, en, ja };

/**
 * t() for use with key paths (e.g. $t('nav.dashboard')) or phrases
 */
export function t(key, fallback) {
    if (!key) return "";
    const activeLocale = i18nState.locale || "id";
    const dict = messages[activeLocale] || messages.id;

    // 1. Dot-path lookup in nested dictionary (e.g. 'nav.dashboard')
    if (typeof key === "string" && key.includes(".")) {
        const keys = key.split(".");
        let val = dict;
        for (const k of keys) {
            if (val === undefined || val === null) break;
            val = val[k];
        }
        if (typeof val === "string") return val;
    }

    // 2. Direct key lookup in root dictionary
    if (dict && typeof dict[key] === "string") {
        return dict[key];
    }

    // 3. Phrase map lookup
    if (phrases && phrases[activeLocale] && phrases[activeLocale][key]) {
        return phrases[activeLocale][key];
    }

    return fallback ?? key;
}

// i18n Vue plugin — installs $t and $i18n globally
export const i18n = {
    install(app) {
        app.config.globalProperties.$t = t;
        app.config.globalProperties.$i18n = i18nState;
    },
};
