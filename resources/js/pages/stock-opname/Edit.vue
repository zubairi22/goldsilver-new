<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import { Card, CardContent } from '@/components/ui/card'
import OpnameForm from '@/pages/stock-opname/partial/OpnameForm.vue'
import type { BreadcrumbItem } from '@/types'

const { opname, items } = defineProps<{
    opname: any
    items: any
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Stock Opname', href: route('stock-opnames.index') },
    { title: opname.code, href: '#' },
]

const form = useForm({
    opname_at: opname.opname_at,
    notes: opname.notes,
    items: opname.items.map((i: any) => i.item_id),
})

const submit = () => {
    form.patch(route('stock-opnames.update', opname.id), {
        preserveScroll: true,
    })
}

const approve = () => {
    router.patch(route('stock-opnames.approve', opname.id))
}
</script>

<template>
    <Head title="Edit Stock Opname" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading
                class="mx-4"
                :title="`Stock Opname ${opname.code}`"
                description="Scan dan cocokkan item fisik emas"
            />

            <div class="mx-auto max-w-8xl">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <OpnameForm
                            :form="form"
                            :items="items"
                            :disabled="opname.status === 'approved'"
                            :submitting="form.processing"
                            @submit="submit"
                        />

                        <div
                            v-if="opname.status !== 'approved'"
                            class="mt-4 flex justify-end gap-2"
                        >
                            <Button
                                variant="secondary"
                                @click="submit"
                                :disabled="form.processing"
                            >
                                Simpan
                            </Button>

                            <Button
                                variant="default"
                                @click="approve"
                            >
                                Approve
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
