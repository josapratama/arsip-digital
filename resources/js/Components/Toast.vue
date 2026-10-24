<script setup>
import { ref, watch, onUnmounted } from "vue";
import { CheckCircle2, XCircle, X } from "lucide-vue-next";

const props = defineProps({
    message: { type: String, default: "" },
    type: { type: String, default: "success" }, // 'success' | 'error'
    duration: { type: Number, default: 3500 },
});

const emit = defineEmits(["close"]);

const visible = ref(false);
let timer = null;

watch(
    () => props.message,
    (val) => {
        if (!val) return;
        visible.value = true;
        clearTimeout(timer);
        timer = setTimeout(() => {
            visible.value = false;
            emit("close");
        }, props.duration);
    },
    { immediate: true },
);

onUnmounted(() => clearTimeout(timer));
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-[-20px] opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-[-20px] opacity-0"
    >
        <div
            v-if="visible && message"
            :class="[
                'fixed top-4 left-1/2 -translate-x-1/2 z-9999',
                'flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl max-w-sm w-[calc(100vw-2rem)]',
                'border text-sm font-medium',
                type === 'error'
                    ? 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300'
                    : 'bg-emerald-50 dark:bg-emerald-950 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300',
            ]"
        >
            <CheckCircle2
                v-if="type !== 'error'"
                class="w-4.5 h-4.5 shrink-0"
            />
            <XCircle v-else class="w-4.5 h-4.5 shrink-0" />
            <span class="flex-1">{{ message }}</span>
            <button
                @click="
                    visible = false;
                    $emit('close');
                "
                class="shrink-0 opacity-60 hover:opacity-100 transition-opacity"
            >
                <X class="w-4 h-4" />
            </button>
        </div>
    </Transition>
</template>
