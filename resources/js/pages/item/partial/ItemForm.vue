<script lang="ts" setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Multiselect from '@vueform/multiselect';
import CurrencyInput from '@/components/CurrencyInput.vue';
import { Textarea } from '@/components/ui/textarea';

defineProps(['itemTypes']);

const form = defineModel<any>('form');
</script>

<template>
    <div class="p-1 space-y-5">

        <!-- CODE -->
        <div>
            <Label for="code">Kode Item</Label>
            <Input id="code" type="text" v-model="form.code" />
            <InputError class="mt-1" :message="form.errors.code" />
        </div>

        <!-- NAME -->
        <div>
            <Label for="name">Nama Item</Label>
            <Input id="name" type="text" v-model="form.name" />
            <InputError class="mt-1" :message="form.errors.name" />
        </div>

        <!-- ITEM TYPE -->
        <div>
            <Label for="item_type_id">Tipe Item</Label>
            <Multiselect
                v-model="form.item_type_id"
                :options="itemTypes"
                searchable
                placeholder="Pilih Tipe Item"
            />
            <InputError class="mt-2" :message="form.errors.item_type_id" />
        </div>

        <!-- WEIGHT -->
        <div>
            <Label for="weight">Berat (gram)</Label>
            <Input id="weight" type="number" min="0" step="0.001" v-model="form.weight" />
            <InputError class="mt-1" :message="form.errors.weight" />
        </div>

        <!-- PRICE BUY -->
        <div>
            <Label for="price_buy">Harga Beli</Label>
            <CurrencyInput v-model="form.price_buy" />
            <InputError class="mt-1" :message="form.errors.price_buy" />
        </div>

        <!-- PRICE SELL -->
        <div>
            <Label for="price_sell">Harga Jual</Label>
            <CurrencyInput v-model="form.price_sell" />
            <InputError class="mt-1" :message="form.errors.price_sell" />
        </div>

        <!-- STATUS -->
        <div>
            <Label for="status">Status</Label>
            <Multiselect
                v-model="form.status"
                :options="{ ready: 'Ready', sold: 'Terjual', pending: 'Pending' }"
                placeholder="Pilih Status"
            />
            <InputError class="mt-1" :message="form.errors.status" />
        </div>

        <!-- QR CODE -->
        <div>
            <Label for="qr_code">QR Code</Label>
            <Input id="qr_code" type="text" v-model="form.qr_code" />
            <InputError class="mt-1" :message="form.errors.qr_code" />
        </div>

        <!-- DESCRIPTION -->
        <div>
            <Label for="description">Deskripsi</Label>
            <Textarea id="description" v-model="form.description" rows="3" />
            <InputError class="mt-1" :message="form.errors.description" />
        </div>

    </div>
</template>

<style src="@vueform/multiselect/themes/default.css" />
