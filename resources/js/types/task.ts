export enum TaskStatus {
    Pending = 'pending',
    InProgress = 'in_progress',
    Done = 'done',
}

export interface Task {
    id: string;
    project_id: string;
    title: string;
    description: string;
    status: TaskStatus;
    assignee_id: string | null;
    created_at: string;
    updated_at: string;
    project?: {
        id: string;
        name: string;
    };
    assignee?: {
        id: string;
        name: string;
        email: string;
    };
    comments?: Comment[];
    comments_count?: number;
}

export interface CreateTaskDTO {
    project_id: string;
    title: string;
    description: string;
}

export interface UpdateTaskDTO {
    status: TaskStatus;
}

export interface AssignTaskDTO {
    assignee_id: string;
}
