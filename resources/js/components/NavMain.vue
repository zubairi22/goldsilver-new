<script setup lang="ts">
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible'
import {
    SidebarGroup,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub, SidebarMenuSubButton, SidebarMenuSubItem, useSidebar
} from '@/components/ui/sidebar';
import { icons } from 'lucide-vue-next'
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

defineProps<{
    items: NavItem[];
}>();

const { toggleSidebar, state } = useSidebar()
</script>

<template>
    <SidebarGroup class="mt-2">
        <template v-for="item in items" :key="item.title">
            <SidebarMenu v-if="item.children?.length === 0">
                <SidebarMenuItem>
                    <SidebarMenuButton as-child :is-active="route().current(item.url)">
                        <Link :href="route(item.url)">
                            <icon :name="item.icon"/>
                            <span>{{ item.title }}</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarMenu v-if="item.children?.length !== 0">
                <Collapsible :key="item.title" as-child :default-open="route().current(`${item.url}.*`)" class="group/collapsible">
                    <SidebarMenuItem>
                        <CollapsibleTrigger as-child>
                            <SidebarMenuButton :tooltip="item.title">
                                <icon
                                    :name="item.icon"
                                    v-if="item.icon"
                                    @click="() => state === 'collapsed' && toggleSidebar()"
                                />
                                <span>{{ item.title }}</span>
                                <component
                                    :is="icons['ChevronRight']"
                                    class="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90"
                                />
                            </SidebarMenuButton>
                        </CollapsibleTrigger>
                        <CollapsibleContent>
                            <SidebarMenuSub>
                                <SidebarMenuSubItem v-for="subItem in item.children" :key="subItem.title">
                                    <SidebarMenuSubButton as-child :is-active="route().current(subItem.url)">
                                        <Link :href="route(subItem.url)">
                                            <icon :name="subItem.icon" v-if="subItem.icon" />
                                            <span>{{ subItem.title }}</span>
                                        </Link>
                                    </SidebarMenuSubButton>
                                </SidebarMenuSubItem>
                            </SidebarMenuSub>
                        </CollapsibleContent>
                    </SidebarMenuItem>
                </Collapsible>
            </SidebarMenu>
        </template>
    </SidebarGroup>
</template>
