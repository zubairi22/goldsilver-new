<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type AppPageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from './AppLogo.vue';

const page = usePage<AppPageProps>();
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="bg-primary rounded-t-lg">
            <SidebarMenu>
                <SidebarMenuItem class="text-primary-foreground" >
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="bg-primary text-primary-foreground">
            <NavMain :items="page.props.sideBarMenus" />
        </SidebarContent>

        <SidebarFooter class="bg-primary text-primary-foreground rounded-b-lg">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
