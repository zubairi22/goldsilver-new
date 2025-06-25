<script lang="ts" setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Multiselect from '@vueform/multiselect'

const form = defineModel<any>('form');

defineProps<{
    parents: any,
}>();

</script>

<template>
    <div>
        <form @submit.prevent>
            <div>
                <Label for="title">Nama Menu</Label>
                <Input id="title" type="text" class="mt-1 block w-full" v-model="form.title" required/>
                <InputError class="mt-2" :message="form.errors.title"/>
            </div>
            <div class="mt-4">
                <Label for="url">Route Name</Label>
                <Input id="url" type="text" class="mt-1 block w-full" v-model="form.url" required/>
                <InputError class="mt-2" :message="form.errors.url"/>
            </div>
            <div class="mt-4">
                <Label for="icon">Icon</Label>
                <Input id="icon" type="text" class="mt-1 block w-full" v-model="form.icon" required/>
                <InputError class="mt-2" :message="form.errors.icon"/>
            </div>
            <div class="mt-4">
                <Label for="sort">Urutan</Label>
                <Input id="sort" type="text" class="mt-1 block w-full" v-model="form.sort" required/>
                <InputError class="mt-2" :message="form.errors.sort"/>
            </div>
            <div class="mt-4">
                <Label for="parent">Parent Menu</Label>
                <Multiselect v-model="form.parent_id" :options="parents"/>
                <InputError class="mt-2" :message="form.errors.parent_id"/>
            </div>
            <div class="mt-4">
                <Label for="permissions">Izin</Label>
                <Multiselect v-model="form.permissions" :options="form.permissions" mode="tags" searchable create-option :add-option-on="['enter']"/>
                <InputError class="mt-2" :message="form.errors.parent_id"/>
                <small>Berikan dalam format izin + title eg.<strong>view dashboard</strong></small>
            </div>
        </form>
    </div>
</template>

<style src="@vueform/multiselect/themes/default.css"/>
