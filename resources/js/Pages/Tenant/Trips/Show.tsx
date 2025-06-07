import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { useEffect } from 'react';
import { toast, Toaster } from 'react-hot-toast';
import {
    ArrowLeft,
    Edit,
    FileText,
    MapPin,
    Calendar,
    Euro,
    Clock,
    Users,
    Plus,
    Download,
    Eye,
    Trash2
} from 'lucide-react';

interface Trip {
    id: number;
    title: string;
    description: string | null;
    destination: string;
    price: number;
    duration: number;
    departure_date: string | null;
    return_date: string | null;
    is_published: boolean;
    created_at: string;
    updated_at: string;
}

interface RecentDocument {
    id: number;
    title: string;
    file_type: string;
    file_icon: string;
    document_type_label: string;
    created_at: string;
    download_url: string;
}

interface Props extends PageProps {
    trip: Trip;
    recentDocuments: RecentDocument[];
    documentsCount: number;
    tenant_id: number;
    success?: string;
    error?: string;
}

export default function Show({ trip, recentDocuments, documentsCount, tenant_id, success, error }: Props) {
    // Εμφάνιση μηνυμάτων επιτυχίας ή σφάλματος
    useEffect(() => {
        if (success) {
            toast.success(success);
        }
        if (error) {
            toast.error(error);
        }
    }, [success, error]);

    const formatDate = (dateString: string | null) => {
        if (!dateString) return 'Δεν έχει οριστεί';
        const date = new Date(dateString);
        return date.toLocaleDateString('el-GR', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('el-GR', {
            style: 'currency',
            currency: 'EUR',
        }).format(price);
    };

    const handleDelete = () => {
        if (confirm('Είστε βέβαιοι ότι θέλετε να διαγράψετε αυτό το ταξίδι; Θα διαγραφούν και όλα τα έγγραφά του.')) {
            router.delete(route('tenant.trips.destroy', { tenant_id, trip: trip.id }));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={trip.title} />

            <div className="py-4 px-8">
                {/* Breadcrumb */}
                <div className="flex items-center space-x-2 mb-6">
                    <Link
                        href={route('tenant.trips.index', { tenant_id })}
                        className="text-primary-500 hover:text-primary-600 flex items-center"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        Ταξίδια
                    </Link>
                    <span className="text-gray-500">/</span>
                    <span className="text-gray-900">{trip.title}</span>
                </div>

                {/* Header */}
                <div className="bg-white rounded-lg shadow mb-6">
                    <div className="px-6 py-4 border-b border-gray-200">
                        <div className="flex items-start justify-between">
                            <div className="flex-1">
                                <div className="flex items-center space-x-3 mb-2">
                                    <h1 className="text-2xl font-bold text-gray-900">{trip.title}</h1>
                                    <span className={`px-3 py-1 text-sm font-medium rounded-full ${trip.is_published
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-yellow-100 text-yellow-800'
                                        }`}>
                                        {trip.is_published ? 'Δημοσιευμένο' : 'Πρόχειρο'}
                                    </span>
                                </div>
                                <div className="flex items-center text-gray-600 mb-3">
                                    <MapPin className="w-4 h-4 mr-1" />
                                    <span>{trip.destination}</span>
                                </div>
                                {trip.description && (
                                    <p className="text-gray-700 mb-4">{trip.description}</p>
                                )}
                            </div>
                            <div className="flex items-center space-x-3 ml-6">
                                <Link
                                    href={route('tenant.trips.edit', { tenant_id, trip: trip.id })}
                                    className="bg-primary-600 hover:bg-primary-700 text-white px-4 py-2 rounded-md flex items-center text-sm"
                                >
                                    <Edit className="w-4 h-4 mr-2" />
                                    Επεξεργασία
                                </Link>
                                <button
                                    onClick={handleDelete}
                                    className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md flex items-center text-sm"
                                >
                                    <Trash2 className="w-4 h-4 mr-2" />
                                    Διαγραφή
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Trip Details */}
                    <div className="px-6 py-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div className="flex items-center space-x-3">
                                <div className="bg-blue-100 p-2 rounded-lg">
                                    <Euro className="w-5 h-5 text-blue-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Τιμή / άτομο</p>
                                    <p className="font-semibold text-gray-900">{formatPrice(trip.price)}</p>
                                </div>
                            </div>

                            <div className="flex items-center space-x-3">
                                <div className="bg-green-100 p-2 rounded-lg">
                                    <Clock className="w-5 h-5 text-green-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Διάρκεια</p>
                                    <p className="font-semibold text-gray-900">{trip.duration} ημέρες</p>
                                </div>
                            </div>

                            <div className="flex items-center space-x-3">
                                <div className="bg-purple-100 p-2 rounded-lg">
                                    <Calendar className="w-5 h-5 text-purple-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Αναχώρηση</p>
                                    <p className="font-semibold text-gray-900 text-sm">{formatDate(trip.departure_date)}</p>
                                </div>
                            </div>

                            <div className="flex items-center space-x-3">
                                <div className="bg-orange-100 p-2 rounded-lg">
                                    <Calendar className="w-5 h-5 text-orange-600" />
                                </div>
                                <div>
                                    <p className="text-sm text-gray-500">Επιστροφή</p>
                                    <p className="font-semibold text-gray-900 text-sm">{formatDate(trip.return_date)}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Documents Section */}
                <div className="bg-white rounded-lg shadow">
                    <div className="px-6 py-4 border-b border-gray-200">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center space-x-3">
                                <FileText className="w-5 h-5 text-gray-600" />
                                <h2 className="text-lg font-semibold text-gray-900">
                                    Έγγραφα ({documentsCount})
                                </h2>
                            </div>
                            <div className="flex items-center space-x-3">
                                <Link
                                    href={route('tenant.trip.documents.create', { tenant_id, trip: trip.id })}
                                    className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center text-sm"
                                >
                                    <Plus className="w-4 h-4 mr-2" />
                                    Ανέβασμα
                                </Link>
                                <Link
                                    href={route('tenant.trip.documents.index', { tenant_id, trip: trip.id })}
                                    className="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md flex items-center text-sm"
                                >
                                    <Eye className="w-4 h-4 mr-2" />
                                    Προβολή όλων
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div className="px-6 py-4">
                        {recentDocuments.length === 0 ? (
                            <div className="text-center py-8">
                                <FileText className="w-12 h-12 mx-auto text-gray-300 mb-4" />
                                <p className="text-gray-500 mb-4">Δεν υπάρχουν έγγραφα για αυτό το ταξίδι</p>
                                <Link
                                    href={route('tenant.trip.documents.create', { tenant_id, trip: trip.id })}
                                    className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md inline-flex items-center"
                                >
                                    <Plus className="w-4 h-4 mr-2" />
                                    Ανέβασμα πρώτου εγγράφου
                                </Link>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                <h3 className="text-sm font-medium text-gray-900 mb-3">Πρόσφατα έγγραφα</h3>
                                {recentDocuments.map((document) => (
                                    <div key={document.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                        <div className="flex items-center space-x-3">
                                            <span className="text-lg">{document.file_icon}</span>
                                            <div>
                                                <p className="font-medium text-gray-900">{document.title}</p>
                                                <div className="flex items-center space-x-3 text-sm text-gray-500">
                                                    <span>{document.document_type_label}</span>
                                                    <span>•</span>
                                                    <span>{document.created_at}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <a
                                            href={document.download_url}
                                            className="text-primary-600 hover:text-primary-700 p-2 rounded-md hover:bg-white"
                                            title="Λήψη"
                                        >
                                            <Download className="w-4 h-4" />
                                        </a>
                                    </div>
                                ))}
                                {documentsCount > 5 && (
                                    <div className="text-center pt-2">
                                        <Link
                                            href={route('tenant.trip.documents.index', { tenant_id, trip: trip.id })}
                                            className="text-primary-600 hover:text-primary-700 text-sm font-medium"
                                        >
                                            Προβολή όλων των {documentsCount} εγγράφων →
                                        </Link>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
            <Toaster position="top-right" />
        </AuthenticatedLayout>
    );
} 