<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Toko',
        url: 'store.settings.index',
        icon: 'X',
    },
    {
        title: 'Metode Pembayaran',
        url: 'store.payment-methods.index',
        icon: 'X',
    },
];

</script>

<template>
    <div class="px-4 py-6">
        <Heading title="Pengaturan Toko" description="Kelola data dari toko anda" />

        <div class="flex flex-col space-y-8 lg:space-y-0 lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-x-0 space-y-1">
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.url"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': route().current(item.url) }]"
                        as-child
                    >
                        <Link :href="route(item.url)">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-4xl">
                <section class="max-w-4xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
