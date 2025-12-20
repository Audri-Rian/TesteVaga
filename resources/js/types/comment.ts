export interface Comment {
    id: string;
    task_id: string;
    user_id: string;
    content: string;
    created_at: string;
    updated_at: string;
    user?: {
        id: string;
        name: string;
        email: string;
    };
    task?: {
        id: string;
        title: string;
    };
}

export interface AddCommentDTO {
    task_id: string;
    content: string;
}
