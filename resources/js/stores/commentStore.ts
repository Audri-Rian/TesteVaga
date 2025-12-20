import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';
import type { Comment, AddCommentDTO } from '@/types/comment';

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

export const useCommentStore = defineStore('comment', () => {
    // State
    const comments = ref<Comment[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    // Getters
    const commentsByTask = computed(() => (taskId: string) => {
        return comments.value.filter(c => c.task_id === taskId);
    });

    // Actions
    async function fetchComments(taskId: string) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.get(`/api/tasks/${taskId}/comments`);
            comments.value = unwrapArrayResponse<Comment>(response.data);
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to fetch comments';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function addComment(dto: AddCommentDTO) {
        loading.value = true;
        error.value = null;

        try {
            const response = await axios.post('/api/comments', dto);
            const comment = unwrapItemResponse<Comment>(response.data);
            comments.value.unshift(comment);
            return comment;
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to add comment';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function deleteComment(id: string) {
        loading.value = true;
        error.value = null;

        try {
            await axios.delete(`/api/comments/${id}`);

            // Remove from list
            const index = comments.value.findIndex(c => c.id === id);
            if (index !== -1) {
                comments.value.splice(index, 1);
            }
        } catch (err: any) {
            error.value = err.response?.data?.message || 'Failed to delete comment';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        // State
        comments,
        loading,
        error,

        // Getters
        commentsByTask,

        // Actions
        fetchComments,
        addComment,
        deleteComment,
    };
});
