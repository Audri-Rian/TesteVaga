<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
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
    FolderKanban,
    MoreHorizontal,
    Plus,
    Search,
    Users,
} from 'lucide-vue-next';

const projectStore = useProjectStore();
const taskStore = useTaskStore();

const showCreateModal = ref(false);
const newProjectName = ref('');
const searchTerm = ref('');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Projects',
        href: '/projects',
    },
];

const palette = [
    'bg-blue-50 text-blue-600',
    'bg-purple-50 text-purple-600',
    'bg-amber-50 text-amber-600',
    'bg-emerald-50 text-emerald-600',
];

onMounted(async () => {
    try {
        await Promise.all([
            projectStore.fetchProjects(),
            taskStore.fetchTasks(),
        ]);
    } catch (error) {
        console.error('Failed to load projects page:', error);
    }
});

const filteredProjects = computed(() => {
    const term = searchTerm.value.trim().toLowerCase();
    if (!term) return projectStore.projects;
    return projectStore.projects.filter(project =>
        project.name.toLowerCase().includes(term),
    );
});

const projectCards = computed(() =>
    filteredProjects.value.map((project, index) => {
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

        const members = project.members ?? [];
        const extraMembers =
            project.members_count && project.members_count > members.length
                ? project.members_count - members.length
                : 0;

        const status = getStatus(totalTasks, completed, inProgress, pending);
        const colorClass = palette[index % palette.length];

        return {
            project,
            totalTasks,
            completed,
            inProgress,
            pending,
            members,
            extraMembers,
            status,
            colorClass,
        };
    }),
);

function getStatus(
    total: number,
    completed: number,
    inProgress: number,
    pending: number,
) {
    if (!total) {
        return {
            label: 'Planning',
            classes: 'border-amber-100 bg-amber-50 text-amber-700',
        };
    }

    if (completed >= total) {
        return {
            label: 'Completed',
            classes: 'border-emerald-100 bg-emerald-50 text-emerald-700',
        };
    }

    if (inProgress > 0) {
        return {
            label: 'In Progress',
            classes: 'border-blue-100 bg-blue-50 text-blue-700',
        };
    }

    return {
        label: 'Pending',
        classes: 'border-slate-200 bg-slate-100 text-slate-700',
    };
}

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

async function createProject() {
    if (!newProjectName.value.trim()) return;

    try {
        await projectStore.createProject({ name: newProjectName.value });
        newProjectName.value = '';
        showCreateModal.value = false;
    } catch (error) {
        console.error('Failed to create project:', error);
    }
}

function viewProject(projectId: string) {
    router.visit(`/projects/${projectId}`);
}
</script>

<template>
    <Head title="Projects" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-[#f5f7fb]">
            <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 lg:px-0">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-3xl font-bold text-slate-900">Projects</h1>
                        <p class="text-base text-slate-500">
                            Manage and track all your ongoing initiatives in one place.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <div
                            class="flex h-10 w-full max-w-xs items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-600 shadow-sm"
                        >
                            <Search class="size-4 text-slate-400" />
                            <input
                                v-model="searchTerm"
                                type="text"
                                class="h-full w-full bg-transparent text-sm outline-none placeholder:text-slate-400"
                                placeholder="Search projects..."
                            />
                        </div>
                        <Button
                            class="h-10 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-md hover:bg-blue-600/90"
                            @click="showCreateModal = true"
                        >
                            <Plus class="mr-2 size-4" />
                            Create Project
                        </Button>
                    </div>
                </div>

                <div
                    v-if="projectStore.loading || taskStore.loading"
                    class="rounded-2xl border border-dashed border-slate-200 bg-white/70 px-6 py-10 text-center text-slate-500 shadow-sm"
                >
                    Loading projects...
                </div>
                <div
                    v-else-if="projectStore.projects.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 bg-white/70 px-6 py-10 text-center text-slate-500 shadow-sm"
                >
                    No projects yet. Create your first project to get started!
                </div>
                <div
                    v-else-if="projectCards.length === 0"
                    class="rounded-2xl border border-dashed border-slate-200 bg-white/70 px-6 py-10 text-center text-slate-500 shadow-sm"
                >
                    No projects match your search.
                </div>
                <div
                    v-else
                    class="grid gap-4 md:grid-cols-2 lg:grid-cols-3"
                >
                    <Card
                        v-for="row in projectCards"
                        :key="row.project.id"
                        class="cursor-pointer rounded-2xl border-slate-200 bg-white shadow-sm transition-shadow hover:shadow-md"
                        @click="viewProject(row.project.id)"
                    >
                        <CardContent class="space-y-4 pb-5 pt-4">
                            <div class="flex items-start justify-between">
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-2xl"
                                    :class="row.colorClass"
                                >
                                    <FolderKanban class="size-6" />
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    class="h-8 w-8 rounded-full text-slate-500 hover:bg-slate-100"
                                    @click.stop
                                >
                                    <MoreHorizontal class="size-5" />
                                </Button>
                            </div>

                            <div class="space-y-2">
                                <h3 class="text-lg font-semibold text-slate-900">
                                    {{ row.project.name }}
                                </h3>
                                <p class="text-sm text-slate-500">
                                    No description added yet.
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-sm">
                                <Badge
                                    :class="`border ${row.status.classes}`"
                                >
                                    {{ row.status.label }}
                                </Badge>
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <CalendarDays class="size-4 text-slate-400" />
                                    Created {{ formatDate(row.project.created_at) }}
                                </div>
                            </div>
                        </CardContent>

                        <div class="border-t border-slate-200 px-6 pb-4 pt-3">
                            <div class="flex items-center justify-between gap-4 text-sm text-slate-600">
                                <div class="flex items-center gap-2">
                                    <CheckCircle2 class="size-4 text-slate-400" />
                                    <span class="font-semibold">
                                        {{ row.completed }}/{{ row.totalTasks }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <FolderKanban class="size-4 text-slate-400" />
                                    <span class="font-semibold">{{ row.totalTasks }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Users class="size-4 text-slate-400" />
                                    <div class="flex items-center gap-1">
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
                                        <span v-else class="text-xs font-medium text-slate-500">
                                            No members
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>

                <div
                    v-if="!projectStore.loading && projectCards.length > 0 && projectCards.length < 6"
                    class="grid h-52 place-items-center rounded-2xl border border-dashed border-slate-200 bg-white/60 text-slate-400"
                >
                    More projects will appear here
                </div>

                <div
                    v-if="showCreateModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    @click.self="showCreateModal = false"
                >
                    <Card class="w-full max-w-md rounded-2xl shadow-lg">
                        <CardHeader>
                            <CardTitle>Create New Project</CardTitle>
                            <CardDescription>Enter a name for your project</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form @submit.prevent="createProject" class="space-y-4">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-700">
                                        Project Name
                                    </label>
                                    <input
                                        v-model="newProjectName"
                                        type="text"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none"
                                        placeholder="My Awesome Project"
                                        required
                                    />
                                </div>
                                <div class="flex justify-end gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        class="rounded-lg border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                        @click="showCreateModal = false"
                                    >
                                        Cancel
                                    </Button>
                                    <Button
                                        type="submit"
                                        class="rounded-lg bg-blue-600 text-white hover:bg-blue-600/90"
                                        :disabled="projectStore.loading"
                                    >
                                        {{ projectStore.loading ? 'Creating...' : 'Create' }}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
