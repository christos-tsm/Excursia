import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState, useEffect } from 'react';
import { User } from '@/types';
import { PageProps } from '@/types';
import { CalendarCheck2, LayoutDashboard, Map, Users, Mail, Eye, BarChart3, Settings, CheckCircle, MessageCircle, FileText } from 'lucide-react';
import { getCurrentTenantDomain } from '@/Utils/tenant';

export default function Authenticated({ header, children }: PropsWithChildren<{ header?: ReactNode }>) {
    const user = usePage<PageProps<{ user: User }>>().props.auth.user;
    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);
    const [domain, setDomain] = useState<string | null>(null);

    // Έλεγχος αν είμαστε σε path-based tenant route
    useEffect(() => {
        // Χρησιμοποιούμε τη συνάρτηση που δημιουργήσαμε
        const currentDomain = getCurrentTenantDomain();
        setDomain(currentDomain);
    }, []);

    // Προσδιορισμός της σωστής διαδρομής dashboard με βάση τον ρόλο του χρήστη
    const dashboardRoute = () => {
        if (user.roles && user.roles.some((role) => ['super-admin', 'admin'].includes(role.name))) {
            return '/admin/dashboard';
        }

        // Χρησιμοποιούμε το domain για το tenant dashboard URL
        return domain ? `/tenant/${domain}/dashboard` : '/';
    };

    // Check if a route is current
    const isCurrentRoute = (path: string) => {
        const currentPath = window.location.pathname;
        return currentPath.includes(path);
    };

    // Προσδιορισμός των διαθέσιμων routes βάσει του ρόλου
    const getTenantRoutes = () => {
        const userRoles = user.roles?.map(role => role.name) || [];

        // Owner - πλήρες navigation
        if (userRoles.includes('owner')) {
            return [
                {
                    name: 'Γενικά',
                    path: 'dashboard',
                    icon: LayoutDashboard,
                    current: isCurrentRoute('/dashboard'),
                },
                {
                    name: 'Ταξίδια',
                    path: 'trips',
                    icon: Map,
                    current: isCurrentRoute('/trips'),
                },
                {
                    name: 'Έγγραφα',
                    path: 'documents',
                    icon: FileText,
                    current: isCurrentRoute('/documents'),
                },
                {
                    name: 'Προσκλήσεις',
                    path: 'invitations',
                    icon: Mail,
                    current: isCurrentRoute('/invitations'),
                },
                {
                    name: 'Αναφορές',
                    path: 'dashboard', // προσωρινά
                    icon: BarChart3,
                    current: isCurrentRoute('/reports'),
                },
                {
                    name: 'Πελάτες',
                    path: 'dashboard', // προσωρινά
                    icon: Users,
                    current: isCurrentRoute('/clients'),
                },
                {
                    name: 'Ρυθμίσεις',
                    path: 'dashboard', // προσωρινά
                    icon: Settings,
                    current: isCurrentRoute('/settings'),
                },
            ];
        }

        // Guide - εστιασμένο σε ταξίδια
        if (userRoles.includes('guide')) {
            return [
                {
                    name: 'Γενικά',
                    path: 'dashboard',
                    icon: LayoutDashboard,
                    current: isCurrentRoute('/dashboard'),
                },
                {
                    name: 'Ταξίδια',
                    path: 'trips',
                    icon: Map,
                    current: isCurrentRoute('/trips'),
                },
                {
                    name: 'Πρόγραμμα',
                    path: 'dashboard', // προσωρινά
                    icon: CalendarCheck2,
                    current: isCurrentRoute('/schedule'),
                },
                {
                    name: 'Ομάδες',
                    path: 'dashboard', // προσωρινά
                    icon: Users,
                    current: isCurrentRoute('/groups'),
                },
            ];
        }

        // Staff - βασικές λειτουργίες
        return [
            {
                name: 'Γενικά',
                path: 'dashboard',
                icon: LayoutDashboard,
                current: isCurrentRoute('/dashboard'),
            },
            {
                name: 'Ταξίδια',
                path: 'trips',
                icon: Eye,
                current: isCurrentRoute('/trips'),
            },
            {
                name: 'Καθήκοντα',
                path: 'dashboard', // προσωρινά
                icon: CheckCircle,
                current: isCurrentRoute('/tasks'),
            },
            {
                name: 'Επικοινωνία',
                path: 'dashboard', // προσωρινά
                icon: MessageCircle,
                current: isCurrentRoute('/communication'),
            },
        ];
    };

    const tenantRoutes = getTenantRoutes();

    return (
        <div className="min-h-screen bg-gray-100 flex">
            <nav className="bg-white flex flex-col">
                <div className="flex flex-col flex-1">
                    <Link href={domain ? `/tenant/${domain}/dashboard` : '/'} className="p-4">
                        <ApplicationLogo />
                    </Link>
                    <div className="flex flex-col">
                        {tenantRoutes.map((item) => (
                            <Link
                                key={item.name}
                                href={domain ? `/tenant/${domain}/${item.path}` : '/'}
                                className={`inline-flex items-center gap-2 py-3 text-sm font-medium px-4 duration-300 hover:bg-slate-200 ${item.current ? 'bg-slate-200' : ''}`}
                            >
                                <item.icon size={16} strokeWidth={1.5} />
                                {item.name}
                            </Link>
                        ))}
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            href={dashboardRoute()}
                            active={isCurrentRoute('dashboard')}
                        >
                            Dashboard
                        </ResponsiveNavLink>
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href="/profile">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href="/logout"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>
            <main className="flex-1 bg-slate-200">
                {children}
            </main>
        </div>
    );
}
