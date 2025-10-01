<script lang="ts" setup>
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import PageNav from '@/components/PageNav.vue';
import { useFormat } from '@/composables/useFormat';
import { useSearch } from '@/composables/useSearch';

defineProps(['products']);
const emit = defineEmits(['add-to-cart']);

const { search } = useSearch('cashier.index', route().params.search, ['products']);

const { formatRupiah } = useFormat();

const handleClick = (product: any) => {
    emit('add-to-cart', product);
};
</script>

<template>
    <Card>
        <CardHeader>
            <Input v-model="search" type="search" placeholder="Cari produk berdasarkan nama dan sku" />
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3">
                <div
                    v-for="product in products.data"
                    :key="product.id"
                    class="cursor-pointer rounded-lg border p-4 shadow-sm transition hover:shadow-md"
                    @click="handleClick(product)"
                >
                    <div class="line-clamp-2 min-h-[3rem] text-base font-medium" :title="product.name">
                        {{ product.name }}
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <div class="text-sm font-semibold text-green-700">
                            {{ formatRupiah(product.units[0].pivot.selling_price) }}
                        </div>
                        <div :class="product.stock < 50 ? 'text-red-600 text-sm' : 'text-gray-800 text-sm'">
                            ({{ product.stock }})
                        </div>
                    </div>
                </div>
            </div>
            <b v-if="!products.total">Produk tidak ditemukan</b>
            <div class="mt-4 flex justify-center overflow-x-auto">
                <PageNav :data="products" />
            </div>
        </CardContent>
    </Card>
</template>
