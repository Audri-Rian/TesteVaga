<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useTaskStore } from '@/stores/taskStore';
import { useProjectStore } from '@/stores/projectStore';
import { TaskStatus } from '@/types/task';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import CommentSection from '@/components/CommentSection.vue';
import { Button } from '@/components/ui/button';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';
import {
    ArrowLeft,
    Folder,
    MoreHorizontal,
    UserRound,
} from 'lucide-vue-next';

interface Props {
    taskId: string;
}

const props = defineProps<Props>();
const taskStore = useTaskStore();
const projectStore = useProjectStore();

const statusSelection = ref<TaskStatus | ''>('');
const assigneeSelection = ref<string>('');
const statusOptions = [
    { label: 'Pending', value: TaskStatus.Pending },
    { label: 'In Progress', value: TaskStatus.InProgress },
    { label: 'Completed', value: TaskStatus.Done },
];

const statusLabel = (value: TaskStatus | string | null | undefined) => {
    const match = statusOptions.find(option => option.value === value);
    return match?.label || value || 'Pending';
};

const statusBadge = (value: TaskStatus | string | null | undefined) => {
    if (value === TaskStatus.Done) {
        return 'border-emerald-100 bg-emerald-50 text-emerald-700';
    }
    if (value === TaskStatus.InProgress) {
        return 'border-amber-100 bg-amber-50 text-amber-700';
    }
    return 'border-slate-200 bg-slate-100 text-slate-700';
};

const task = computed(() => taskStore.currentTask);
const projectMembers = computed(
    () => projectStore.currentProject?.members || [],
);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Tasks', href: '/tasks' },
    {
        title: task.value?.title || 'Task',
        href: `/tasks/${props.taskId}`,
    },
]);

async function loadTask(taskId: string) {
    try {
        await taskStore.fetchTask(taskId);
        statusSelection.value = taskStore.currentTask?.status || '';
        assigneeSelection.value = taskStore.currentTask?.assignee_id || '';

        if (taskStore.currentTask?.project_id) {
            await projectStore.fetchProject(taskStore.currentTask.project_id);
        }
    } catch (error) {
        console.error('Failed to load task details:', error);
    }
}

onMounted(() => loadTask(props.taskId));

watch(
    () => props.taskId,
    taskId => {
        loadTask(taskId);
    },
);

async function updateStatus() {
    if (!statusSelection.value) return;

    try {
        await taskStore.updateTask(props.taskId, {
            status: statusSelection.value as TaskStatus,
        });
    } catch (error) {
        console.error('Failed to update task status:', error);
    }
}

async function assignTask() {
    if (!assigneeSelection.value) return;

    try {
        await taskStore.assignTask(props.taskId, {
            assignee_id: assigneeSelection.value,
        });
    } catch (error) {
        console.error('Failed to assign task:', error);
    }
}

function formatDate(date: string | undefined) {
    if (!date) return '-';
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
    <Head :title="task?.title || 'Task'" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-[#f5f7fb]">
            <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 lg:px-0">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-3xl font-bold text-slate-900">
                            {{ task?.title || 'Task' }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                            <span>Task ID: #{{ task?.id || '-' }}</span>
                            <span class="h-1 w-1 rounded-full bg-slate-300" />
                            <span>{{ statusLabel(task?.status) }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            as="a"
                            href="/tasks"
                            variant="outline"
                            class="h-10 rounded-xl border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50"
                        >
                            <ArrowLeft class="mr-2 size-4" />
                            Back to Board
                        </Button>
                        <Button
                            v-if="task?.project_id"
                            as="a"
                            :href="`/projects/${task.project_id}`"
                            class="h-10 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-md hover:bg-blue-600/90"
                        >
                            View Project
                        </Button>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="md:col-span-2 space-y-4">
                        <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                            <CardContent class="space-y-4 pb-5 pt-4">
                                <div class="flex items-start justify-between">
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-slate-900">
                                            Snapshot
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            Quick view of status, assignee, and project
                                        </p>
                                    </div>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        class="h-8 w-8 rounded-full text-slate-500 hover:bg-slate-100"
                                    >
                                        <MoreHorizontal class="size-5" />
                                    </Button>
                                </div>

                                <div class="grid gap-4 md:grid-cols-3">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                        <p class="text-xs font-semibold uppercase text-slate-500">
                                            Status
                                        </p>
                                        <div class="mt-2">
                                            <Badge :class="`border ${statusBadge(task?.status)}`">
                                                {{ statusLabel(task?.status) }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                        <p class="text-xs font-semibold uppercase text-slate-500">
                                            Assignee
                                        </p>
                                        <div class="mt-2 flex items-center gap-2">
                                            <Avatar class="h-9 w-9 border border-white shadow-sm">
                                                <AvatarFallback class="bg-slate-200 text-xs font-semibold text-slate-700">
                                                    {{ getInitials(task?.assignee?.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <span class="text-sm font-semibold text-slate-900">
                                                {{ task?.assignee?.name || 'Unassigned' }}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                                        <p class="text-xs font-semibold uppercase text-slate-500">
                                            Project
                                        </p>
                                        <div class="mt-2 flex items-center gap-2 text-sm font-semibold text-blue-600">
                                            <Folder class="size-4" />
                                            <a
                                                v-if="task?.project_id"
                                                :href="`/projects/${task.project_id}`"
                                                class="hover:underline"
                                            >
                                                {{ task?.project?.name || 'View project' }}
                                            </a>
                                            <span v-else class="text-slate-500">No project</span>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                            <CardContent class="space-y-3 pb-5 pt-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-slate-900">Description</p>
                                </div>
                                <div class="space-y-3 text-sm leading-relaxed text-slate-600">
                                    <p class="whitespace-pre-line">
                                        {{ task?.description || 'No description provided.' }}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <div class="grid gap-3 md:grid-cols-3">
                            <Card class="rounded-xl border-slate-200 bg-white shadow-sm">
                                <CardContent class="pb-3 pt-3">
                                    <p class="text-xs font-semibold uppercase text-slate-500">
                                        Created By
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ task?.assignee?.name || 'Unknown' }}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card class="rounded-xl border-slate-200 bg-white shadow-sm">
                                <CardContent class="pb-3 pt-3">
                                    <p class="text-xs font-semibold uppercase text-slate-500">
                                        Created At
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ formatDate(task?.created_at) }}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card class="rounded-xl border-slate-200 bg-white shadow-sm">
                                <CardContent class="pb-3 pt-3">
                                    <p class="text-xs font-semibold uppercase text-slate-500">
                                        Last Updated
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ formatDate(task?.updated_at) }}
                                    </p>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                            <CardContent class="space-y-3 pb-5 pt-4">
                                <p class="text-sm font-semibold text-slate-900">
                                    Update Status
                                </p>
                                <select
                                    v-model="statusSelection"
                                    class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm shadow-sm focus:border-blue-400 focus:outline-none"
                                >
                                    <option
                                        v-for="status in statusOptions"
                                        :key="status.value"
                                        :value="status.value"
                                    >
                                        {{ status.label }}
                                    </option>
                                </select>
                                <Button
                                    class="h-10 w-full rounded-lg bg-blue-600 text-white hover:bg-blue-600/90"
                                    :disabled="taskStore.loading || !statusSelection"
                                    @click="updateStatus"
                                >
                                    Save Changes
                                </Button>
                            </CardContent>
                        </Card>

                        <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                            <CardContent class="space-y-3 pb-5 pt-4">
                                <p class="text-sm font-semibold text-slate-900">
                                    Assign to Member
                                </p>
                                <div class="relative">
                                    <select
                                        v-model="assigneeSelection"
                                        class="h-11 w-full rounded-lg border border-slate-200 bg-white px-3 text-sm shadow-sm focus:border-blue-400 focus:outline-none"
                                    >
                                        <option value="">Select member</option>
                                        <option
                                            v-for="member in projectMembers"
                                            :key="member.id"
                                            :value="member.id"
                                        >
                                            {{ member.name }}
                                        </option>
                                    </select>
                                    <UserRound class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                                </div>
                                <Button
                                    class="h-10 w-full rounded-lg border border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                    :disabled="taskStore.loading || !assigneeSelection"
                                    @click="assignTask"
                                >
                                    Assign
                                </Button>
                            </CardContent>
                        </Card>

                        <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                            <CardContent class="space-y-3 pb-5 pt-4">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-semibold text-slate-900">Comments</p>
                                    <span class="text-xs font-semibold text-slate-500">
                                        {{ task?.comments_count || 0 }}
                                    </span>
                                </div>
                                <CommentSection v-if="task?.id" :task-id="task.id" />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
