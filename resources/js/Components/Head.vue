<script setup>
import { Head as InertiaHead } from '@inertiajs/vue3';
defineProps({ title: String });
</script>
<template>
    <InertiaHead :title="title" />
</template>
