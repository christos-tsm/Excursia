import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Tenant, User } from '@/types/models';
import { getCurrentTenantDomain } from '@/Utils/tenant';
import {
    MapPin,
    Users,
    Calendar,
    Mail,
    Settings,
    BarChart3,
    CreditCard,
    UserPlus,
    FileText
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface OwnerDashboardProps {
    tenant: Tenant;
    userName: string;
    users: User[];
}

export default function OwnerDashboard({ tenant, userName, users }: OwnerDashboardProps) {
    const domain = getCurrentTenantDomain();

    const quickActions = [
        {
            title: 'Διαχείριση Ταξιδιών',
            description: 'Δημιουργήστε νέα ταξίδια και διαχειριστείτε τα υπάρχοντα',
            icon: MapPin,
            href: domain ? `/tenant/${domain}/trips` : '#',
            color: 'bg-blue-50 border-blue-200',
            iconColor: 'text-blue-600',
            buttonColor: 'bg-blue-600 hover:bg-blue-700'
        },
        {
            title: 'Διαχείριση Προσωπικού',
            description: 'Στείλτε προσκλήσεις σε νέους ξεναγούς και προσωπικό',
            icon: UserPlus,
            href: domain ? `/tenant/${domain}/invitations` : '#',
            color: 'bg-green-50 border-green-200',
            iconColor: 'text-green-600',
            buttonColor: 'bg-green-600 hover:bg-green-700'
        },
        {
            title: 'Αναφορές & Στατιστικά',
            description: 'Προβολή στατιστικών πωλήσεων και αναφορών',
            icon: BarChart3,
            href: '#',
            color: 'bg-purple-50 border-purple-200',
            iconColor: 'text-purple-600',
            buttonColor: 'bg-purple-600 hover:bg-purple-700'
        },
        {
            title: 'Οικονομικά',
            description: 'Διαχείριση κρατήσεων και πληρωμών',
            icon: CreditCard,
            href: '#',
            color: 'bg-orange-50 border-orange-200',
            iconColor: 'text-orange-600',
            buttonColor: 'bg-orange-600 hover:bg-orange-700'
        },
        {
            title: 'Πελάτες',
            description: 'Διαχείριση και επικοινωνία με πελάτες',
            icon: Users,
            href: '#',
            color: 'bg-cyan-50 border-cyan-200',
            iconColor: 'text-cyan-600',
            buttonColor: 'bg-cyan-600 hover:bg-cyan-700'
        },
        {
            title: 'Ρυθμίσεις Γραφείου',
            description: 'Επεξεργασία προφίλ και ρυθμίσεων γραφείου',
            icon: Settings,
            href: '#',
            color: 'bg-gray-50 border-gray-200',
            iconColor: 'text-gray-600',
            buttonColor: 'bg-gray-600 hover:bg-gray-700'
        }
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Dashboard - ${tenant.name}`} />
            <div className="p-4">
                <div className="space-y-6">
                    {/* Welcome Header */}
                    <div className="bg-gradient-to-r from-[#3892DA] to-[#43ABFF] rounded-lg p-6 text-white">
                        <h1 className="text-2xl font-bold mb-2">
                            Καλώς ήρθατε, {userName}!
                        </h1>
                        <p className="text-blue-100 text-lg">
                            Διαχειριστείτε το τουριστικό σας γραφείο "{tenant.name}"
                        </p>
                        <div className="mt-4 flex items-center gap-4 text-sm">
                            <div className="flex items-center gap-1">
                                <Mail size={16} />
                                <span>{tenant.email}</span>
                            </div>
                            {tenant.phone && (
                                <div className="flex items-center gap-1">
                                    <span>📞</span>
                                    <span>{tenant.phone}</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Quick Stats */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Ενεργά Ταξίδια</p>
                                    <p className="text-2xl font-bold text-gray-900">0</p>
                                </div>
                                <MapPin className="text-blue-600" size={24} />
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Αριθμός Προσωπικού</p>
                                    <p className="text-2xl font-bold text-gray-900">{users?.length}</p>
                                </div>
                                <Users className="text-green-600" size={24} />
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Μηνιαίες Κρατήσεις</p>
                                    <p className="text-2xl font-bold text-gray-900">0</p>
                                </div>
                                <Calendar className="text-purple-600" size={24} />
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Έσοδα Μήνα</p>
                                    <p className="text-2xl font-bold text-gray-900">€0</p>
                                </div>
                                <CreditCard className="text-orange-600" size={24} />
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions Grid */}
                    <div>
                        <h2 className="text-xl font-semibold mb-4">Γρήγορες Ενέργειες</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            {quickActions.map((action, index) => (
                                <div key={index} className={`p-6 rounded-lg shadow border ${action.color}`}>
                                    <div className="flex items-start justify-between mb-4">
                                        <action.icon className={action.iconColor} size={32} />
                                    </div>
                                    <h3 className="font-semibold mb-2 text-gray-900">{action.title}</h3>
                                    <p className="text-sm text-gray-600 mb-4">{action.description}</p>
                                    <Link
                                        href={action.href}
                                        className={`inline-block px-4 py-2 text-white text-sm rounded transition ${action.buttonColor}`}
                                    >
                                        Μετάβαση
                                    </Link>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Recent Activity */}
                    <div className="bg-white rounded-lg shadow border border-gray-200">
                        <div className="p-6 border-b border-gray-200">
                            <h2 className="text-xl font-semibold">Πρόσφατη Δραστηριότητα</h2>
                        </div>
                        <div className="p-6">
                            <div className="text-center py-8 text-gray-500">
                                <FileText size={48} className="mx-auto mb-4 text-gray-300" />
                                <p>Δεν υπάρχει πρόσφατη δραστηριότητα</p>
                                <p className="text-sm">Η δραστηριότητα θα εμφανιστεί εδώ όταν αρχίσετε να χρησιμοποιείτε το σύστημα</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 