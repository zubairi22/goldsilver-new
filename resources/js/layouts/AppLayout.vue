<script setup lang="ts">
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import AppHeaderLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import Toaster from '@/components/Toaster.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const isAdmin = usePage().props.auth.user.roles.some((role : any) => role.name.includes('admin'))

const Layout = computed(() =>
    isAdmin ? AppSidebarLayout : AppHeaderLayout
);
</script>

<template>
    <Layout :breadcrumbs="breadcrumbs">
        <Toaster :flash="usePage().props.flash"/>
        <slot />
    </Layout>
</template>
