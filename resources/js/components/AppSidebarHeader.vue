<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import Badge from '@/components/ui/badge/Badge.vue';
import type { BreadcrumbItemType } from '@/types';
import { Bell } from 'lucide-vue-next';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const notifications = [
    {
        id: 1,
        title: 'Task assignment',
        description: 'You were assigned to "Refine onboarding flow".',
        time: '2h ago',
    },
    {
        id: 2,
        title: 'New comment',
        description: 'Ana comentou na tarefa "Atualizar docs do projeto".',
        time: '4h ago',
    },
    {
        id: 3,
        title: 'Status change',
        description: '"Revisar release" foi movida para InProgress.',
        time: '1d ago',
    },
];
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-2">
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="relative h-9 w-9 rounded-full"
                    >
                        <Bell class="size-5" />
                        <span
                            class="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground"
                        >
                            {{ notifications.length }}
                        </span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-80">
                    <DropdownMenuLabel class="flex items-center justify-between">
                        <span>Notifications</span>
                        <Badge variant="secondary">Static</Badge>
                    </DropdownMenuLabel>
                    <DropdownMenuSeparator />
                    <div class="max-h-80 space-y-1 overflow-y-auto">
                        <DropdownMenuItem
                            v-for="note in notifications"
                            :key="note.id"
                            class="flex items-start gap-2"
                        >
                            <div
                                class="mt-1 rounded-full bg-muted p-2 text-muted-foreground"
                            >
                                <Bell class="size-4" />
                            </div>
                            <div class="space-y-1 text-left">
                                <p class="text-sm font-semibold">
                                    {{ note.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ note.description }}
                                </p>
                                <span class="text-[11px] text-muted-foreground">
                                    {{ note.time }}
                                </span>
                            </div>
                        </DropdownMenuItem>
                    </div>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
