<script lang="ts" setup>
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useFormat } from '@/composables/useFormat';
import Multiselect from '@vueform/multiselect';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import { onMounted } from 'vue';
import { useBarcodeScanner } from '@/composables/useBarcodeScanner';

defineProps<{
    suppliers: object;
    products: object;
    submitting?: boolean;
}>();

const form = defineModel<any>('form');

const { formatRupiah } = useFormat();

type PurchaseItem = {
    product_id: number | null;
    unit_price: number;
    qty: number;
    note?: string;
    _key?: string;
};

const defaultItem = (): PurchaseItem => ({
    product_id: null,
    unit_price: 0,
    qty: 1,
    note: '',
    _key: Math.random().toString(36).slice(2),
});

const addRow = (productId: number | null = null, unit_price: number = 0) => {
    form.value.items.push({
        ...defaultItem(),
        product_id: productId,
        unit_price,
    });
};
const removeRow = (idx: number) => form.value.items.splice(idx, 1);

const formTotal = () => form.value.items.reduce((sum: number, it: PurchaseItem) => sum + Number(it.unit_price || 0) * Number(it.qty || 0), 0);

const emit = defineEmits<{ (e: 'submit'): void }>();

const { scannerInput, scannedCode, refocusScanner, processScan } = useBarcodeScanner()

const handleFound = (product: any) => {
    const exists = form.value.items.find((it: any) => it.product_id === product.id)
    if (exists) {
        exists.qty += 1
    } else {
        const unit = product.units[0]
        addRow(product.id, unit?.pivot?.purchase_price || 0)
    }
}

onMounted(() => {
    refocusScanner();


    (window as any).testScan = (sku: string) => {
        scannedCode.value = sku
        processScan(handleFound)
    }
});
</script>

<template>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div>
            <label class="text-sm font-medium">Supplier</label>
            <Multiselect v-model="form.supplier_id" :options="suppliers" />
            <InputError :message="form.errors.supplier_id" />
        </div>
        <div>
            <label class="text-sm font-medium">Nomor PO</label>
            <Input v-model="form.purchase_number" class="h-10.5" placeholder="PO-XXXX" />
            <InputError :message="form.errors.purchase_number" />
        </div>
        <div>
            <label class="text-sm font-medium">Status</label>
            <Select v-model="form.status">
                <SelectTrigger id="payment_method">
                    <SelectValue placeholder="Pilih Status PO" />
                </SelectTrigger>
                <SelectContent class="w-52">
                    <SelectGroup>
                        <SelectItem value="draft">Draft</SelectItem>
                        <SelectItem value="ordered">Dipesan</SelectItem>
                        <SelectItem value="received">Diterima</SelectItem>
                        <SelectItem value="cancelled">Dibatalkan</SelectItem>
                    </SelectGroup>
                </SelectContent>
            </Select>
        </div>
        <div class="md:col-span-2">
            <label class="text-sm font-medium">Catatan</label>
            <Textarea v-model="form.note" class="w-full rounded border p-2" rows="2" />
        </div>
    </div>

    <div class="mt-4">
        <div class="mb-2 flex items-center justify-between">
            <h3 class="font-semibold">Item</h3>
            <Button size="sm" variant="secondary" @click="addRow">+ Item</Button>
        </div>
        <div class="overflow-x-auto">
            <Table class="w-full">
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[35%]">Produk</TableHead>
                        <TableHead>Qty</TableHead>
                        <TableHead>Harga/Unit</TableHead>
                        <TableHead class="w-32">Total</TableHead>
                        <TableHead class="w-8" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="(it, idx) in form.items" :key="it._key ?? idx">
                        <TableCell>
                            <Multiselect
                                v-model="it.product_id"
                                :options="products"
                                @change="() => form.clearErrors(`items.${idx}.product_id`)"
                                searchable
                                append-to-body
                            />
                            <InputError :message="form.errors[`items.${idx}.product_id`]" />
                        </TableCell>
                        <TableCell>
                            <Input type="number" min="1" v-model.number="it.qty" />
                        </TableCell>
                        <TableCell>
                            <Input type="number" min="0" v-model.number="it.unit_price" />
                        </TableCell>
                        <TableCell>{{ formatRupiah((it.qty || 0) * (it.unit_price || 0)) }}</TableCell>
                        <TableCell>
                            <Button size="icon" variant="destructive" @click="removeRow(idx)">✕</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div class="mt-3 text-right font-semibold">Total: {{ formatRupiah(formTotal()) }}</div>
    </div>

    <div class="mt-4 flex justify-end gap-2">
        <Button type="button" :disabled="submitting" @click="emit('submit')"> Simpan </Button>
    </div>

    <input
        ref="scannerInput"
        v-model="scannedCode"
        type="text"
        tabindex="-1"
        class="fixed top-1/2 left-1/2 opacity-0"
        @keyup.enter="processScan(handleFound)"
    />
</template>

<style src="@vueform/multiselect/themes/default.css" />
