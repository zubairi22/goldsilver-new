<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import { Toaster } from '@/components/ui/sonner';
import 'vue-sonner/style.css';
import { usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import { toast } from 'vue-sonner';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const isAdmin = usePage().props.auth.user.roles.some((role: any) => role.name.includes('admin'));

const Layout = computed(() => (isAdmin ? AppSidebarLayout : AppHeaderLayout));

watchEffect(() => {
    const flash = usePage().props.flash as any;
    if (flash?.message) {
        if (flash.status === 'error') {
            toast.error(flash.message);
        } else {
            toast.success(flash.message);
        }
    }
});
</script>

<template>
    <Layout :breadcrumbs="breadcrumbs">
        <Toaster position="top-right" richColors closeButton :duration="3000" />
        <slot />
    </Layout>
</template>
