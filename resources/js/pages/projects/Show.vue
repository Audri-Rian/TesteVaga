<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useProjectStore } from '@/stores/projectStore';
import { useTaskStore } from '@/stores/taskStore';
import { TaskStatus } from '@/types/task';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import Badge from '@/components/ui/badge/Badge.vue';
import AlertError from '@/components/AlertError.vue';
import { Button } from '@/components/ui/button';
import Avatar from '@/components/ui/avatar/Avatar.vue';
import AvatarFallback from '@/components/ui/avatar/AvatarFallback.vue';
import {
    ArrowUpRight,
    BadgeCheck,
    Circle,
    ClipboardList,
    MoreHorizontal,
    Plus,
} from 'lucide-vue-next';

interface Props {
    projectId: string;
}

const props = defineProps<Props>();
const projectStore = useProjectStore();
const taskStore = useTaskStore();

const newMemberId = ref('');
const newTask = ref({
    title: '',
    description: '',
    project_id: props.projectId,
});
const memberError = ref<string | null>(null);

const statusOptions = [
    { label: 'Pending', value: TaskStatus.Pending },
    { label: 'In Progress', value: TaskStatus.InProgress },
    { label: 'Completed', value: TaskStatus.Done },
];

const statusBadge = (status: TaskStatus | string) => {
    if (status === TaskStatus.Done) {
        return 'border-emerald-100 bg-emerald-50 text-emerald-700';
    }
    if (status === TaskStatus.InProgress) {
        return 'border-blue-100 bg-blue-50 text-blue-700';
    }
    return 'border-slate-200 bg-slate-100 text-slate-700';
};

const statusLabel = (value: TaskStatus | string) => {
    const match = statusOptions.find(option => option.value === value);
    return match?.label || value;
};

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Projects',
        href: '/projects',
    },
    {
        title: projectStore.currentProject?.name || 'Project',
        href: `/projects/${props.projectId}`,
    },
]);

const projectTitle = computed(
    () => projectStore.currentProject?.name || 'Project',
);

const projectSubtitle = computed(
    () =>
        'Manage tasks, members, and progress for this initiative.',
);

const projectTasks = computed(() => taskStore.tasksByProject(props.projectId));
const projectMembers = computed(
    () => projectStore.currentProject?.members || [],
);

const taskStats = computed(() => {
    const total = projectTasks.value.length;
    const active = projectTasks.value.filter(
        task => task.status !== TaskStatus.Done,
    ).length;
    return { total, active };
});

async function loadData(projectId: string) {
    try {
        await Promise.all([
            projectStore.fetchProject(projectId),
            taskStore.fetchTasks({ project_id: projectId }),
        ]);
    } catch (error) {
        console.error('Failed to load project details:', error);
    }
}

onMounted(() => loadData(props.projectId));

watch(
    () => props.projectId,
    projectId => {
        loadData(projectId);
    },
);

async function addMember() {
    if (!newMemberId.value.trim()) return;

    try {
        memberError.value = null;
        await projectStore.addMember(props.projectId, {
            user_id: newMemberId.value,
        });
        newMemberId.value = '';
    } catch (error) {
        const message =
            (error as any)?.response?.data?.message ??
            projectStore.error ??
            'Failed to add member';
        memberError.value = message;
        console.error('Failed to add member:', error);
    }
}

async function createTask() {
    if (!newTask.value.title.trim() || !newTask.value.description.trim())
        return;

    try {
        newTask.value.project_id = props.projectId;
        await taskStore.createTask(newTask.value);
        newTask.value = {
            title: '',
            description: '',
            project_id: props.projectId,
        };
    } catch (error) {
        console.error('Failed to create task:', error);
    }
}

async function updateTaskStatus(taskId: string, status: TaskStatus) {
    try {
        await taskStore.updateTask(taskId, { status });
    } catch (error) {
        console.error('Failed to update task status:', error);
    }
}

async function assignTask(taskId: string, assigneeId: string) {
    if (!assigneeId) return;

    try {
        await taskStore.assignTask(taskId, {
            assignee_id: assigneeId,
        });
    } catch (error) {
        console.error('Failed to assign task:', error);
    }
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
    <Head :title="projectTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-[#f5f7fb]">
            <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 lg:px-0">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-3xl font-bold text-slate-900">
                            {{ projectTitle }}
                        </h1>
                        <p class="text-base text-slate-500">
                            {{ projectSubtitle }}
                        </p>
                    </div>
                    <Button
                        class="h-10 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-md hover:bg-blue-600/90"
                        as="a"
                        href="/tasks"
                    >
                        <ArrowUpRight class="mr-2 size-4" />
                        Go to Board
                    </Button>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="space-y-4">
                        <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                            <CardContent class="space-y-4 pb-5 pt-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">
                                            Members
                                        </p>
                                        <p class="text-xs text-slate-500">
                                            Manage project access
                                        </p>
                                    </div>
                                    <Badge class="border-slate-200 bg-slate-50 text-slate-700">
                                        {{ projectMembers.length }}
                                    </Badge>
                                </div>

                                <div
                                    v-if="projectMembers.length === 0"
                                    class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-3 py-4 text-sm text-slate-500"
                                >
                                    No members yet.
                                </div>
                                <ul v-else class="space-y-3">
                                    <li
                                        v-for="member in projectMembers"
                                        :key="member.id"
                                        class="flex items-center justify-between rounded-xl border border-slate-200 px-3 py-2"
                                    >
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-9 w-9 border border-white shadow-sm">
                                                <AvatarFallback class="bg-slate-200 text-xs font-semibold text-slate-700">
                                                    {{ getInitials(member.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div>
                                                <p class="text-sm font-semibold text-slate-900">
                                                    {{ member.name }}
                                                </p>
                                                <p class="text-xs text-slate-500">
                                                    {{ member.email }}
                                                </p>
                                            </div>
                                        </div>
                                        <Badge
                                            variant="secondary"
                                            class="border-slate-200 bg-slate-100 text-slate-700"
                                        >
                                            Member
                                        </Badge>
                                    </li>
                                </ul>

                                <form class="space-y-2" @submit.prevent="addMember">
                                    <label class="text-xs font-semibold uppercase text-slate-500">
                                        Add by UUID
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            v-model="newMemberId"
                                            type="text"
                                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none"
                                            placeholder="e.g. 550e8400-e29b..."
                                        />
                                        <Button
                                            type="submit"
                                            variant="outline"
                                            class="h-10 w-10 rounded-lg border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                            :disabled="projectStore.loading"
                                        >
                                            <Plus class="size-4" />
                                        </Button>
                                    </div>
                                    <AlertError
                                        v-if="memberError"
                                        :errors="[memberError]"
                                        title="Error adding member"
                                    />
                                </form>
                            </CardContent>
                        </Card>

                        <Card class="rounded-2xl border-slate-200 bg-white shadow-sm">
                            <CardContent class="space-y-4 pb-5 pt-4">
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-slate-900">
                                        New Task
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        Create a task in this project
                                    </p>
                                </div>
                                <form class="space-y-3" @submit.prevent="createTask">
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">
                                            Title
                                        </label>
                                        <input
                                            v-model="newTask.title"
                                            type="text"
                                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none"
                                            placeholder="Task summary"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">
                                            Description
                                        </label>
                                        <textarea
                                            v-model="newTask.description"
                                            rows="4"
                                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none"
                                            placeholder="Detailed requirements..."
                                            required
                                        ></textarea>
                                    </div>
                                    <div class="flex items-center justify-between gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            class="h-10 flex-1 rounded-lg border-slate-200 bg-white text-slate-700 hover:bg-slate-50"
                                            @click="
                                                newTask = {
                                                    title: '',
                                                    description: '',
                                                    project_id: props.projectId,
                                                }
                                            "
                                        >
                                            Reset
                                        </Button>
                                        <Button
                                            type="submit"
                                            class="h-10 flex-1 rounded-lg bg-blue-600 text-white hover:bg-blue-600/90"
                                            :disabled="taskStore.loading"
                                        >
                                            {{ taskStore.loading ? 'Creating...' : 'Create Task' }}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <Card class="md:col-span-2 rounded-2xl border-slate-200 bg-white shadow-sm">
                        <CardContent class="space-y-4 pb-5 pt-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <ClipboardList class="size-5 text-slate-600" />
                                    <p class="text-sm font-semibold text-slate-900">
                                        Project Tasks
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge class="border-slate-200 bg-slate-50 text-slate-700">
                                        Total: {{ taskStats.total }}
                                    </Badge>
                                    <Badge class="border-blue-100 bg-blue-50 text-blue-700">
                                        Active: {{ taskStats.active }}
                                    </Badge>
                                </div>
                            </div>

                            <div
                                v-if="taskStore.loading"
                                class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-slate-500"
                            >
                                Loading tasks...
                            </div>
                            <div
                                v-else-if="projectTasks.length === 0"
                                class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-slate-500"
                            >
                                No tasks in this project yet.
                            </div>
                            <div v-else class="space-y-3">
                                <div
                                    v-for="task in projectTasks"
                                    :key="task.id"
                                    class="rounded-xl border border-slate-200 px-4 py-4"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="space-y-1">
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-base font-semibold text-slate-900">
                                                    {{ task.title }}
                                                </h3>
                                                <Badge
                                                    :class="`border ${statusBadge(task.status)}`"
                                                >
                                                    {{ statusLabel(task.status) }}
                                                </Badge>
                                            </div>
                                            <p class="text-sm text-slate-500">
                                                {{ task.description }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <select
                                                class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none"
                                                :value="task.status"
                                                @click.stop
                                                @change="
                                                    updateTaskStatus(
                                                        task.id,
                                                        ($event.target as HTMLSelectElement)
                                                            .value as TaskStatus
                                                    )
                                                "
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
                                            variant="ghost"
                                            size="icon"
                                            class="h-8 w-8 rounded-full text-slate-500 hover:bg-slate-100"
                                            as="a"
                                            :href="`/tasks/${task.id}`"
                                            @click.stop
                                        >
                                                <ArrowUpRight class="size-4" />
                                            </Button>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center gap-1 rounded-full bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-700">
                                                <Circle
                                                    class="size-3"
                                                    :class="{
                                                        'text-emerald-500': task.status === TaskStatus.Done,
                                                        'text-blue-500': task.status === TaskStatus.InProgress,
                                                        'text-slate-400': task.status === TaskStatus.Pending,
                                                    }"
                                                />
                                                {{ statusLabel(task.status) }}
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <BadgeCheck class="size-4 text-slate-400" />
                                                <span class="text-xs text-slate-500">
                                                    #{{ task.id }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <select
                                                class="h-9 rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none"
                                                :value="task.assignee_id || ''"
                                                @click.stop
                                                @change="
                                                    assignTask(
                                                        task.id,
                                                        ($event.target as HTMLSelectElement).value
                                                    )
                                                "
                                            >
                                                <option value="">Unassigned</option>
                                                <option
                                                    v-for="member in projectMembers"
                                                    :key="member.id"
                                                    :value="member.id"
                                                >
                                                    {{ member.name }}
                                                </option>
                                            </select>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                class="h-8 w-8 rounded-full text-slate-500 hover:bg-slate-100"
                                            >
                                                <MoreHorizontal class="size-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
