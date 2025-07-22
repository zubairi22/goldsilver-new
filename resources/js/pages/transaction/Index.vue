<script lang="ts" setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { LoaderCircle } from 'lucide-vue-next';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import DeleteButton from '@/components/DeleteButton.vue';
import { useCurrency } from '@/composables/useCurrency';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useSearch } from '@/composables/useSearch';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

defineProps(['products'])

const { formatRupiah } = useCurrency();
const { search } = useSearch('transactions.index', '', ['products']);

const form = useForm({
    items: [] as any[],
    paid_amount: '',
    payment_method: '',
});

const addModal = ref(false);
const selectedProduct = ref<any>(null)
const selectedUnitId = ref<number | null>(null);
const quantity = ref(1);

const successModal = ref(false);
const lastTransaction = ref<{ total: number; paid: number; change: number; } | null>(null);

const addToCart = (product: any) => {
    selectedProduct.value = product;
    selectedUnitId.value = product.units[0].id;
    quantity.value = 1;
    addModal.value = true;
};

const confirmAddToCart = () => {
    if (!selectedProduct.value || !selectedUnitId.value) return;

    const unit = selectedProduct.value.units.find((u: any) => u.id === selectedUnitId.value);
    if (!unit) return;

    const exists = form.items.find((item) =>
        item.product_id === selectedProduct.value.id && item.unit_id === unit.id
    );

    if (exists) {
        exists.quantity += quantity.value;
    } else {
        form.items.push({
            product_id: selectedProduct.value.id,
            unit_id: unit.id,
            name: selectedProduct.value.name,
            unit_name: unit.name,
            purchase_price: unit.pivot.purchase_price,
            selling_price: unit.pivot.selling_price,
            quantity: quantity.value,
        });
    }

    addModal.value = false;
};


const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const paymentModal = ref(false);
const paymentMethod = ref('cash');

const totalPrice = computed(() => {
    return form.items.reduce((sum, item) => sum + item.quantity * item.selling_price, 0);
});

const changeAmount = computed(() => {
    return parseFloat(form.paid_amount || '0') - totalPrice.value;
});

const submitTransaction = () => {
    form.payment_method = paymentMethod.value;

    form.post(route('transactions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            lastTransaction.value = {
                total: totalPrice.value,
                paid: parseFloat(form.paid_amount),
                change: changeAmount.value,
            };

            form.reset();
            paymentModal.value = false;
            successModal.value = true;
        },
    });
};

const draftModal = ref(false)

const saveDraft = () => {
    if (form.items.length === 0) return alert('Tidak ada item untuk disimpan.');

    const drafts = JSON.parse(localStorage.getItem('order_drafts') || '[]');
    drafts.push({
        id: Date.now(),
        items: form.items,
        note: 'Order sementara ' + new Date().toLocaleString()
    });

    localStorage.setItem('order_drafts', JSON.stringify(drafts));

    form.items = []

    alert('Order berhasil disimpan sementara!');
}

const draftList = ref<any[]>([])

function loadDrafts() {
    draftList.value = JSON.parse(localStorage.getItem('order_drafts') || '[]');
}

function loadDraftItems(draft: any) {
    form.items = draft.items;
    draftModal.value = false;
}

function deleteDraft(id: number) {
    const drafts = JSON.parse(localStorage.getItem('order_drafts') || '[]');
    const updated = drafts.filter((d: any) => d.id !== id);
    localStorage.setItem('order_drafts', JSON.stringify(updated));
    loadDrafts();
}


</script>

<template>
    <Head title="Kasir" />

    <AppLayout>
        <div class="py-6 px-4">
            <div class="flex items-center justify-between">
                <Heading title="Transaksi Kasir" description="Lakukan transaksi penjualan produk" />
                <Button variant="outline" size="lg" @click="draftModal = true; loadDrafts()">
                    Daftar Order
                </Button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <div class="md:col-span-2">
                    <Card>
                        <CardHeader>
                            <div class="flex gap-2">
                                <Input
                                    v-model="search"
                                    placeholder="Cari produk berdasarkan nama"
                                />
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                <div
                                    v-for="product in products"
                                    :key="product.id"
                                    class="border rounded-lg p-4 shadow-sm hover:shadow-md transition cursor-pointer"
                                    @click="addToCart(product)"
                                >
                                    <div class="text-base font-medium">{{ product.name }}</div>
                                    <div class="mt-2 text-lg font-semibold text-green-600">
                                        {{ formatRupiah(product.units[0].pivot.selling_price) }}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <div>
                    <Card class="flex flex-col h-[calc(100vh-200px)]">
                        <CardHeader>
                            <CardTitle>Keranjang</CardTitle>
                        </CardHeader>
                        <CardContent class="flex-1">
                            <div class="overflow-x-auto" v-if="form.items.length">
                                <Table class="w-full text-sm">
                                    <TableHeader>
                                    <TableRow>
                                        <TableHead/>
                                        <TableHead class="text-start">Produk</TableHead>
                                        <TableHead class="text-center">Harga</TableHead>
                                        <TableHead class="w-8"/>
                                    </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                    <TableRow v-for="(item, index) in form.items" :key="index" >
                                        <TableCell class="text-center">
                                            {{ item.quantity }}
                                        </TableCell>
                                        <TableCell>{{ item.name }} ({{ item.unit_name }})</TableCell>
                                        <TableCell class="text-end">{{ formatRupiah(item.selling_price) }}</TableCell>
                                        <TableCell>
                                            <DeleteButton size="sm" @click="removeItem(index)">Hapus</DeleteButton>
                                        </TableCell>
                                    </TableRow>
                                    </TableBody>
                                </Table>
                            </div>
                            <div v-else class="text-gray-500">Belum ada item.</div>
                        </CardContent>
                        <div class="flex flex-col gap-2 p-4 -mb-6 border-t">
                            <div class="flex justify-between items-center gap-2">
                                <DeleteButton variant="destructive" size="sm" @click="form.items = []"/>
                                <Button class="flex-1" variant="outline" size="sm" @click="saveDraft">
                                    Simpan Order
                                </Button>
                            </div>

                            <Button
                                class="w-full"
                                :disabled="form.items.length === 0"
                                @click="paymentModal = true"
                            >
                                Bayar
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>

    <Dialog :open="addModal" @update:open="(val) => addModal = val">
        <DialogContent @interactOutside.prevent>
            <DialogHeader>
                <DialogTitle>Detail Produk</DialogTitle>
                <DialogDescription>{{ selectedProduct.name }}.</DialogDescription>
            </DialogHeader>
            <div class="flex gap-6">
                <div class="w-1/2">
                    <Label for="unit">Satuan</Label>
                    <Select v-model="selectedUnitId">
                        <SelectTrigger id="satuan" class="w-full">
                            <SelectValue placeholder="Pilih Satuan" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="(unit, index) in selectedProduct.units"
                                    :key="unit.id"
                                    :value="unit.id"
                                >
                                    {{ unit.name }} - {{ formatRupiah(unit.pivot.selling_price) }}
                                    <span v-if="index > 0">
                                        ({{ unit.pivot.conversion }} {{ selectedProduct.units[0].name }})
                                    </span>
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
                <div class="w-1/2">
                    <Label for="kuantitas">Kuantitas</Label>
                    <Input class="h-10" v-model="quantity" type="number" @blur="quantity = quantity < 1 ? 1 : quantity" />
                </div>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="addModal = false">Batal</Button>
                <Button @click="confirmAddToCart">
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="paymentModal" @update:open="val => paymentModal = val">
        <DialogContent @interactOutside.prevent>
            <DialogHeader>
                <DialogTitle>Pembayaran</DialogTitle>
                <DialogDescription>Lengkapi detail pembayaran transaksi.</DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <div class="text-sm text-gray-500">Total</div>
                    <div class="text-xl font-bold">{{ formatRupiah(totalPrice) }}</div>
                </div>

                <div>
                    <Label for="method">Metode Pembayaran</Label>
                    <Select v-model="paymentMethod">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Pilih metode" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="cash">Tunai</SelectItem>
                                <SelectItem value="qris">QRIS</SelectItem>
                                <SelectItem value="debit">Debit</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>

                <div>
                    <Label for="paid_amount">Jumlah Bayar</Label>
                    <div class="flex items-center gap-2">
                        <Input
                            id="paid_amount"
                            v-model="form.paid_amount"
                            type="number"
                            min="0"
                            required
                            class="flex-1"
                        />
                        <Button type="button" @click="form.paid_amount = totalPrice">
                            Pas
                        </Button>
                    </div>
                    <InputError :message="form.errors.paid_amount" class="mt-1" />
                </div>

                <div>
                    <div class="text-sm text-gray-500">Kembalian</div>
                    <div class="text-lg font-semibold">{{ formatRupiah(changeAmount) }}</div>
                </div>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="paymentModal = false">Batal</Button>
                <Button :disabled="form.processing || totalPrice === 0" @click="submitTransaction">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin mr-2" />
                    Simpan Transaksi
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="successModal" @update:open="val => successModal = val">
        <DialogContent @interactOutside.prevent>
            <DialogHeader>
                <DialogTitle>Transaksi Berhasil</DialogTitle>
                <DialogDescription>Transaksi telah disimpan dengan sukses.</DialogDescription>
            </DialogHeader>

            <div class="space-y-3">
                <div class="flex justify-between text-sm">
                    <span>Total Tagihan:</span>
                    <span class="font-semibold">{{ formatRupiah(lastTransaction?.total ?? 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Jumlah Bayar:</span>
                    <span class="font-semibold">{{ formatRupiah(lastTransaction?.paid ?? 0) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>Kembalian:</span>
                    <span class="font-semibold">{{ formatRupiah(lastTransaction?.change ?? 0) }}</span>
                </div>
            </div>

            <DialogFooter class="gap-2 mt-4">
                <Button variant="secondary" @click="successModal = false">Tutup</Button>
<!--                <Button @click="printReceipt">Cetak Struk</Button>-->
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog :open="draftModal" @update:open="val => draftModal = val">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Daftar Order Sementara</DialogTitle>
                <DialogDescription>Pilih order yang ingin dilanjutkan.</DialogDescription>
            </DialogHeader>

            <div v-if="draftList.length">
                <ul class="space-y-2">
                    <li v-for="draft in draftList" :key="draft.id" class="flex justify-between items-center border p-2 rounded">
                        <div>
                            <div class="text-sm font-semibold">{{ draft.note }}</div>
                            <div class="text-xs text-gray-500">{{ draft.items.length }} item</div>
                        </div>
                        <div class="flex gap-2">
                            <Button size="sm" @click="loadDraftItems(draft)">Gunakan</Button>
                            <DeleteButton variant="destructive" size="sm" @click="deleteDraft(draft.id)"/>
                        </div>
                    </li>
                </ul>
            </div>
            <div v-else class="text-gray-500">Tidak ada order tersimpan.</div>
        </DialogContent>
    </Dialog>

</template>
