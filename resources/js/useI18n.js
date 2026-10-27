/**
 * useI18n composable
 *
 * Provides reactive translation helper powered by Polyglot Hub:
 * - Supports dual/triple arguments: t(idText, enText, jaText)
 * - Supports key lookups: t('nav.dashboard')
 * - Switches instantly without page reload
 */
import { computed } from "vue";
import { i18nState, t as i18nT } from "./i18n.js";
import phrases from "./locales/phrases.json";

export function useI18n() {
    const locale = computed(() => i18nState.locale);

    function t(arg1, arg2, arg3) {
        const curLocale = locale.value;

        // If called with multiple arguments: t(idText, enText, jaText)
        if (arg2 !== undefined) {
            if (curLocale === "id") return arg1;
            if (curLocale === "en") return arg2 || arg1;
            if (curLocale === "ja") {
                if (arg3) return arg3;
                if (phrases && phrases.ja) {
                    if (phrases.ja[arg1]) return phrases.ja[arg1];
                    if (phrases.ja[arg2]) return phrases.ja[arg2];
                }
                return arg2 || arg1;
            }
            return arg1;
        }

        // If called with single argument: key path or phrase lookup
        return i18nT(arg1);
    }

    return { t, locale };
}

/**
 * setLocale — call this whenever locale changes.
 * Updates reactive state AND localStorage so it survives navigation.
 */
export function setLocale(newLocale) {
    if (!["id", "en", "ja"].includes(newLocale)) return;
    i18nState.locale = newLocale;
    localStorage.setItem("locale", newLocale);
}
