import { Link, Head } from '@inertiajs/react';
import { Tenant } from '@/types/models';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { getCurrentTenantDomain } from '@/Utils/tenant';
import {
    MapPin,
    Calendar,
    Clock,
    Users,
    Navigation,
    Briefcase,
    Star,
    Camera
} from 'lucide-react';

interface GuideDashboardProps {
    tenant: Tenant;
    userName: string;
}

export default function GuideDashboard({ tenant, userName }: GuideDashboardProps) {
    const domain = getCurrentTenantDomain();

    const guideActions = [
        {
            title: 'Τα Ταξίδια μου',
            description: 'Προβολή των ταξιδιών που έχετε ανατεθεί ως ξεναγός',
            icon: MapPin,
            href: domain ? `/tenant/${domain}/trips` : '#',
            color: 'bg-blue-50 border-blue-200',
            iconColor: 'text-blue-600',
            buttonColor: 'bg-blue-600 hover:bg-blue-700'
        },
        {
            title: 'Πρόγραμμα Εβδομάδας',
            description: 'Δείτε το πρόγραμμά σας για την επόμενη εβδομάδα',
            icon: Calendar,
            href: '#',
            color: 'bg-green-50 border-green-200',
            iconColor: 'text-green-600',
            buttonColor: 'bg-green-600 hover:bg-green-700'
        },
        {
            title: 'Διαχείριση Ομάδων',
            description: 'Διαχειριστείτε τις ομάδες των ταξιδιωτών σας',
            icon: Users,
            href: '#',
            color: 'bg-purple-50 border-purple-200',
            iconColor: 'text-purple-600',
            buttonColor: 'bg-purple-600 hover:bg-purple-700'
        },
        {
            title: 'Πόροι & Υλικό',
            description: 'Υλικό ξενάγησης και χρήσιμες πληροφορίες',
            icon: Briefcase,
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
                    <div className="bg-gradient-to-r from-green-600 to-blue-600 rounded-lg p-6 text-white">
                        <h1 className="text-2xl font-bold mb-2">
                            Καλώς ήρθατε, {userName}!
                        </h1>
                        <p className="text-green-100 text-lg">
                            Ξεναγός στο "{tenant.name}"
                        </p>
                        <div className="mt-4 flex items-center gap-2">
                            <Star size={16} />
                            <span className="text-sm">Ετοιμοι για νέες περιπέτειες;</span>
                        </div>
                    </div>

                    {/* Today's Overview */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Σημερινά Ταξίδια</p>
                                    <p className="text-2xl font-bold text-gray-900">0</p>
                                </div>
                                <Calendar className="text-blue-600" size={24} />
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Ταξιδιώτες Σήμερα</p>
                                    <p className="text-2xl font-bold text-gray-900">0</p>
                                </div>
                                <Users className="text-green-600" size={24} />
                            </div>
                        </div>
                        <div className="bg-white p-4 rounded-lg shadow border border-gray-200">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Επόμενο Ταξίδι</p>
                                    <p className="text-lg font-bold text-gray-900">-</p>
                                </div>
                                <Clock className="text-purple-600" size={24} />
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions for Guides */}
                    <div>
                        <h2 className="text-xl font-semibold mb-4">Οι Δραστηριότητές μου</h2>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {guideActions.map((action, index) => (
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

                    {/* Today's Schedule */}
                    <div className="bg-white rounded-lg shadow border border-gray-200">
                        <div className="p-6 border-b border-gray-200">
                            <h2 className="text-xl font-semibold flex items-center gap-2">
                                <Calendar size={20} />
                                Το Πρόγραμμά μου Σήμερα
                            </h2>
                        </div>
                        <div className="p-6">
                            <div className="text-center py-8 text-gray-500">
                                <Navigation size={48} className="mx-auto mb-4 text-gray-300" />
                                <p className="font-medium">Δεν έχετε προγραμματισμένα ταξίδια σήμερα</p>
                                <p className="text-sm">Απολαύστε την ημέρα σας!</p>
                            </div>
                        </div>
                    </div>

                    {/* Quick Tips */}
                    <div className="bg-white rounded-lg shadow border border-gray-200">
                        <div className="p-6 border-b border-gray-200">
                            <h2 className="text-xl font-semibold flex items-center gap-2">
                                <Star size={20} />
                                Συμβουλές για Ξεναγούς
                            </h2>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="flex gap-3">
                                    <Camera className="text-blue-500 mt-1" size={20} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Φωτογραφίες</h4>
                                        <p className="text-sm text-gray-600">Τραβήξτε φωτογραφίες των σημαντικών στιγμών</p>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <Users className="text-green-500 mt-1" size={20} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Επικοινωνία</h4>
                                        <p className="text-sm text-gray-600">Διατηρήστε επαφή με την ομάδα σας</p>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <Clock className="text-orange-500 mt-1" size={20} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Χρονοδιάγραμμα</h4>
                                        <p className="text-sm text-gray-600">Τηρήστε τους χρόνους του προγράμματος</p>
                                    </div>
                                </div>
                                <div className="flex gap-3">
                                    <Star className="text-purple-500 mt-1" size={20} />
                                    <div>
                                        <h4 className="font-medium text-gray-900">Εμπειρία</h4>
                                        <p className="text-sm text-gray-600">Δημιουργήστε αξέχαστες εμπειρίες</p>
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