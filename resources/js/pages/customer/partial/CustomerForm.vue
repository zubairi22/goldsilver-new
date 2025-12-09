<script lang="ts" setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const form = defineModel<any>('form');
</script>

<template>
    <div>
        <form @submit.prevent>
            <!-- Name -->
            <div>
                <Label for="name">Nama</Label>
                <Input id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Phone -->
            <div class="mt-4">
                <Label for="phone">Telepon</Label>
                <Input id="phone" type="text" class="mt-1 block w-full" v-model="form.phone" />
                <InputError class="mt-2" :message="form.errors.phone" />
            </div>

            <!-- Address -->
            <div class="mt-4">
                <Label for="address">Alamat</Label>
                <Input id="address" type="text" class="mt-1 block w-full" v-model="form.address" />
                <InputError class="mt-2" :message="form.errors.address" />
            </div>
        </form>
    </div>
</template>
