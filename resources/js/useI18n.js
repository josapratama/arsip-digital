/**
 * useI18n composable
 *
 * Provides a reactive t(idText, enText) that switches instantly
 * when i18nState.locale changes — no page reload needed.
 */
import { computed } from "vue";
import { i18nState } from "./i18n.js";

export function useI18n() {
    const locale = computed(() => i18nState.locale);

    function t(idText, enText) {
        return locale.value === "en" ? enText : idText;
    }

    return { t, locale };
}

/**
 * setLocale — call this whenever locale changes.
 * Updates reactive state AND localStorage so it survives navigation.
 */
export function setLocale(newLocale) {
    if (!["id", "en"].includes(newLocale)) return;
    i18nState.locale = newLocale;
    localStorage.setItem("locale", newLocale);
}
