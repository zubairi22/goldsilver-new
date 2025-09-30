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
    { title: 'Edit', href: '#' },
]

const { purchase } = defineProps<{
    purchase: any,
    suppliers: object,
    products: object,
}>()

const mapItem = (it:any) => ({
    product_id: it.product_id,
    unit_price: it.unit_price,
    qty: it.qty,
    note: it.note ?? '',
    _key: Math.random().toString(36).slice(2),
})

const form = useForm({
    supplier_id: purchase.supplier_id,
    purchase_number: purchase.purchase_number,
    status: purchase.status,
    ordered_at: purchase.ordered_at,
    note: purchase.note,
    items: purchase.items.map(mapItem),
})

const submit = () => {
    form.patch(route('outlet.purchases.update', purchase.id), { preserveScroll: true })
}
</script>

<template>
    <Head title="Edit PO" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Edit Purchase Order" :description="`Ubah data PO ${purchase.purchase_number}`" />
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
