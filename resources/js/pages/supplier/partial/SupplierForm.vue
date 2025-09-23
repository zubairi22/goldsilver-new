<script lang="ts" setup>
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import InputError from '@/components/InputError.vue'

const form = defineModel<any>('form')
</script>

<template>
    <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
        <div class="md:col-span-1">
            <label class="text-sm font-medium">Nama</label>
            <Input v-model="form.name" class="h-10.5" placeholder="Nama supplier"/>
            <InputError :message="form.errors?.name" />
        </div>

        <div class="md:col-span-1">
            <label class="text-sm font-medium">Telepon</label>
            <Input v-model="form.phone" class="h-10.5" placeholder="08xxxxxxxxxx"/>
            <InputError :message="form.errors?.phone" />
        </div>

        <div class="md:col-span-2">
            <label class="text-sm font-medium">Alamat</label>
            <Textarea v-model="form.address" rows="3" placeholder="Jl. Contoh No. 123" />
            <InputError :message="form.errors?.address" />
        </div>
    </div>
</template>
