import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Tenant } from '@/types/models';
import { getCurrentTenantDomain } from '@/Utils/tenant';
import {
    Eye,
    Calendar,
    Clock,
    Phone,
    MessageCircle,
    Users,
    FileText,
    CheckCircle,
    AlertCircle
} from 'lucide-react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';

interface StaffDashboardProps {
    tenant: Tenant;
    userName: string;
}

export default function StaffDashboard({ tenant, userName }: StaffDashboardProps) {
    const domain = getCurrentTenantDomain();

    const staffActions = [
        {
            title: 'Προβολή Ταξιδιών',
            description: 'Δείτε πληροφορίες για τα τρέχοντα ταξίδια',
            icon: Eye,
            href: domain ? `/tenant/${domain}/trips` : '#',
            color: 'bg-blue-50 border-blue-200',
            iconColor: 'text-blue-600',
            buttonColor: 'bg-blue-600 hover:bg-blue-700'
        },
        {
            title: 'Καθήκοντα & Εργασίες',
            description: 'Τα καθήκοντά σας για σήμερα',
            icon: CheckCircle,
            href: '#',
            color: 'bg-green-50 border-green-200',
            iconColor: 'text-green-600',
            buttonColor: 'bg-green-600 hover:bg-green-700'
        },
        {
            title: 'Επικοινωνία',
            description: 'Στείλτε μηνύματα στην ομάδα',
            icon: MessageCircle,
            href: '#',
            color: 'bg-purple-50 border-purple-200',
            iconColor: 'text-purple-600',
            buttonColor: 'bg-purple-600 hover:bg-purple-700'
        },
        {
            title: 'Αναφορές',
            description: 'Υποβάλετε αναφορές και ενημερώσεις',
            icon: FileText,
            href: '#',
            color: 'bg-orange-50 border-orange-200',
            iconColor: 'text-orange-600',
            buttonColor: 'bg-orange-600 hover:bg-orange-700'
        }
    ];

    return (
        <AuthenticatedLayout>
            <Head title={`Dashboard - ${tenant.name}`} />
            <div className="p-4">
                <div className="space-y-6">
                    {/* Welcome Header */}
                    <div className="bg-gradient-to-r from-purple-600 to-pink-600 rounded-lg p-6 text-white">
                        <h1 className="text-2xl font-bold mb-2">
                            Καλώς ήρθατε, {userName}!
                        </h1>
                        <p className="text-purple-100 text-lg">
                            Μέλος προσωπικού στο "{tenant.name}"
                        </p>
                        <div className="mt-4 flex items-center gap-2">
                            <CheckCircle size={16} />
                            <span className="text-sm">Έτοιμοι για δουλειά;</span>
                        </div>
                    </div>

                    {/* Today's Overview */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Σημερινά Καθήκοντα</p>
                                    <p className="text-2xl font-bold text-gray-900">0</p>
                                </div>
                                <CheckCircle className="text-green-600" size={24} />
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Ενεργά Ταξίδια</p>
                                    <p className="text-2xl font-bold text-gray-900">0</p>
                                </div>
                                <Eye className="text-blue-600" size={24} />
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Μηνύματα</p>
                                    <p className="text-2xl font-bold text-gray-900">0</p>
                                </div>
                                <MessageCircle className="text-purple-600" size={24} />
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions for Staff */}
                    <div>
                        <h2 className="text-xl font-semibold mb-4">Γρήγορες Ενέργειες</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {staffActions.map((action, index) => (
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
                                        Άνοιγμα
                                    </Link>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Today's Tasks */}
                    <div className="bg-white rounded-lg shadow border border-gray-200">
                        <div className="p-6 border-b border-gray-200">
                            <h2 className="text-xl font-semibold flex items-center gap-2">
                                <CheckCircle size={20} />
                                Καθήκοντα Σήμερα
                            </h2>
                        </div>
                        <div className="p-6">
                            <div className="text-center py-8 text-gray-500">
                                <CheckCircle size={48} className="mx-auto mb-4 text-gray-300" />
                                <p className="font-medium">Δεν έχετε καθήκοντα για σήμερα</p>
                                <p className="text-sm">Τα νέα καθήκοντα θα εμφανιστούν εδώ</p>
                            </div>
                        </div>
                    </div>

                    {/* Important Contact Info */}
                    <div className="bg-white rounded-lg shadow border border-gray-200">
                        <div className="p-6 border-b border-gray-200">
                            <h2 className="text-xl font-semibold flex items-center gap-2">
                                <Phone size={20} />
                                Σημαντικές Επαφές
                            </h2>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="flex gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                    <Phone className="text-blue-600 mt-1" size={20} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Γραφείο</h4>
                                        <p className="text-sm text-gray-600">{tenant.phone || 'Δεν έχει οριστεί'}</p>
                                    </div>
                                </div>
                                <div className="flex gap-3 p-4 bg-green-50 rounded-lg border border-green-200">
                                    <AlertCircle className="text-green-600 mt-1" size={20} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Έκτακτα</h4>
                                        <p className="text-sm text-gray-600">112</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Working Guidelines */}
                    <div className="bg-white rounded-lg shadow border border-gray-200">
                        <div className="p-6 border-b border-gray-200">
                            <h2 className="text-xl font-semibold flex items-center gap-2">
                                <FileText size={20} />
                                Οδηγίες Εργασίας
                            </h2>
                        </div>
                        <div className="p-6">
                            <div className="space-y-4">
                                <div className="flex gap-3">
                                    <Clock className="text-blue-500 mt-1 flex-shrink-0" size={16} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Ωράριο Εργασίας</h4>
                                        <p className="text-sm text-gray-600">Τηρήστε τα καθορισμένα ωράρια και ενημερώστε για αλλαγές</p>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <Users className="text-green-500 mt-1 flex-shrink-0" size={16} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Συνεργασία</h4>
                                        <p className="text-sm text-gray-600">Διατηρήστε καλή επικοινωνία με την ομάδα</p>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <CheckCircle className="text-purple-500 mt-1 flex-shrink-0" size={16} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Ολοκλήρωση Εργασιών</h4>
                                        <p className="text-sm text-gray-600">Σημειώστε ως ολοκληρωμένες τις εργασίες σας</p>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <MessageCircle className="text-orange-500 mt-1 flex-shrink-0" size={16} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Αναφορά Προβλημάτων</h4>
                                        <p className="text-sm text-gray-600">Ενημερώστε άμεσα για οποιοδήποτε πρόβλημα</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 