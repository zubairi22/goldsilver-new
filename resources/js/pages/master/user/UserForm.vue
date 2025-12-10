<script lang="ts" setup>
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'

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
                <Label for="role">Role</Label>
                <Select v-model="form.role">
                    <SelectTrigger id="role" class="w-full">
                        <SelectValue placeholder="Pilih Role" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup>
                            <SelectItem
                                v-for="role in roles"
                                :key="role.id"
                                :value="role.id"
                            >
                                {{ role.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <InputError class="mt-2" :message="form.errors.role" />
            </div>
        </form>
    </div>
</template>
