import "./bootstrap";
import { createApp, h } from "vue";
import { createInertiaApp, Head, Link, router } from "@inertiajs/vue3";
import { resolvePageComponent } from "laravel-vite-plugin/inertia-helpers";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import { i18n, i18nState } from "./i18n";

const appName = import.meta.env.VITE_APP_NAME || "Arsip Digital Desa";

// ── Theme: apply before first render ─────────────────────────────────────────
const savedTheme = localStorage.getItem("theme") || "light";
document.documentElement.classList.toggle("dark", savedTheme === "dark");

// ── i18nState is initialized from localStorage inside i18n.js ────────────────
// No need to set it here — i18n.js already reads localStorage on module init.

// ── Inertia navigation hook ───────────────────────────────────────────────────
router.on("navigate", (event) => {
    const props = event.detail?.page?.props ?? {};

    // First page load: if localStorage has no locale yet, seed from server
    if (!localStorage.getItem("locale")) {
        const serverLocale = props.auth?.user?.locale ?? props.locale ?? "id";
        if (["id", "en", "ja"].includes(serverLocale)) {
            i18nState.locale = serverLocale;
            localStorage.setItem("locale", serverLocale);
        }
    }
    // NOTE: We intentionally do NOT sync i18nState from props on subsequent
    // navigations — localStorage is the source of truth after first load.

    // Theme: always sync from server (safe — no flicker)
    const userTheme = props.auth?.user?.theme;
    if (userTheme) {
        document.documentElement.classList.toggle("dark", userTheme === "dark");
        localStorage.setItem("theme", userTheme);
    }

    // Scroll to top
    window.scrollTo({ top: 0, behavior: "smooth" });
});

createInertiaApp({
    title: (title) => (title ? `${title} — ${appName}` : appName),

    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob("./Pages/**/*.vue"),
        ),

    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(i18n)
            .component("Head", Head)
            .component("Link", Link)
            .mount(el);
    },

    progress: {
        color: "#059669",
        showSpinner: false,
    },
});
