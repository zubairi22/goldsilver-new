<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import { Card, CardContent } from '@/components/ui/card'
import OpnameForm from '@/pages/stock-opname/partial/OpnameForm.vue'
import type { BreadcrumbItem } from '@/types'

const { items, defaults } = defineProps<{
    items: any
    defaults: {
        opname_at: string
        status: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: route('stock-opnames.index') },
    { title: 'Tambah', href: '#' },
]

const form = useForm({
    opname_at: defaults.opname_at,
    notes: null,
    items: [] as number[],
})

const submit = () => {
    form.post(route('stock-opnames.store'), {
        preserveScroll: true,
        onSuccess: (page) => {
            const id = page.props?.opname?.id
            if (id) {
                router.visit(route('stock-opnames.edit', id))
            }
        },
    })
}
</script>

<template>
    <Head title="Tambah Stock Opname" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading
                class="mx-4"
                title="Tambah Stock Opname"
                description="Mulai audit fisik item emas"
            />

            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <OpnameForm
                            :form="form"
                            :items="items"
                            :submitting="form.processing"
                            @submit="submit"
                        />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
