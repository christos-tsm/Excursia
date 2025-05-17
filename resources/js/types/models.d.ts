interface User {
    id: number;
    name: string;
    email: string;
    tenant_id?: number;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
    roles?: Role[];
    [key: string]: any; // Για χρήση στα routes
}

interface Role {
    id: number;
    name: string;
    guard_name: string;
    created_at: string;
    updated_at: string;
    pivot?: any;
    [key: string]: any; // Για χρήση στα routes
}

interface Tenant {
    id: number;
    name: string;
    email: string;
    phone?: string;
    logo?: string;
    description?: string;
    database: string;
    is_active: boolean;
    owner_id: number;
    created_at: string;
    updated_at: string;
    owner?: User;
    domains?: Domain[];
    domains_count?: number;
    [key: string]: any; // Για χρήση στα routes
}

interface Domain {
    id: number;
    domain: string;
    tenant_id: number;
    is_primary: boolean;
    created_at: string;
    updated_at: string;
    [key: string]: any; // Για χρήση στα routes
}

export { User, Role, Tenant, Domain }; 