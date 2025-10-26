<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Card, CardContent } from '@/components/ui/card';
import PageNav from '@/components/PageNav.vue';
import Heading from '@/components/Heading.vue';
import SearchInput from '@/components/SearchInput.vue';
import { useSearch } from '@/composables/useSearch';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { ref, watch } from 'vue';
import { format } from 'date-fns';
import Multiselect from '@vueform/multiselect';

defineProps(['products', 'categories']);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Laporan Stok', href: '#' },
];

const { search } = useSearch('reports.stock.index', route().params.search, ['products']);

const today = format(new Date(), 'yyyy-MM-dd');
const date = ref<[string, string] | null>([route().params.start ?? today, route().params.end ?? today]);
const category_id = ref();

const applyFilters = () => {
    const params: Record<string, string> = {};
    if (date.value && date.value[0] && date.value[1]) {
        params.start = date.value[0];
        params.end = date.value[1];
    }

    if (category_id.value) {
        params.category_id = category_id.value;
    }

    router.get(route('reports.stock.index'), params, {
        preserveScroll: true,
        preserveState: true,
    });
};

watch([date, category_id], applyFilters);
</script>

<template>
    <Head title="Laporan Stok" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Laporan Stok" description="Laporan stok terkini dan mutasi selama periode tertentu." />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="mb-3 flex flex-wrap justify-between items-center gap-3 sm:gap-4">
                            <div class="w-full sm:w-auto">
                                <Multiselect
                                    v-model="category_id"
                                    :options="categories"
                                    searchable
                                    placeholder="Kategori"
                                    class="w-full sm:min-w-52"
                                    size="sm"
                                />
                            </div>

                            <div class="w-full sm:w-auto flex-1 sm:flex-initial">
                                <VueDatePicker
                                    v-model="date"
                                    range
                                    :enable-time-picker="false"
                                    model-type="yyyy-MM-dd"
                                    format="yyyy-MM-dd"
                                    locale="id"
                                    class="w-full"
                                />
                            </div>
                        </div>

                        <div class="mb-3 flex flex-wrap items-center gap-3 sm:gap-4">
                            <div class="w-full sm:w-auto sm:ml-auto">
                                <div class="w-full sm:w-auto">
                                    <SearchInput v-model:search="search" />
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Produk</TableHead>
                                        <TableHead>Kategori</TableHead>
                                        <TableHead class="text-right">Masuk</TableHead>
                                        <TableHead class="text-right">Keluar</TableHead>
                                        <TableHead class="text-right">Net</TableHead>
                                        <TableHead class="text-right">Stok Saat Ini</TableHead>
                                        <TableHead class="text-center">Status</TableHead>
                                        <TableHead class="text-center w-32">Aksi</TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="prod in products.data"
                                        :key="prod.id"
                                        class="cursor-pointer hover:bg-muted/30"
                                        @click="router.get(route('reports.stock.show', prod.id))"
                                    >
                                        <TableCell>{{ prod.name }}</TableCell>
                                        <TableCell>{{ prod.category?.name || '-' }}</TableCell>

                                        <TableCell class="text-right">{{ prod.qty_in }}</TableCell>
                                        <TableCell class="text-right">{{ prod.qty_out }}</TableCell>
                                        <TableCell class="text-right font-medium">{{ prod.net_movement }}</TableCell>
                                        <TableCell class="text-right font-semibold">{{ prod.stock }}</TableCell>

                                        <TableCell class="text-center">
                                            <Badge
                                                :variant="
                                                    prod.stock <= 0
                                                        ? 'destructive'
                                                        : prod.net_movement < 0
                                                        ? 'secondary'
                                                        : 'outline'
                                                "
                                            >
                                                {{
                                                    prod.stock <= 0
                                                        ? 'Habis'
                                                        : prod.net_movement < 0
                                                            ? 'Menurun'
                                                            : 'Normal'
                                                }}
                                            </Badge>
                                        </TableCell>

                                        <TableCell class="text-center">
                                            <Button variant="outline" size="sm" @click.stop="router.get(route('reports.stock.show', prod.id))">
                                                Detail
                                            </Button>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!products.total">
                                        <TableCell colspan="8" class="text-center py-4 text-muted-foreground">
                                            Tidak ada data stok ditemukan.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                            <PageNav :data="products" />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<style src="@vueform/multiselect/themes/default.css" />
