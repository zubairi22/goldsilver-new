<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import { Card, CardContent } from '@/components/ui/card'
import PurchaseForm from '@/pages/purchase/partial/PurchaseForm.vue'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Purchase Order', href: route('outlet.purchases.index') },
    { title: 'Tambah', href: '#' },
]

const { defaults } = defineProps<{
    suppliers: object;
    products: object;
    defaults: { purchase_number: string, status: string },
}>()

const form = useForm({
    supplier_id: null as number | null,
    purchase_number: defaults.purchase_number,
    status: defaults.status,
    ordered_at: '',
    note: '',
    items: [],
})

const submit = () => {
    form.post(route('outlet.purchases.store'), { preserveScroll: true })
}
</script>

<template>
    <Head title="Tambah PO" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Tambah Purchase Order" description="Buat purchase order baru" />
            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <PurchaseForm :form="form" :suppliers="suppliers" :products="products" :submitting="form.processing" @submit="submit" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
