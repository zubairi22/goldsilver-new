<script lang="ts" setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Multiselect from '@vueform/multiselect';
import CurrencyInput from '@/components/CurrencyInput.vue';

defineProps(['itemTypes']);

const form = defineModel<any>('form');

const handleImageChange = (e: Event) => {
    const target = e.target as HTMLInputElement | null;
    if (!target?.files?.length) return;
    form.value.image = target.files[0];
};
</script>

<template>
    <div class="p-1 space-y-5">

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
            <Input id="weight" type="number" v-model="form.weight" />
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
            />
            <InputError class="mt-1" :message="form.errors.status" />
        </div>

        <div>
            <Label for="image">Foto Item</Label>

            <Input
                id="image"
                type="file"
                accept="image/*"
                capture="environment"
                @change="handleImageChange"
            />

            <InputError class="mt-1" :message="form.errors.image" />
        </div>

    </div>
</template>

<style src="@vueform/multiselect/themes/default.css" />
