import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    can: string[]
    isAdmin: boolean
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    url: string;
    icon: string;
    isActive?: boolean;
    children?: {
        title: string
        url: string
        icon: string
    }[]
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    auth: Auth;
    ziggy: Config & { location: string };
    flash: Flash;
    sidebarOpen: boolean;
    sideBarMenus: any;
};

export type Flash = {
    status: string | null;
    message: string | null;
    sale: any;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    roles: string[];
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
