import { Config } from 'ziggy-js';

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at: string;
    tenant_id: number | null;
    roles?: Array<{
        id: number,
        name: string,
        guard_name: string,
        pivot?: {
            model_id: number,
            role_id: number,
            model_type: string
        }
    }>;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    ziggy: Config & { location: string };
};
