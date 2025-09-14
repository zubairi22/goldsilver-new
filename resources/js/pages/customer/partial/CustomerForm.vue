<script lang="ts" setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import CurrencyInput from '@/components/CurrencyInput.vue';
import { usePage } from '@inertiajs/vue3';

const form = defineModel<any>('form');
</script>

<template>
    <div>
        <form @submit.prevent>
            <div>
                <Label for="name">Nama</Label>
                <Input id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <Label for="email">Email</Label>
                <Input id="email" type="email" class="mt-1 block w-full" v-model="form.email" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <Label for="phone">Telepon</Label>
                <Input id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" />
                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <div class="mt-4">
                <Label for="address">Alamat</Label>
                <Input id="address" type="text" class="mt-1 block w-full" v-model="form.address" />
                <InputError class="mt-2" :message="form.errors.address" />
            </div>

            <div v-if="usePage().props.auth.can.includes('delete customer')" class="mt-4">
                <Label for="debt_limit">Limit Utang</Label>
                <CurrencyInput v-model="form.debt_limit" />
                <InputError class="mt-2" :message="form.errors.debt_limit" />
            </div>
        </form>
    </div>
</template>
