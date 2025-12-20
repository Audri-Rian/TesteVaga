import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import { TaskStatus, type Task, type CreateTaskDTO, type UpdateTaskDTO, type AssignTaskDTO } from '@/types/task';

function unwrapArrayResponse<T>(payload: unknown): T[] {
    if (Array.isArray(payload)) return payload;
    if (payload && typeof payload === 'object' && Array.isArray((payload as any).data)) {
        return (payload as any).data;
    }
    return [];
}

function unwrapItemResponse<T>(payload: unknown): T {
    if (payload && typeof payload === 'object' && 'data' in (payload as any)) {
        return (payload as any).data as T;
    }
    return payload as T;
}

function normalizeStatus(status: string): TaskStatus {
    const normalized = status.toLowerCase();

    if (normalized === 'pending') return TaskStatus.Pending;
    if (normalized === 'in_progress' || normalized === 'inprogress') return TaskStatus.InProgress;
    if (normalized === 'done' || normalized === 'completed') return TaskStatus.Done;

    return status as TaskStatus;
}

function mapTask(task: Task): Task {
    return {
        ...task,
        status: normalizeStatus(task.status),
    };
}

export const useTaskStore = defineStore('task', () => {
    // State
    const tasks = ref<Task[]>([]);
    const currentTask = ref<Task | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // Getters
    const tasksByStatus = computed(() => (status: TaskStatus) => {
        return tasks.value.filter(t => t.status === status);
    });

    const tasksByProject = computed(() => (projectId: string) => {
        return tasks.value.filter(t => t.project_id === projectId);
    });

    const pendingTasks = computed(() => {
        return tasks.value.filter(t => t.status === TaskStatus.Pending);
    });

    const inProgressTasks = computed(() => {
        return tasks.value.filter(t => t.status === TaskStatus.InProgress);
    });

    const completedTasks = computed(() => {
        return tasks.value.filter(t => t.status === TaskStatus.Done);
    });

    // Actions
    async function fetchTasks(filters?: { status?: string; project_id?: string; assignee_id?: string }) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/tasks', { params: filters });
            tasks.value = unwrapArrayResponse<Task>(response.data).map(mapTask);
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch tasks';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createTask(dto: CreateTaskDTO) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/api/tasks', dto);
            const task = mapTask(unwrapItemResponse<Task>(response.data));
            tasks.value.unshift(task);
            return task;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to create task';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchTask(id: string) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(`/api/tasks/${id}`);
            const task = mapTask(unwrapItemResponse<Task>(response.data));
            currentTask.value = task;

            // Update in the list if exists
            const index = tasks.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tasks.value[index] = task;
            }

            return task;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch task';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function updateTask(id: string, dto: UpdateTaskDTO) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.patch(`/api/tasks/${id}`, dto);
            const task = mapTask(unwrapItemResponse<Task>(response.data));

            // Update current task
            if (currentTask.value?.id === id) {
                currentTask.value = task;
            }

            // Update in list
            const index = tasks.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tasks.value[index] = task;
            }

            return task;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to update task';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function assignTask(id: string, dto: AssignTaskDTO) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.patch(`/api/tasks/${id}/assign`, dto);
            const task = mapTask(unwrapItemResponse<Task>(response.data));

            // Update current task
            if (currentTask.value?.id === id) {
                currentTask.value = task;
            }

            // Update in list
            const index = tasks.value.findIndex(t => t.id === id);
            if (index !== -1) {
                tasks.value[index] = task;
            }

            return task;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to assign task';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        // State
        tasks,
        currentTask,
        loading,
        error,

        // Getters
        tasksByStatus,
        tasksByProject,
        pendingTasks,
        inProgressTasks,
        completedTasks,

        // Actions
        fetchTasks,
        createTask,
        fetchTask,
        updateTask,
        assignTask,
    };
});
