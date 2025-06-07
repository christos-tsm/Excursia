import { User } from '@/types/models';

/**
 * Επιστρέφει τους ρόλους ενός χρήστη ως array από strings
 */
export function getUserRoles(user: User): string[] {
    return user.roles?.map(role => role.name) || [];
}

/**
 * Ελέγχει αν ο χρήστης έχει συγκεκριμένο ρόλο
 */
export function userHasRole(user: User, role: string): boolean {
    const userRoles = getUserRoles(user);
    return userRoles.includes(role);
}

/**
 * Ελέγχει αν ο χρήστης έχει οποιονδήποτε από τους δοθέντες ρόλους
 */
export function userHasAnyRole(user: User, roles: string[]): boolean {
    const userRoles = getUserRoles(user);
    return roles.some(role => userRoles.includes(role));
}

/**
 * Επιστρέφει τον κύριο ρόλο του χρήστη (με προτεραιότητα)
 */
export function getUserPrimaryRole(user: User): string {
    const userRoles = getUserRoles(user);

    // Σειρά προτεραιότητας
    const rolePriority = ['super-admin', 'admin', 'owner', 'guide', 'staff'];

    for (const role of rolePriority) {
        if (userRoles.includes(role)) {
            return role;
        }
    }

    return 'staff'; // fallback
}

/**
 * Ελέγχει αν ο χρήστης είναι admin (super-admin ή admin)
 */
export function isAdmin(user: User): boolean {
    return userHasAnyRole(user, ['super-admin', 'admin']);
}

/**
 * Ελέγχει αν ο χρήστης είναι owner
 */
export function isOwner(user: User): boolean {
    return userHasRole(user, 'owner');
}

/**
 * Ελέγχει αν ο χρήστης είναι guide
 */
export function isGuide(user: User): boolean {
    return userHasRole(user, 'guide');
}

/**
 * Ελέγχει αν ο χρήστης είναι staff
 */
export function isStaff(user: User): boolean {
    return userHasRole(user, 'staff');
} 