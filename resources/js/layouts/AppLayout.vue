<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Toaster } from '@/components/ui/sonner';
import 'vue-sonner/style.css';
import { usePage } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

watchEffect(() => {
    const flash = usePage().props.flash as any;
    if (flash?.message) {
        if (flash.status === 'error') {
            queueMicrotask(() => toast.error(flash.message));
        } else {
            queueMicrotask(() => toast.success(flash.message));
        }
    }
});
</script>

<template>
    <AppSidebarLayout :breadcrumbs="breadcrumbs">
        <Toaster position="top-right" richColors closeButton :duration="3000" />
        <slot />
    </AppSidebarLayout>
</template>
