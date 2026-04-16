<script lang="ts" setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Multiselect from '@vueform/multiselect';

const form = defineModel<any>('form');

defineProps<{
    roles: any,
}>();
</script>

<template>
    <div>
        <form @submit.prevent>
            <div>
                <Label for="name">Nama</Label>
                <Input id="name" type="text" class="mt-1 block w-full" v-model="form.name" required/>
                <InputError class="mt-2" :message="form.errors.name"/>
            </div>
            <div class="mt-4">
                <Label for="email">Email</Label>
                <Input id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                <InputError class="mt-2" :message="form.errors.email"/>
            </div>
            <div class="mt-4">
                <Label name="roles">Role</Label>
               <Multiselect
                    v-model="form.roles"
                    mode="tags"
                    :options="roles"
                    value-prop="id"
                    label="name"
                    placeholder="Pilih Role"
                    class="mt-1"
                />
                <InputError class="mt-2" :message="form.errors.roles" />
            </div>

            <div class="mt-4">
                <Label for="password">Password <span v-if="form.id" class="text-xs text-muted-foreground">(Biarkan kosong jika tidak ingin mengubah)</span></Label>
                <Input id="password" type="password" class="mt-1 block w-full" v-model="form.password" autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>
        </form>
    </div>
</template>

<style src="@vueform/multiselect/themes/default.css" />
