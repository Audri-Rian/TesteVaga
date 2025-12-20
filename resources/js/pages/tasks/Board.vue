<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useTaskStore } from '@/stores/taskStore';
import { useProjectStore } from '@/stores/projectStore';
import { TaskStatus } from '@/types/task';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import { Button } from '@/components/ui/button';
import Badge from '@/components/ui/badge/Badge.vue';
import { ArrowLeft, ArrowRight, Check, Plus } from 'lucide-vue-next';

const taskStore = useTaskStore();
const projectStore = useProjectStore();
const showCreateModal = ref(false);
const newTask = ref({
    project_id: '',
    title: '',
    description: '',
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tasks',
        href: '/tasks',
    },
];

onMounted(async () => {
    try {
        await Promise.all([
            taskStore.fetchTasks(),
            projectStore.fetchProjects(),
        ]);
    } catch (error) {
        console.error('Failed to load tasks:', error);
    }
});

const columns = computed(() => [
    {
        title: 'Pending',
        color: 'text-slate-700',
        dot: 'bg-blue-500',
        tasks: taskStore.pendingTasks,
        status: TaskStatus.Pending,
    },
    {
        title: 'In Progress',
        color: 'text-slate-700',
        dot: 'bg-purple-500',
        tasks: taskStore.inProgressTasks,
        status: TaskStatus.InProgress,
    },
    {
        title: 'Completed',
        color: 'text-slate-700',
        dot: 'bg-emerald-500',
        tasks: taskStore.completedTasks,
        status: TaskStatus.Done,
    },
]);

const tagsPalette = [
    'bg-blue-50 text-blue-600',
    'bg-amber-50 text-amber-600',
    'bg-emerald-50 text-emerald-600',
    'bg-indigo-50 text-indigo-600',
    'bg-rose-50 text-rose-600',
];

function getTagStyle(index: number) {
    return tagsPalette[index % tagsPalette.length];
}

async function createTask() {
    if (!newTask.value.title.trim() || !newTask.value.project_id) return;

    try {
        await taskStore.createTask(newTask.value);
        newTask.value = { project_id: '', title: '', description: '' };
        showCreateModal.value = false;
    } catch (error) {
        console.error('Failed to create task:', error);
    }
}

async function updateTaskStatus(taskId: string, status: TaskStatus) {
    try {
        await taskStore.updateTask(taskId, { status });
    } catch (error) {
        console.error('Failed to update task:', error);
    }
}

function viewTask(taskId: string) {
    router.visit(`/tasks/${taskId}`);
}
</script>

<template>
    <Head title="Tasks" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-full bg-[#f5f7fb]">
            <div class="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-6 lg:px-0">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-3xl font-bold text-slate-900">Tasks</h1>
                        <p class="text-base text-slate-500">
                            Manage your project workflow efficiently
                        </p>
                    </div>
                    <Button
                        class="h-10 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-md hover:bg-blue-600/90"
                        @click="showCreateModal = true"
                    >
                        <Plus class="mr-2 size-4" />
                        Create Task
                    </Button>
                </div>

                <div
                    v-if="taskStore.loading"
                    class="rounded-2xl border border-dashed border-slate-200 bg-white/70 px-6 py-10 text-center text-slate-500 shadow-sm"
                >
                    Loading tasks...
                </div>

                <div v-else class="grid gap-4 md:grid-cols-3">
                    <div
                        v-for="(column, idx) in columns"
                        :key="column.title"
                        class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white shadow-sm"
                    >
                        <div class="flex items-center justify-between px-4 pt-4">
                            <div class="flex items-center gap-2">
                                <span :class="`h-2 w-2 rounded-full ${column.dot}`" />
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ column.title.toUpperCase() }}
                                </p>
                            </div>
                            <Badge class="border-slate-200 bg-slate-50 text-slate-700">
                                {{ column.tasks.length }}
                            </Badge>
                        </div>

                        <div class="flex flex-1 flex-col gap-3 px-4 pb-4">
                            <Card
                                v-for="(task, taskIndex) in column.tasks"
                                :key="task.id"
                                class="cursor-pointer rounded-xl border-slate-200 bg-white shadow-sm hover:shadow-md"
                                @click="viewTask(task.id)"
                            >
                                <CardContent class="space-y-3 pb-4 pt-3">
                                    <div class="flex items-center justify-between text-xs font-semibold uppercase text-slate-500">
                                        <Badge
                                            variant="secondary"
                                            :class="`border-0 ${getTagStyle(taskIndex + idx)}`"
                                        >
                                            {{ task.project?.name || 'Project' }}
                                        </Badge>
                                        <div class="flex items-center gap-2">
                                            <Button
                                                v-if="column.status !== TaskStatus.Pending"
                                                variant="ghost"
                                                size="icon"
                                                class="h-7 w-7 rounded-full text-slate-500 hover:bg-slate-100"
                                                @click.stop="updateTaskStatus(task.id, column.status === TaskStatus.Done ? TaskStatus.InProgress : TaskStatus.Pending)"
                                            >
                                                <ArrowLeft class="size-4" />
                                            </Button>
                                            <Button
                                                v-if="column.status !== TaskStatus.Done"
                                                variant="ghost"
                                                size="icon"
                                                class="h-7 w-7 rounded-full text-slate-500 hover:bg-slate-100"
                                                @click.stop="updateTaskStatus(task.id, column.status === TaskStatus.Pending ? TaskStatus.InProgress : TaskStatus.Done)"
                                            >
                                                <ArrowRight class="size-4" />
                                            </Button>
                                        </div>
                                    </div>

                                    <div class="space-y-1">
                                        <h3 class="text-base font-semibold text-slate-900">
                                            {{ task.title }}
                                        </h3>
                                        <p class="text-sm text-slate-500">
                                            {{ task.description }}
                                        </p>
                                    </div>

                                    <div class="flex items-center justify-between text-sm text-blue-600">
                                        <span class="flex items-center gap-1 text-slate-500">
                                            <ArrowLeft class="size-4" />
                                            <ArrowRight class="size-4" />
                                        </span>
                                        <div class="flex items-center gap-1 text-emerald-600" v-if="column.status === TaskStatus.Done">
                                            <Check class="size-4" />
                                            Done
                                        </div>
                                        <a v-else class="font-semibold text-blue-600">Open</a>
                                    </div>
                                </CardContent>
                            </Card>

                            <div
                                v-if="column.tasks.length === 0"
                                class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500"
                            >
                                No tasks here yet.
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="showCreateModal"
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    @click.self="showCreateModal = false"
                >
                    <Card class="w-full max-w-md rounded-2xl shadow-lg">
                        <CardContent class="space-y-4 pb-5 pt-4">
                            <div class="space-y-1">
                                <p class="text-lg font-semibold text-slate-900">
                                    Create New Task
                                </p>
                                <p class="text-sm text-slate-500">
                                    Add a task to a project
                                </p>
                            </div>
                            <form @submit.prevent="createTask" class="space-y-3">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">
                                        Project
                                    </label>
                                    <select
                                        v-model="newTask.project_id"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none"
                                        required
                                    >
                                        <option value="">Select a project</option>
                                        <option
                                            v-for="project in projectStore.projects"
                                            :key="project.id"
                                            :value="project.id"
                                        >
                                            {{ project.name }}
                                        </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">
                                        Title
                                    </label>
                                    <input
                                        v-model="newTask.title"
                                        type="text"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none"
                                        placeholder="Task title"
                                        required
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="newTask.description"
                                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-400 focus:outline-none"
                                        placeholder="Task description"
                                        rows="3"
                                        required
                                    ></textarea>
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
                                        :disabled="taskStore.loading"
                                    >
                                        {{ taskStore.loading ? 'Creating...' : 'Create' }}
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
