/**
 * Εξάγει το domain από το URL path της τρέχουσας σελίδας.
 * Το URL αναμένεται να είναι της μορφής "/tenant/{domain}/..."
 * 
 * @returns {string|null} Το domain ή null αν δεν βρέθηκε
 */
export const getCurrentTenantDomain = (): string | null => {
    // Χρησιμοποιούμε το window.location.pathname για να πάρουμε το τρέχον path
    const currentUrl = window.location.pathname;
    const pathSegments = currentUrl.split('/');

    // Το URL πρέπει να είναι της μορφής "/tenant/{domain}/..."
    return pathSegments.length >= 3 && pathSegments[1] === 'tenant' ? pathSegments[2] : null;
};

/**
 * Δημιουργεί ένα URL για μια συγκεκριμένη route χρησιμοποιώντας το τρέχον tenant domain
 * 
 * @param {string} routeName Το όνομα του route
 * @param {any} params Επιπλέον παράμετροι για το route
 * @returns {string} Το πλήρες URL
 */
export const tenantRoute = (routeName: string, params: any = {}): string => {
    const domain = getCurrentTenantDomain();
    return route(routeName, { ...params, domain });
}; 