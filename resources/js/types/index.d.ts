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
            settingsManage?: boolean;
            selfAssess?: boolean;
            calibrationView?: boolean;
            goalView?: boolean;
        };
        unreadNotifications: number;
    };
    branding: {
        app_name: string;
        app_logo: string | null;
        primary_color: string;
        footer_text: string;
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
