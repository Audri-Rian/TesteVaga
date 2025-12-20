export interface Project {
    id: string;
    name: string;
    created_at: string;
    updated_at: string;
    members?: User[];
    tasks?: Task[];
    tasks_count?: number;
    members_count?: number;
}

export interface Member {
    id: string;
    name: string;
    email: string;
    pivot?: {
        access_level: string;
        joined_at: string;
    };
}

export interface User {
    id: string;
    name: string;
    email: string;
    created_at: string;
}

export interface CreateProjectDTO {
    name: string;
}

export interface AddMemberDTO {
    user_id: string;
}
