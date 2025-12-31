<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { ref, watch } from 'vue'
import { useFormat } from '@/composables/useFormat'
import VueDatePicker from '@vuepic/vue-datepicker'
import '@vuepic/vue-datepicker/dist/main.css'
import Icon from '@/components/Icon.vue'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

const props = defineProps<{
    summary: {
        totalSales: number
        totalBuyback: number
    }
    filters: {
        mode: 'daily' | 'weekly' | 'monthly'
        start: string
        end: string
    }
}>()

const { formatRupiah } = useFormat()

const mode = ref<'daily' | 'weekly' | 'monthly'>(route().params.mode ?? props.filters.mode ?? 'daily')

const date = ref<[string, string] | null>(
    route().params.start && route().params.end
        ? [route().params.start, route().params.end]
        : null
)

const applyFilters = () => {
    const params: Record<string, string> = {}

    if (date.value && date.value[0] && date.value[1]) {
        params.mode = ''
        params.start = date.value[0]
        params.end = date.value[1]
    } else {
        date.value = null
        params.mode = mode.value
    }

    router.get(route('dashboard'), params, {
        preserveScroll: true,
        preserveState: true,
    })
}

watch(mode, (val) => {
    if (val) date.value = null
    applyFilters()
})

watch(date, () => {
    applyFilters()
})
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout>
        <div class="flex justify-end gap-3 px-8 pt-4 mb-4">
            <div class="w-32">
                <Select v-model="mode">
                    <SelectTrigger id="mode">
                        <SelectValue placeholder="Pilih Mode" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem value="daily">Hari Ini</SelectItem>
                            <SelectItem value="weekly">Minggu Ini</SelectItem>
                            <SelectItem value="monthly">Bulan Ini</SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
            </div>

            <div class="w-64">
                <VueDatePicker
                    v-model="date"
                    range
                    :enable-time-picker="false"
                    model-type="yyyy-MM-dd"
                    locale="id"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 px-4 pb-6">
            <Card class="gap-0 bg-emerald-50 border-emerald-200">
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="text-sm font-medium text-emerald-700">Total Penjualan</CardTitle>
                    <Icon name="DollarSign" class="w-5 h-5 text-emerald-600" />
                </CardHeader>
                <CardContent class="py-2">
                    <p class="text-xl font-bold text-emerald-800">
                        {{ formatRupiah(summary.totalSales) }}
                    </p>
                </CardContent>
            </Card>

            <Card class="gap-0 bg-rose-50 border-rose-200">
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="text-sm font-medium text-rose-700">Total Buyback</CardTitle>
                    <Icon name="DollarSign" class="w-5 h-5 text-rose-600" />
                </CardHeader>
                <CardContent class="py-2">
                    <p class="text-xl font-bold text-rose-800">
                        {{ formatRupiah(summary.totalBuyback) }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
