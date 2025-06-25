<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import Multiselect from "@vueform/multiselect";
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

const form = defineModel<any>('form');

defineProps<{
    permissions: any;
}>();
</script>

<template>
    <div>
        <form @submit.prevent>
            <div>
                <Label for="name">Nama Peran</Label>
                <Input
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>
            <div class="mt-2">
                <Label for="guard_name">Guard Name</Label>
                <Select v-model="form.guard_name">
                    <SelectTrigger id="guard_name" class="w-full">
                        <SelectValue placeholder="Pilih Guard Name" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem value="web">
                                web
                            </SelectItem>
                            <SelectItem value="api">
                                api
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <InputError class="mt-2" :message="form.errors.guard_name" />
            </div>
            <div class="mt-2">
                <Label for="permissions">Izin</Label>
                <Multiselect
                    v-model="form.permissions"
                    :options="permissions"
                    mode="tags"
                    searchable
                />
                <InputError class="mt-2" :message="form.errors.permissions" />
            </div>
        </form>
    </div>
</template>

<style src="@vueform/multiselect/themes/default.css" />
