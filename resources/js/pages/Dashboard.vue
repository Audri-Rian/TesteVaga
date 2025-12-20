<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { onMounted, computed } from 'vue';
import { useProjectStore } from '@/stores/projectStore';
import { useTaskStore } from '@/stores/taskStore';
import { TaskStatus } from '@/types/task';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';
import {
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Filter,
    LayoutGrid,
    ListChecks,
    MoreHorizontal,
    Plus,
    TrendingUp,
} from 'lucide-vue-next';

const projectStore = useProjectStore();
const taskStore = useTaskStore();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

onMounted(async () => {
    try {
        await Promise.all([
            projectStore.fetchProjects(),
            taskStore.fetchTasks(),
        ]);
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    }
});

const stats = computed(() => ({
    totalProjects: projectStore.projects.length,
    totalTasks: taskStore.tasks.length,
    pendingTasks: taskStore.pendingTasks.length,
    inProgressTasks: taskStore.inProgressTasks.length,
    completedTasks: taskStore.completedTasks.length,
}));

const completionRate = computed(() => {
    const total = stats.value.totalTasks;
    if (!total) return 0;
    return Math.round((stats.value.completedTasks / total) * 100);
});

const activeProjectsCount = computed(() => {
    const activeProjectIds = new Set(
        taskStore.tasks
            .filter(task => task.status !== TaskStatus.Done)
            .map(task => task.project_id),
    );

    return projectStore.projects.filter(project =>
        activeProjectIds.has(project.id),
    ).length;
});

const projectActivityRate = computed(() => {
    if (!stats.value.totalProjects) return 0;
    return Math.min(
        100,
        Math.round((activeProjectsCount.value / stats.value.totalProjects) * 100),
    );
});

const displayedProjects = computed(() =>
    projectStore.projects.slice(0, 5),
);

const projectSnapshots = computed(() =>
    displayedProjects.value.map(project => {
        const relatedTasks = taskStore.tasks.filter(
            task => task.project_id === project.id,
        );

        const totalTasks = relatedTasks.length || project.tasks_count || 0;
        const completed = relatedTasks.filter(
            task => task.status === TaskStatus.Done,
        ).length;
        const inProgress = relatedTasks.filter(
            task => task.status === TaskStatus.InProgress,
        ).length;
        const pending = Math.max(totalTasks - completed - inProgress, 0);
        const progress = totalTasks
            ? Math.round((completed / totalTasks) * 100)
            : 0;

        const members = project.members ?? [];
        const extraMembers =
            project.members_count && project.members_count > members.length
                ? project.members_count - members.length
                : 0;

        return {
            project,
            totalTasks,
            completed,
            inProgress,
            pending,
            progress,
            members,
            extraMembers,
        };
    }),
);

const showingLabel = computed(() => {
    const total = stats.value.totalProjects;
    const showing = projectSnapshots.value.length;
    if (!total) return 'No projects yet';
    return `Showing ${showing} of ${total} projects`;
});

function formatDate(date: string | undefined) {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });
}

function getInitials(name?: string) {
    if (!name) return '?';
    return name
        .split(' ')
        .map(part => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-[#f5f7fb]">
            <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 lg:px-0">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-3xl font-bold text-slate-900">Dashboard</h1>
                        <p class="text-base text-slate-500">
                            Welcome back! Here's an overview of your active projects.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <Button
                            variant="outline"
                            class="h-10 rounded-xl border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            <Filter class="mr-2 size-4" />
                            Filter
                        </Button>
                        <Button
                            class="h-10 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-md hover:bg-blue-600/90"
                        >
                            <Plus class="mr-2 size-4" />
                            New Project
                        </Button>
                    </div>
                </div>

                <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                        <CardContent class="space-y-5 pb-6 pt-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                        <LayoutGrid class="size-6" />
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="text-sm font-semibold text-slate-900">Total Projects</p>
                                        <p class="text-xs text-slate-500">Active projects</p>
                                    </div>
                                </div>
                                <Badge class="border-blue-100 bg-blue-50 text-blue-600">
                                    <TrendingUp class="size-3" />
                                    {{ projectActivityRate }}% active
                                </Badge>
                            </div>
                            <div class="space-y-2">
                                <div class="text-4xl font-semibold text-slate-900">
                                    {{ stats.totalProjects }}
                                </div>
                                <p class="text-sm font-medium text-blue-600">
                                    {{ activeProjectsCount }} currently active
                                </p>
                                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-blue-500 transition-all"
                                        :style="{ width: `${projectActivityRate}%` }"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                        <CardContent class="space-y-5 pb-6 pt-4">
                            <div class="flex items-start justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                                        <ListChecks class="size-6" />
                                    </div>
                                    <div class="space-y-0.5">
                                        <p class="text-sm font-semibold text-slate-900">Total Tasks</p>
                                        <p class="text-xs text-slate-500">Across all projects</p>
                                    </div>
                                </div>
                                <Badge class="border-emerald-100 bg-emerald-50 text-emerald-600">
                                    {{ completionRate }}% done
                                </Badge>
                            </div>
                            <div class="space-y-2">
                                <div class="text-4xl font-semibold text-slate-900">
                                    {{ stats.totalTasks }}
                                </div>
                                <p class="text-sm font-semibold text-emerald-600">
                                    {{ completionRate }}% completion rate
                                </p>
                                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                    <div
                                        class="h-full rounded-full bg-indigo-500 transition-all"
                                        :style="{ width: `${completionRate}%` }"
                                    />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                        <CardContent class="space-y-5 pb-6 pt-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-500">
                                    <CheckCircle2 class="size-6" />
                                </div>
                                <div class="space-y-0.5">
                                    <p class="text-sm font-semibold text-slate-900">Tasks Overview</p>
                                    <p class="text-xs text-slate-500">Status breakdown</p>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#a6c9ff]" />
                                        <span class="text-slate-600">Pending</span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">
                                        {{ stats.pendingTasks }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#3676f2]" />
                                        <span class="text-slate-600">In Progress</span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">
                                        {{ stats.inProgressTasks }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 rounded-full bg-[#25c290]" />
                                        <span class="text-slate-600">Completed</span>
                                    </div>
                                    <span class="text-sm font-semibold text-slate-900">
                                        {{ stats.completedTasks }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card class="flex-1 rounded-2xl border-slate-200 bg-white shadow-sm">
                    <CardHeader class="flex flex-row items-center justify-between gap-2">
                        <div>
                            <CardTitle>Recent Projects</CardTitle>
                            <CardDescription>Keep track of progress at a glance</CardDescription>
                        </div>
                        <a
                            href="/projects"
                            class="text-sm font-semibold text-blue-600 hover:text-blue-700"
                        >
                            View All ->
                        </a>
                    </CardHeader>
                    <CardContent class="space-y-4 pb-6">
                        <div
                            v-if="projectStore.loading || taskStore.loading"
                            class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-slate-500"
                        >
                            Loading dashboard data...
                        </div>
                        <div
                            v-else-if="projectStore.projects.length === 0"
                            class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center text-slate-500"
                        >
                            No projects yet. Create one to get started!
                        </div>
                        <div v-else class="overflow-hidden rounded-xl border border-slate-200">
                            <div class="grid grid-cols-[2fr_1.4fr_1fr_1fr_auto] items-center gap-4 bg-slate-50 px-6 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <span>Project Name</span>
                                <span>Progress</span>
                                <span>Members</span>
                                <span>Last Update</span>
                                <span class="text-right">Action</span>
                            </div>
                            <div
                                v-for="row in projectSnapshots"
                                :key="row.project.id"
                                class="grid grid-cols-[2fr_1.4fr_1fr_1fr_auto] items-center gap-4 border-t border-slate-200 bg-white px-6 py-4"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-600">
                                        <LayoutGrid class="size-6" />
                                    </div>
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">
                                            {{ row.project.name }}
                                        </h3>
                                        <p class="text-xs text-slate-500">
                                            <span v-if="row.totalTasks > 0">
                                                {{ row.completed }} of {{ row.totalTasks }} tasks completed
                                            </span>
                                            <span v-else>
                                                No tasks yet
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <div class="flex items-center justify-between text-xs text-slate-600">
                                        <span v-if="row.totalTasks > 0">
                                            {{ row.completed }}/{{ row.totalTasks }} Tasks
                                        </span>
                                        <span v-else>No tasks</span>
                                        <span class="font-semibold text-emerald-600">
                                            {{ row.progress }}%
                                        </span>
                                    </div>
                                    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100">
                                        <div
                                            class="h-full rounded-full transition-all"
                                            :class="row.progress >= 60 ? 'bg-emerald-500' : row.progress >= 30 ? 'bg-amber-400' : 'bg-blue-500'"
                                            :style="{ width: `${row.progress}%` }"
                                        />
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <template v-if="row.members.length">
                                        <div class="-space-x-2">
                                            <Avatar
                                                v-for="member in row.members.slice(0, 3)"
                                                :key="member.id"
                                                class="inline-flex border-2 border-white shadow-sm"
                                            >
                                                <AvatarFallback class="bg-slate-200 text-[11px] font-semibold text-slate-700">
                                                    {{ getInitials(member.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                        </div>
                                        <Badge
                                            v-if="row.extraMembers > 0"
                                            variant="secondary"
                                            class="border-slate-200 bg-slate-50 text-slate-700"
                                        >
                                            +{{ row.extraMembers }}
                                        </Badge>
                                    </template>
                                    <span
                                        v-else
                                        class="text-xs font-medium text-slate-500"
                                    >
                                        No members
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 text-sm text-slate-600">
                                    <CalendarDays class="size-4 text-slate-400" />
                                    <span>{{ formatDate(row.project.updated_at) }}</span>
                                </div>

                                <div class="flex justify-end">
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-9 w-9 rounded-full text-slate-500 hover:bg-slate-100"
                                    >
                                        <MoreHorizontal class="size-5" />
                                    </Button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between border-t border-slate-200 bg-slate-50 px-6 py-3 text-sm text-slate-600">
                                <span>{{ showingLabel }}</span>
                                <div class="flex items-center gap-2">
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        class="h-9 w-9 rounded-full border-slate-200 bg-white text-slate-600 hover:bg-slate-100"
                                    >
                                        <ChevronLeft class="size-4" />
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="icon"
                                        class="h-9 w-9 rounded-full border-slate-200 bg-white text-slate-600 hover:bg-slate-100"
                                    >
                                        <ChevronRight class="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
