<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Card, CardContent } from '@/components/ui/card'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter } from '@/components/ui/dialog'
import Heading from '@/components/Heading.vue'
import { useFormat } from '@/composables/useFormat'
import type { BreadcrumbItem } from '@/types'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { ref } from 'vue'
import { LoaderCircle } from 'lucide-vue-next'
import InputError from '@/components/InputError.vue'
import CurrencyInput from '@/components/CurrencyInput.vue'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Piutang', href: '#' },
]

const props = defineProps({
    sales: Object,
    paymentMethods: Array,
})

const { formatRupiah, formatDate } = useFormat()

/* -------------------------------
    SETTLEMENT
--------------------------------*/
const settlementModal = ref(false)
const selectedSale = ref<any>(null)

const settleForm = useForm({
    amount: 0,
    payment_method_id: '',
})

const openSettlement = (sale: any) => {
    selectedSale.value = sale
    settlementModal.value = true
}

const handleSettlement = () => {
    settleForm.post(route('transactions.debts.settle', selectedSale.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            settlementModal.value = false
            settleForm.reset()
        },
    })
}

/* -------------------------------
    DUE DATE
--------------------------------*/
const dueDateModal = ref(false)

const dueDateForm = useForm({
    due_date_days: 1,
})

const openDueDate = (sale: any) => {
    selectedSale.value = sale
    dueDateModal.value = true
}

const handleDueDate = () => {
    dueDateForm.post(route('transactions.debts.dueDate', selectedSale.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            dueDateModal.value = false
            dueDateForm.reset()
        },
    })
}

/* -------------------------------
    DETAIL PEMBAYARAN
--------------------------------*/
const detailModal = ref(false)

const openDetail = (sale: any) => {
    selectedSale.value = sale
    detailModal.value = true
}

</script>

<template>
    <Head title="Piutang" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="py-8">
            <Heading class="mx-4" title="Piutang" description="Daftar transaksi yang masih memiliki piutang" />

            <div class="max-w-8xl mx-auto">
                <Card class="py-4 md:mx-4">
                    <CardContent>
                        <div class="overflow-x-auto">
                            <Table class="w-full">
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Kode</TableHead>
                                        <TableHead>Pelanggan</TableHead>
                                        <TableHead>Kasir</TableHead>
                                        <TableHead>Tanggal</TableHead>
                                        <TableHead class="text-right">Total</TableHead>
                                        <TableHead class="text-right">Dibayar</TableHead>
                                        <TableHead class="text-right">Sisa</TableHead>
                                        <TableHead></TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="sale in sales.data" :key="sale.id">
                                        <TableCell>{{ sale.invoice_no }}</TableCell>
                                        <TableCell>{{ sale.customer?.name ?? '-' }}</TableCell>
                                        <TableCell>{{ sale.user?.name }}</TableCell>

                                        <TableCell>
                                            {{ formatDate(sale.created_at, 'dd MMM yyyy HH:mm') }}
                                        </TableCell>

                                        <TableCell class="text-right">
                                            {{ formatRupiah(sale.total_price) }}
                                        </TableCell>

                                        <TableCell class="text-right">
                                            {{ formatRupiah(sale.paid_amount) }}
                                        </TableCell>

                                        <TableCell class="text-right text-red-600 font-semibold">
                                            {{ formatRupiah(sale.remaining_amount) }}
                                        </TableCell>

                                        <TableCell>
                                            <div class="flex gap-2">
                                                <Button size="sm" @click="openDetail(sale)">Detail</Button>
                                                <Button size="sm" @click="openSettlement(sale)">Bayar</Button>
                                                <Button size="sm" variant="secondary" @click="openDueDate(sale)">Jatuh Tempo</Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>

                                    <TableRow v-if="sales.total === 0">
                                        <TableCell colspan="8" class="text-center py-6">Tidak ada piutang</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>

    <!-- ===========================
        MODAL PEMBAYARAN
    ==============================-->
    <Dialog :open="settlementModal" @update:open="v => (settlementModal = v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Pembayaran Piutang</DialogTitle>
            </DialogHeader>

            <div>
                <Label>Jumlah Pembayaran</Label>
                <CurrencyInput v-model="settleForm.amount" required />
                <InputError :message="settleForm.errors.amount" />
            </div>

            <div class="mt-3">
                <Label>Metode Pembayaran</Label>
                <select v-model="settleForm.payment_method_id" class="w-full border rounded p-2">
                    <option value="">Pilih...</option>
                    <option v-for="pm in paymentMethods" :key="pm.id" :value="pm.id">
                        {{ pm.name }}
                    </option>
                </select>
                <InputError :message="settleForm.errors.payment_method_id" />
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="settlementModal = false">Batal</Button>
                <Button :disabled="settleForm.processing" @click="handleSettlement">
                    <LoaderCircle v-if="settleForm.processing" class="h-4 w-4 animate-spin" />
                    Bayar
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ===========================
        DETAIL PEMBAYARAN
    ==============================-->
    <Dialog :open="detailModal" @update:open="v => (detailModal = v)">
        <DialogContent class="sm:max-w-3xl">
            <DialogHeader>
                <DialogTitle>Detail Pembayaran</DialogTitle>
            </DialogHeader>

            <div v-if="selectedSale">
                <p><strong>Kode:</strong> {{ selectedSale.invoice_no }}</p>
                <p><strong>Total:</strong> {{ formatRupiah(selectedSale.total_price) }}</p>
                <p><strong>Dibayar:</strong> {{ formatRupiah(selectedSale.paid_amount) }}</p>
                <p><strong>Sisa:</strong> {{ formatRupiah(selectedSale.remaining_amount) }}</p>

                <Table class="mt-4">
                    <TableHeader>
                        <TableRow>
                            <TableHead>Jumlah</TableHead>
                            <TableHead>Tanggal</TableHead>
                            <TableHead>Metode</TableHead>
                            <TableHead>Catatan</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="pay in selectedSale.payments" :key="pay.id">
                            <TableCell>{{ formatRupiah(pay.amount) }}</TableCell>
                            <TableCell>{{ formatDate(pay.created_at) }}</TableCell>
                            <TableCell>{{ pay.payment_method?.name }}</TableCell>
                            <TableCell>{{ pay.note }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <DialogFooter>
                <Button @click="detailModal = false">Tutup</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <!-- ===========================
        MODAL DUE DATE
    ==============================-->
    <Dialog :open="dueDateModal" @update:open="v => (dueDateModal = v)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Atur Jatuh Tempo</DialogTitle>
            </DialogHeader>

            <div>
                <Label>Tenor (hari)</Label>
                <Input type="number" v-model="dueDateForm.due_date_days" min="1" />
                <InputError :message="dueDateForm.errors.due_date_days" />
            </div>

            <DialogFooter>
                <Button variant="secondary" @click="dueDateModal = false">Batal</Button>
                <Button :disabled="dueDateForm.processing" @click="handleDueDate">
                    <LoaderCircle v-if="dueDateForm.processing" class="h-4 w-4 animate-spin" />
                    Simpan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
