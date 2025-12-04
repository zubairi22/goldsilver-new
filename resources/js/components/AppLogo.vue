<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
</script>

<template>
    <div class="ml-1 grid flex-1 text-left text-md">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ usePage().props.name }}</span>
    </div>
</template>
