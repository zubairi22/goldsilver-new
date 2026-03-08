<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useBarcodeScanner } from '@/composables/useBarcodeScanner';
import Multiselect from '@vueform/multiselect';
import VueDatePicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { toast } from 'vue-sonner';

const { items, disabled, submitting } = defineProps<{
    items: any;
    disabled?: boolean;
    submitting?: boolean;
}>();

const emit = defineEmits(['submit']);
const form = defineModel<any>('form');

const addItem = (itemId: number) => {
    if (disabled) return;
    if (form.value.items.includes(itemId)) return;
    form.value.items.push(itemId);
};

const removeItem = (idx: number) => {
    if (disabled) return;
    form.value.items.splice(idx, 1);
};

useBarcodeScanner((code: string) => {
    if (disabled) return;

    const item = items.find((i: any) => i.code === code);
    if (item) {
        addItem(item.id);
        toast.success(`Berhasil scan: ${item.name}`);
    } else {
        toast.error(`Item dengan kode ${code} tidak ditemukan dakam daftar opname ini`);
    }
});
</script>

<template>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div>
            <Label class="text-sm font-medium">Tanggal Opname</Label>

            <VueDatePicker
                v-model="form.opname_at"
                :enable-time-picker="false"
                model-type="yyyy-MM-dd"
                :disabled="disabled"
                class="w-full"
                auto-apply
            />

            <InputError :message="form.errors.opname_at" />
        </div>

        <div>
            <Label class="text-sm font-medium">Catatan</Label>

            <textarea v-model="form.notes" :disabled="disabled" class="w-full rounded-md border px-3 py-2 text-sm" rows="3" />
        </div>
    </div>

    <div class="mt-4">
        <hr class="mb-3" />

        <div class="mb-2 flex items-center justify-between">
            <h3 class="font-semibold">Item Discan ({{ form.items.length }})</h3>
        </div>

        <div class="mb-3">
            <Multiselect :options="items" label="name" value-prop="id" searchable append-to-body :disabled="disabled" @select="addItem" />
        </div>

        <div class="overflow-x-auto">
            <Table class="w-full">
                <TableHeader>
                    <TableRow>
                        <TableHead>Kode</TableHead>
                        <TableHead>Nama</TableHead>
                        <TableHead>Berat</TableHead>
                        <TableHead class="w-8" />
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="(id, idx) in form.items" :key="id">
                        <TableCell>
                            {{ items.find((i: any) => i.id === id)?.code }}
                        </TableCell>

                        <TableCell>
                            {{ items.find((i: any) => i.id === id)?.name }}
                        </TableCell>

                        <TableCell>
                            {{ items.find((i: any) => i.id === id)?.weight }}
                        </TableCell>

                        <TableCell>
                            <Button size="icon" variant="destructive" :disabled="disabled" @click="removeItem(idx)"> ✕ </Button>
                        </TableCell>
                    </TableRow>

                    <TableRow v-if="!form.items.length">
                        <TableCell colspan="4"> Belum ada item discan </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <InputError class="mt-2" :message="form.errors.items" />
    </div>


</template>

<style src="@vueform/multiselect/themes/default.css" />
