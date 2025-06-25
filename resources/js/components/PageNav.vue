<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import {
    Pagination,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
} from '@/components/ui/pagination'

defineProps<{
    data: {
        current_page: string
        first_page_url: string,
        last_page_url: string,
        next_page_url: string,
        prev_page_url: string
        per_page: number,
        total: number,
        links: {
            active: boolean,
            label: string,
            url: string
        }[]
    },
}>()
</script>

<template>
    <Pagination :items-per-page="data.per_page" :total="data.total">
        <PaginationList class="flex items-center gap-1 float-right pt-4">
            <Link v-if="data.first_page_url && data.total > data.per_page" :href="data.first_page_url">
                <PaginationFirst />
            </Link>
            <PaginationFirst v-else />
            <Link v-if="data.prev_page_url" :href="data.prev_page_url">
                <PaginationPrev />
            </Link>

            <template v-for="(link, index) in data.links" :key="index">
                <PaginationListItem
                    v-if="link.label.match(/^[0-9]+$/)"
                    :value="parseInt(link.label)"
                    as-child
                >
                    <Link v-if="link.label != data.current_page" :href="link.url">
                        <Button class="w-10 h-10 p-0" :variant="link.active ? 'default' : 'outline'">
                            {{ link.label }}
                        </Button>
                    </Link>
                    <Button v-else disabled="disabled" class="w-10 h-10 p-0" :variant="link.active ? 'default' : 'outline'">
                        {{ link.label }}
                    </Button>
                </PaginationListItem>
            </template>

            <Link v-if="data.next_page_url" :href="data.next_page_url">
                <PaginationNext />
            </Link>
            <Link v-if="data.last_page_url && data.total > data.per_page" :href="data.last_page_url">
                <PaginationLast />
            </Link>
            <PaginationLast v-else />
        </PaginationList>
    </Pagination>
</template>
