<script lang="ts" setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import Multiselect from '@vueform/multiselect';
import { Separator } from '@/components/ui/separator';

const form = defineModel<any>('form');
defineProps(['categories', 'units'])

console.log(form.value.units);

const addUnit = () => {
    form.value.units.push({
        id: null,
        pivot: {
            sku: '',
            price: '0',
            conversion: 1,
        }
    });
};

const removeUnit = (index: number) => {
    form.value.units.splice(index, 1);
};
</script>

<template>
    <form @submit.prevent class="p-1">
        <div>
            <Label for="name">Nama Produk</Label>
            <Input id="name" type="text" v-model="form.name" />
            <InputError class="mt-1" :message="form.errors.name" />
        </div>

        <div class="mt-4">
            <Label for="stock">Stok</Label>
            <Input id="stock" type="number" min="0" v-model="form.stock" />
            <InputError class="mt-1" :message="form.errors.stock" />
        </div>

        <div class="mt-4">
            <Label for="category">Kategori</Label>
            <Multiselect v-model="form.category_id" :options="categories" searchable/>
            <InputError class="mt-2" :message="form.errors.category_id"/>
        </div>

        <separator class="my-6"/>

        <div class="mt-4">
            <div v-for="(unit, index) in form.units" :key="index" class="mb-4 grid grid-cols-2 gap-x-2">
                <div>
                    <Label for="unit">Satuan</Label>
                    <Multiselect v-model="unit.id" :options="units" searchable/>
                    <InputError class="mt-1" :message="form.errors.unit" />
                </div>

                <div>
                    <Label for="sku">SKU</Label>
                    <Input id="sku" v-model="unit.pivot.sku" type="text" class="h-10.5" placeholder="Contoh: SK001" />
                    <InputError class="mt-1" :message="form.errors.sku" />
                </div>

                <div class="mt-4">
                    <Label for="purchase_price">Harga Beli</Label>
                    <Input v-model="unit.pivot.purchase_price" placeholder="Contoh: 1000" type="number" min="0" required />
                    <InputError class="mt-1" :message="form.errors.purchase_price" />
                </div>

                <div class="mt-4">
                    <Label for="selling_price">Harga Jual</Label>
                    <Input v-model="unit.pivot.selling_price" placeholder="Contoh: 1000" type="number" min="0" required />
                    <InputError class="mt-1" :message="form.errors.selling_price" />
                </div>

                <div class="mt-4">
                    <Label for="conversion">Konversi</Label>
                    <Input v-model="unit.pivot.conversion" placeholder="Konversi (misalnya, 10 pcs = 1 set)" type="number" min="1" />
                    <InputError class="mt-2" :message="form.errors.conversion_id"/>
                </div>
                <div class="col-span-2 mt-4 text-right">
                    <Button @click="removeUnit(index)" variant="destructive">Hapus</Button>
                </div>
                <div class="col-span-2 mt-4 text-right">
                    <Button @click="addUnit" variant="secondary">Tambah Satuan</Button>
                </div>
            </div>
        </div>
    </form>
</template>

<style src="@vueform/multiselect/themes/default.css"/>
