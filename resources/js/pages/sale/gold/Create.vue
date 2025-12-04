<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { computed, ref } from 'vue'
import { useFormat } from '@/composables/useFormat'
import { toast } from 'vue-sonner'
import type { BreadcrumbItem } from '@/types'
import Multiselect from '@vueform/multiselect'
import InputError from '@/components/InputError.vue'
import DeleteButton from '@/components/DeleteButton.vue'
import { Textarea } from '@/components/ui/textarea'
import CurrencyInput from '@/components/CurrencyInput.vue';

const { formatRupiah } = useFormat()

defineProps(['paymentMethods', 'customers', 'items'])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Penjualan Emas', href: '/sale/gold' },
    { title: 'Tambah', href: '#' },
]

const form = useForm({
    category: 'gold',
    sale_type: 'retail',
    mode: 'auto',
    customer_id: '',
    payment_method_id: '',
    paid_amount: 0,
    items: <any>[],
})

const showAddItemModal = ref(false)
const editIndex = ref<number|null>(null)

const modalItem = ref<any>({
    id: null,
    manual_name: '',
    weight: 0,
    price: 0,
})

const addItem = () => {
    modalItem.value = { id: null, manual_name: '', weight: 0, price: 0 }
    editIndex.value = null
    showAddItemModal.value = true
}

const editItem = (index: number) => {
    const it = form.items[index]
    modalItem.value = {
        id: it.id ?? null,
        manual_name: it.manual_name ?? '',
        weight: it.weight,
        price: it.price,
    }
    editIndex.value = index
    showAddItemModal.value = true
}

const removeItem = (index: number) => {
    form.items.splice(index, 1)
}

const modalSubtotal = computed(() => {
    const w = Number(modalItem.value.weight || 0)
    const p = Number(modalItem.value.price || 0)
    return Math.round(w * p)
})

const totalPrice = computed(() => {
    const total = form.items.reduce((sum: any, i: any) => sum + Number(i.subtotal || 0), 0)
    return Math.round(total)
})

const totalWeight = computed(() =>
    form.items.reduce((sum: any, i: any) => sum + Number(i.weight || 0), 0)
)

const change = computed(() => {
    const raw = Number(form.paid_amount) - Number(totalPrice.value);
    const clean = Math.round(raw);
    return clean < 0 ? 0 : clean;
});


const saveModalItem = () => {
    if (form.mode === 'auto' && !modalItem.value.id) {
        toast.error('Silakan pilih barang dari stok.')
        return
    }
    if (form.mode === 'manual' && !modalItem.value.manual_name) {
        toast.error('Nama barang harus diisi.')
        return
    }
    if (modalSubtotal.value <= 0) {
        toast.error('Berat dan harga harus lebih dari 0.')
        return
    }

    form.items.push({
        ...modalItem.value,
        mode: form.mode,
        subtotal: Math.round(modalSubtotal.value),
    })

    showAddItemModal.value = false
}

const updateModalItem = () => {
    if (editIndex.value === null) return

    form.items[editIndex.value] = {
        ...modalItem.value,
        mode: form.mode,
        subtotal: Math.round(modalSubtotal.value),
    }

    editIndex.value = null
    showAddItemModal.value = false
}

const setExactPayment = () => {
    form.paid_amount = Number(totalPrice.value)
}

const submitSale = () => {
    if (!form.items.length) {
        toast.error('Minimal 1 item harus ditambahkan.')
        return
    }

    form.post(route('transactions.sales.gold.store'), {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Penjualan emas berhasil disimpan.')
            form.reset()
        },
        onError: () => {
            toast.error('Terjadi kesalahan saat menyimpan. Periksa kembali input.')
        },
    })
}
</script>

<template>
    <Head title="Tambah Penjualan Emas" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-6 px-4">
            <div class="max-w-8xl mx-auto space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle class="mb-1">Informasi Penjualan Emas</CardTitle>
                        <hr>
                    </CardHeader>
                    <CardContent class="grid md:grid-cols-2 gap-4">
                        <div>
                            <Label>Tipe Penjualan</Label>
                            <Select v-model="form.sale_type">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="retail">Eceran</SelectItem>
                                    <SelectItem value="wholesale">Partai</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.sale_type" />
                        </div>

                        <div>
                            <Label>Mode Input</Label>
                            <Select v-model="form.mode">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="auto">Dari Stok</SelectItem>
                                    <SelectItem value="manual">Manual</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.mode" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <div class="flex justify-between items-center mb-1">
                            <CardTitle>Daftar Item</CardTitle>
                            <Button @click="addItem">Tambah Item</Button>
                        </div>
                        <hr>
                    </CardHeader>

                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead class="w-[35%]">Nama Barang</TableHead>
                                        <TableHead class="w-28">Berat (g)</TableHead>
                                        <TableHead class="w-32">Harga/gram</TableHead>
                                        <TableHead class="w-32">Subtotal</TableHead>
                                        <TableHead class="w-8"></TableHead>
                                    </TableRow>
                                </TableHeader>

                                <TableBody>
                                    <TableRow
                                        v-for="(item, index) in form.items"
                                        :key="index"
                                        class="cursor-pointer hover:bg-muted/50"
                                        @click="editItem(index)"
                                    >
                                        <TableCell>
                                            <span v-if="item.mode === 'auto'">
                                                {{ items.find((i: any) => i.id === item.id)?.name ?? '-' }}
                                            </span>
                                            <span v-else>{{ item.manual_name }}</span>
                                        </TableCell>
                                        <TableCell>{{ item.weight }} g</TableCell>
                                        <TableCell>{{ formatRupiah(item.price) }}</TableCell>
                                        <TableCell>{{ formatRupiah(item.subtotal) }}</TableCell>
                                        <TableCell @click.stop>
                                            <DeleteButton size="icon" @confirm="removeItem(index)" />
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="!form.items.length">
                                        <TableCell colspan="10" class="text-center py-4">
                                            Belum ada item yang ditambahkan.
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <InputError class="mt-2" :message="form.errors.items" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="mb-1">Pembayaran</CardTitle>
                        <hr />
                    </CardHeader>

                    <CardContent class="space-y-8">

                        <!-- SUMMARY BOX -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="p-4 rounded-lg bg-muted/40 border">
                                <div class="text-sm text-muted-foreground">Total Berat</div>
                                <div class="font-bold text-2xl mt-1">{{ totalWeight.toFixed(2) }} g</div>
                            </div>

                            <div class="p-4 rounded-lg bg-muted/40 border">
                                <div class="text-sm text-muted-foreground">Total Harga</div>
                                <div class="font-bold text-2xl mt-1">{{ formatRupiah(totalPrice) }}</div>
                            </div>

                            <div class="p-4 rounded-lg bg-muted/40 border">
                                <div class="text-sm text-muted-foreground">Kembalian</div>
                                <div class="font-bold text-2xl mt-1">{{ formatRupiah(change) }}</div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-6">
                                <div>
                                    <Label>Jenis Pembayaran</Label>
                                    <Select v-model="form.payment_method_id" @change="form.clearErrors('payment_method_id')">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih metode pembayaran" />
                                        </SelectTrigger>
                                        <SelectContent class="w-80">
                                            <SelectGroup>
                                                <SelectItem
                                                    v-for="pm in paymentMethods"
                                                    :key="pm.id"
                                                    :value="pm.id"
                                                >
                                                    {{ pm.name }}
                                                </SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.payment_method_id" />
                                </div>

                                <div>
                                    <Label>Bayar</Label>
                                    <div class="flex items-center gap-2">
                                        <CurrencyInput
                                            v-model.number="form.paid_amount"
                                            @input="form.clearErrors('paid_amount')"
                                            class="flex-1"
                                        />

                                        <Button
                                            type="button"
                                            class="whitespace-nowrap"
                                            @click="setExactPayment"
                                        >
                                            PAS
                                        </Button>
                                    </div>

                                    <InputError :message="form.errors.paid_amount" />
                                </div>
                            </div>

                            <div class="flex flex-col gap-6">

                                <div>
                                    <Label>Pelanggan</Label>
                                    <Multiselect v-model="form.customer_id" :options="customers" />
                                    <InputError :message="form.errors.customer_id" />
                                </div>

                            </div>
                        </div>

                        <div class="pt-4 text-right">
                            <Button @click="submitSale" :disabled="form.processing" class="px-6">
                                <span v-if="form.processing">Menyimpan...</span>
                                <span v-else>Simpan Transaksi</span>
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="showAddItemModal">
            <DialogContent class="max-w-md">
                <DialogHeader>
                    <DialogTitle>{{ editIndex === null ? 'Tambah Item' : 'Edit Item' }}</DialogTitle>
                </DialogHeader>

                <div class="space-y-4">
                    <div v-if="form.mode === 'auto'">
                        <Label>Barang dari Stok</Label>
                        <Multiselect
                            v-model="modalItem.id"
                            :options="items"
                            value-prop="id"
                            label="name"
                            searchable
                            placeholder="Pilih barang"
                            @change="(value) => {
                                const it = items.find((p: any) => p.id === value)
                                modalItem.price = it?.price_sell ?? 0
                                modalItem.weight = it?.weight ?? 0
                            }"
                        />
                    </div>

                    <div v-else>
                        <Label>Nama Barang</Label>
                        <Input v-model="modalItem.manual_name" />
                    </div>

                    <div>
                        <Label>Berat (g)</Label>
                        <Input type="number" min="0" step="0.01" v-model.number="modalItem.weight" />
                    </div>

                    <div>
                        <Label>Harga</Label>
                        <Input type="number" min="0" v-model.number="modalItem.price" />
                    </div>

                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-semibold">{{ formatRupiah(modalSubtotal) }}</span>
                    </div>
                </div>

                <DialogFooter>
                    <Button variant="outline" @click="showAddItemModal = false">Batal</Button>

                    <Button v-if="editIndex === null" @click="saveModalItem">Tambah</Button>
                    <Button v-else @click="updateModalItem">Simpan Perubahan</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </AppLayout>
</template>

<style src="@vueform/multiselect/themes/default.css" />
