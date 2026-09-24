export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
        access: {
            role: string;
            scopeLabels: string[];
        } | null;
        abilities: {
            dashboardView?: boolean;
            employeeView?: boolean;
            incidentView?: boolean;
            evaluationView?: boolean;
            reportView?: boolean;
            auditView?: boolean;
            organizationView?: boolean;
            userView?: boolean;
        };
        unreadNotifications: number;
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
    impersonation: {
        active: boolean;
        target_name: string;
    } | null;
};
