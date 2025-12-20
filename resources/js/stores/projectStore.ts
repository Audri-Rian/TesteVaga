import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import type { Project, CreateProjectDTO, AddMemberDTO } from '@/types/project';

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

export const useProjectStore = defineStore('project', () => {
    // State
    const projects = ref<Project[]>([]);
    const currentProject = ref<Project | null>(null);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // Getters
    const userProjects = computed(() => projects.value);

    const projectById = computed(() => (id: string) => {
        return projects.value.find(p => p.id === id);
    });

    // Actions
    async function fetchProjects() {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get('/api/projects');
            projects.value = unwrapArrayResponse<Project>(response.data);
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch projects';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createProject(dto: CreateProjectDTO) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/api/projects', dto);
            const project = unwrapItemResponse<Project>(response.data);
            projects.value.unshift(project);
            return project;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to create project';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchProject(id: string) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(`/api/projects/${id}`);
            const project = unwrapItemResponse<Project>(response.data);
            currentProject.value = project;

            // Update in the list if exists
            const index = projects.value.findIndex(p => p.id === id);
            if (index !== -1) {
                projects.value[index] = project;
            }

            return project;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch project';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function addMember(projectId: string, dto: AddMemberDTO) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post(
                `/api/projects/${projectId}/members`,
                dto
            );
            const project = unwrapItemResponse<Project>(response.data);

            // Update current project
            if (currentProject.value?.id === projectId) {
                currentProject.value = project;
            }

            // Update in list
            const index = projects.value.findIndex(p => p.id === projectId);
            if (index !== -1) {
                projects.value[index] = project;
            }

            return project;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to add member';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        // State
        projects,
        currentProject,
        loading,
        error,

        // Getters
        userProjects,
        projectById,

        // Actions
        fetchProjects,
        createProject,
        fetchProject,
        addMember,
    };
});
