<script lang="ts" setup>
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import {
    AlertDialog,
    AlertDialogTrigger,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogCancel,
    AlertDialogAction,
} from '@/components/ui/alert-dialog';
import { useFormat } from '@/composables/useFormat';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import DeleteButton from '@/components/DeleteButton.vue';

defineProps(['items', 'totalPrice']);
defineEmits(['edit-item', 'remove-item', 'clear-cart', 'save-draft', 'pay']);

const { formatRupiah } = useFormat();
</script>

<template>
    <Card class="flex h-[39rem] min-h-[28rem] flex-col">
        <CardHeader>
            <CardTitle class="pb-0">Keranjang</CardTitle>
        </CardHeader>
        <CardContent class="flex-1">
            <div class="max-h-[26rem] overflow-y-auto" v-if="items.length">
                <Table class="w-full text-sm">
                    <TableHeader>
                        <TableRow>
                            <TableHead class="h-8 w-12" />
                            <TableHead class="h-8 text-start">Produk</TableHead>
                            <TableHead class="h-8 text-center">Subtotal</TableHead>
                            <TableHead class="h-8 w-8" />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="(item, index) in items" :key="index" @click="$emit('edit-item', item, index)">
                            <TableCell class="px-0 text-center">{{ item.quantity }}</TableCell>
                            <TableCell>
                                <div class="flex flex-col leading-tight break-words whitespace-normal">
                                    <span class="font-medium">{{ item.name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ item.unit_name }}</span>
                                </div>
                            </TableCell>
                            <TableCell class="px-0 text-center">{{ formatRupiah(item.quantity * item.selling_price) }}</TableCell>
                            <TableCell class="px-2" @click.stop>
                                <AlertDialog>
                                    <AlertDialogTrigger>
                                        <DeleteButton size="sm">Hapus</DeleteButton>
                                    </AlertDialogTrigger>
                                    <AlertDialogContent>
                                        <AlertDialogHeader>
                                            <AlertDialogTitle>Hapus {{ item.name }}?</AlertDialogTitle>
                                            <AlertDialogDescription>Item akan dihapus dari keranjang</AlertDialogDescription>
                                        </AlertDialogHeader>
                                        <AlertDialogFooter>
                                            <AlertDialogCancel>Batal</AlertDialogCancel>
                                            <AlertDialogAction variant="destructive" @click="$emit('remove-item', index)">Hapus</AlertDialogAction>
                                        </AlertDialogFooter>
                                    </AlertDialogContent>
                                </AlertDialog>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
            <div v-else class="text-gray-500">Belum ada item.</div>
        </CardContent>
        <div class="-mb-6 flex flex-col gap-2 border-t p-4">
            <div class="flex items-center justify-between gap-2">
                <AlertDialog>
                    <AlertDialogTrigger>
                        <DeleteButton size="sm"/>
                    </AlertDialogTrigger>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Hapus isi keranjang?</AlertDialogTitle>
                            <AlertDialogDescription>
                                Keranjang akan dikosongkan
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Batal</AlertDialogCancel>
                            <AlertDialogAction variant="destructive" @click="$emit('clear-cart')">
                                Hapus
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
                <Button class="flex-1" variant="outline" size="sm" @click="$emit('save-draft')"> Simpan Order </Button>
            </div>
            <Button class="flex h-12 w-full items-center justify-between text-lg" :disabled="!items.length" @click="$emit('pay')">
                <span>Bayar</span>
                <span>{{ formatRupiah(totalPrice) }}</span>
            </Button>
        </div>
    </Card>
</template>
