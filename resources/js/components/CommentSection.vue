<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useCommentStore } from '@/stores/commentStore';
import { usePage } from '@inertiajs/vue3';
import Card from '@/components/ui/card/Card.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';
import CardContent from '@/components/ui/card/CardContent.vue';

interface Props {
    taskId: string;
}

const props = defineProps<Props>();
const commentStore = useCommentStore();
const newComment = ref('');
const page = usePage();

const currentUserId = computed(() => String(page.props.auth?.user?.id || ''));

onMounted(async () => {
    if (props.taskId) {
        try {
            await commentStore.fetchComments(props.taskId);
        } catch (error) {
            console.error('Failed to load comments:', error);
        }
    }
});

const taskComments = computed(() => commentStore.commentsByTask(props.taskId));

async function addComment() {
    if (!newComment.value.trim()) return;
    
    try {
        await commentStore.addComment({
            task_id: props.taskId,
            content: newComment.value,
        });
        newComment.value = '';
    } catch (error) {
        console.error('Failed to add comment:', error);
    }
}

async function deleteComment(commentId: string) {
    if (!confirm('Are you sure you want to delete this comment?')) return;
    
    try {
        await commentStore.deleteComment(commentId);
    } catch (error) {
        console.error('Failed to delete comment:', error);
    }
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Comments</CardTitle>
        </CardHeader>
        <CardContent>
            <!-- Add Comment Form -->
            <form @submit.prevent="addComment" class="mb-4">
                <div class="flex gap-2">
                    <input
                        v-model="newComment"
                        type="text"
                        class="flex-1 rounded-lg border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Add a comment..."
                        :disabled="commentStore.loading"
                    />
                    <button
                        type="submit"
                        :disabled="commentStore.loading || !newComment.trim()"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ commentStore.loading ? 'Posting...' : 'Post' }}
                    </button>
                </div>
            </form>

            <!-- Comments List -->
            <div v-if="commentStore.loading" class="text-center text-muted-foreground py-4">
                Loading comments...
            </div>
            <div v-else-if="taskComments.length === 0" class="text-center text-muted-foreground py-4">
                No comments yet. Be the first to comment!
            </div>
            <div v-else class="space-y-3">
                <div
                    v-for="comment in taskComments"
                    :key="comment.id"
                    class="rounded-lg border p-3"
                >
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="font-medium text-sm">{{ comment.user?.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ new Date(comment.created_at).toLocaleString() }}
                            </p>
                        </div>
                        <button
                            v-if="comment.user_id === currentUserId"
                            @click="deleteComment(comment.id)"
                            class="text-xs text-destructive hover:underline"
                        >
                            Delete
                        </button>
                    </div>
                    <p class="text-sm">{{ comment.content }}</p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
