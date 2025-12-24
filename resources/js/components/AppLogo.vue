<script setup lang="ts">
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { useInitials } from '@/composables/useInitials'
import { computed } from 'vue'
import {usePage} from "@inertiajs/vue3";

const { getInitials } = useInitials()

const appName = computed(() => usePage().props.name ?? '')
</script>

<template>
    <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
        <AvatarFallback class="rounded-lg font-bold text-black dark:text-white">
            {{ getInitials(appName) }}
        </AvatarFallback>
    </Avatar>

    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">
          {{ appName }}
        </span>
    </div>
</template>
