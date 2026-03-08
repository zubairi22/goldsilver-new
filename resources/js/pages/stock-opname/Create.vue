<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Button from '@/components/ui/button/Button.vue';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import OpnameForm from '@/pages/stock-opname/partial/OpnameForm.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const { items, defaults } = defineProps<{
    items: any;
    defaults: {
        opname_at: string;
        status: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: route('store.stock-opnames.index') },
    { title: 'Tambah', href: '#' },
];

const form = useForm({
    opname_at: defaults.opname_at,
    notes: null,
    items: [] as number[],
});

const submit = () => {
    form.post(route('store.stock-opnames.store'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Tambah Stock Opname" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Tambah Stock Opname" description="Mulai audit fisik item emas" />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <OpnameForm :form="form" :items="items" :submitting="form.processing" @submit="submit" />

                        <div class="mt-4 flex justify-end">
                            <Button :disabled="form.processing" @click="submit"> Simpan </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
