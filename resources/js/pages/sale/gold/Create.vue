<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { ref, computed } from 'vue'
import { useFormat } from '@/composables/useFormat'
import { toast } from 'vue-sonner'
import type { BreadcrumbItem } from '@/types'

const { formatRupiah } = useFormat()

defineProps(['paymentMethods', 'customers', 'items'])

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Penjualan Emas', href: '/sale/gold' },
    { title: 'Tambah', href: '#' },
]

const saleType = ref<'retail' | 'wholesale'>('retail')
const inputMode = ref<'auto' | 'manual'>('auto')
const customerId = ref<number | null>(null)
const paymentMethodId = ref<number | null>(null)
const paidAmount = ref<number>(0)
const note = ref<string>('')

interface SaleItem {
    id?: number
    manual_name?: string
    weight: number
    price: number
    subtotal: number
}

const saleItems = ref<SaleItem[]>([])

const addItem = () => {
    saleItems.value.push({ weight: 0, price: 0, subtotal: 0 })
}

const removeItem = (index: number) => {
    saleItems.value.splice(index, 1)
}

const updateSubtotal = (item: SaleItem) => {
    item.subtotal = item.weight * item.price
}

const totalWeight = computed(() => saleItems.value.reduce((sum, i) => sum + i.weight, 0))
const totalPrice = computed(() => saleItems.value.reduce((sum, i) => sum + i.subtotal, 0))
const remainingAmount = computed(() => Math.max(totalPrice.value - paidAmount.value, 0))

const submitSale = () => {
    if (!saleItems.value.length) {
        toast({ title: 'Gagal', description: 'Minimal 1 item harus ditambahkan.', variant: 'destructive' })
        return
    }

    router.post('/sale/gold', {
        category: 'gold',
        sale_type: saleType.value,
        mode: inputMode.value,
        customer_id: customerId.value,
        payment_method_id: paymentMethodId.value,
        paid_amount: paidAmount.value,
        items: saleItems.value,
    }, {
        onSuccess: () => {
            toast({ title: 'Berhasil', description: 'Penjualan emas berhasil disimpan.' })
        },
        onError: () => {
            toast({ title: 'Gagal', description: 'Terjadi kesalahan saat menyimpan.' })
        }
    })
}
</script>

<template>
    <Head title="Tambah Penjualan Emas" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <div class="max-w-6xl mx-auto space-y-6">

                <Card>
                    <CardHeader>
                        <CardTitle>Informasi Penjualan</CardTitle>
                    </CardHeader>
                    <CardContent class="grid md:grid-cols-2 gap-4">

                        <div>
                            <label class="text-sm font-medium">Tipe Penjualan</label>
                            <Select v-model="saleType">
                                <SelectTrigger><SelectValue placeholder="Pilih Tipe" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="retail">Eceran</SelectItem>
                                    <SelectItem value="wholesale">Partai</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <label class="text-sm font-medium">Mode Input</label>
                            <Select v-model="inputMode">
                                <SelectTrigger><SelectValue placeholder="Pilih Mode" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="auto">Otomatis</SelectItem>
                                    <SelectItem value="manual">Manual</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex justify-between items-center">
                        <CardTitle>Daftar Item</CardTitle>
                        <Button @click="addItem">Tambah Item</Button>
                    </CardHeader>

                    <CardContent>
                        <div v-if="!saleItems.length" class="text-gray-500 text-center py-6">Belum ada item ditambahkan.</div>

                        <div v-for="(item, index) in saleItems" :key="index" class="grid md:grid-cols-5 gap-3 items-end mb-3 border-b pb-3">
                            <div class="md:col-span-2">
                                <label class="text-sm font-medium">Nama Barang</label>
                                <template v-if="inputMode === 'auto'">
                                    <Select v-model="item.id" @update:modelValue="val => { const i = items.find((it: any) => it.id === val); item.price = i?.price_per_gram ?? 0; updateSubtotal(item) }">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih Item" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="it in items" :key="it.id" :value="it.id">{{ it.name }}</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </template>
                                <template v-else>
                                    <Input v-model="item.manual_name" placeholder="Nama Barang" />
                                </template>
                            </div>

                            <div>
                                <label class="text-sm font-medium">Berat (gram)</label>
                                <Input type="number" min="0" step="0.01" v-model.number="item.weight" @input="updateSubtotal(item)" />
                            </div>

                            <div>
                                <label class="text-sm font-medium">Harga / gram</label>
                                <Input type="number" min="0" v-model.number="item.price" @input="updateSubtotal(item)" />
                            </div>

                            <div>
                                <label class="text-sm font-medium">Subtotal</label>
                                <Input type="text" :value="formatRupiah(item.subtotal)" readonly />
                            </div>

                            <div class="text-right">
                                <Button variant="destructive" size="sm" @click="removeItem(index)">Hapus</Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Pembayaran</CardTitle>
                    </CardHeader>

                    <CardContent class="space-y-6">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div class="flex flex-col gap-6">

                                <div class="flex justify-between items-center">
                                    <span>Total Harga</span>
                                    <span class="font-semibold text-xl">{{ formatRupiah(totalPrice) }}</span>
                                </div>

                                <div>
                                    <label class="text-sm font-medium">Jenis Pembayaran</label>
                                    <Select v-model="paymentMethodId">
                                        <SelectTrigger><SelectValue placeholder="Pilih Metode" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">{{ pm.name }}</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label class="text-sm font-medium">Bayar</label>
                                    <Input type="number" min="0" v-model.number="paidAmount" />
                                </div>

                                <div>
                                    <label class="text-sm font-medium">Keterangan</label>
                                    <textarea
                                        v-model="note"
                                        class="w-full border rounded-md p-2 text-sm"
                                        rows="3"
                                    ></textarea>
                                </div>

                            </div>

                            <div class="flex flex-col gap-6">

                                <div>
                                    <label class="text-sm font-medium">Pelanggan</label>
                                    <Select v-model="customerId">
                                        <SelectTrigger><SelectValue placeholder="Pilih Pelanggan" /></SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                <SelectItem v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</SelectItem>
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div>
                                    <label class="text-sm font-medium">Total Bayar</label>
                                    <Input :value="paidAmount" readonly class="bg-gray-100" />
                                </div>

                                <div>
                                    <label class="text-sm font-medium">Kembalian</label>
                                    <Input :value="paidAmount - totalPrice < 0 ? 0 : paidAmount - totalPrice" readonly class="bg-gray-100" />
                                </div>

                            </div>

                        </div>

                        <div class="pt-4 text-right">
                            <Button @click="submitSale" class="px-6">Simpan Transaksi</Button>
                        </div>

                    </CardContent>
                </Card>

            </div>
        </div>
    </AppLayout>
</template>
