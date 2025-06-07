import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { FormEvent, useState, useEffect } from 'react';
import { toast, Toaster } from 'react-hot-toast';
import { Download, FileText, Search, Trash2, Filter, MapPin } from 'lucide-react';

interface Trip {
    id: number;
    title: string;
    destination: string;
}

interface Document {
    id: number;
    title: string;
    description: string | null;
    file_name: string;
    file_type: string;
    file_size: string;
    file_icon: string;
    document_type: string;
    document_type_label: string;
    is_public: boolean;
    uploaded_by: string;
    created_at: string;
    download_url: string;
    trip: Trip;
}

interface Props extends PageProps {
    documents: {
        data: Document[];
        links: any[];
        total: number;
    };
    trips: Trip[];
    tenant_id: number;
    filters: {
        search: string;
        trip_id: string;
        document_type: string;
    };
    success?: string;
    error?: string;
}

export default function Index({ documents, trips, tenant_id, filters, success, error }: Props) {
    const [searchTerm, setSearchTerm] = useState(filters.search);
    const [tripFilter, setTripFilter] = useState(filters.trip_id);
    const [documentTypeFilter, setDocumentTypeFilter] = useState(filters.document_type);

    // Εμφάνιση μηνυμάτων επιτυχίας ή σφάλματος
    useEffect(() => {
        if (success) {
            toast.success(success);
        }
        if (error) {
            toast.error(error);
        }
    }, [success, error]);

    const handleSearch = (e: FormEvent) => {
        e.preventDefault();
        router.get(route('tenant.documents.index', { tenant_id }), {
            search: searchTerm,
            trip_id: tripFilter,
            document_type: documentTypeFilter,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        setSearchTerm('');
        setTripFilter('');
        setDocumentTypeFilter('');
        router.get(route('tenant.documents.index', { tenant_id }), {}, {
            preserveState: true,
            replace: true,
        });
    };

    const getDocumentTypeColor = (type: string) => {
        switch (type) {
            case 'manual':
                return 'bg-blue-100 text-blue-800';
            case 'program':
                return 'bg-green-100 text-green-800';
            case 'notes':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const hasActiveFilters = filters.search || filters.trip_id || filters.document_type;

    return (
        <AuthenticatedLayout>
            <Head title="Όλα τα Έγγραφα" />

            <div className="py-4 px-8">
                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900">Όλα τα Έγγραφα</h1>
                        <p className="text-gray-600 mt-1">
                            {documents.total} έγγραφα συνολικά
                            {hasActiveFilters && ` (${documents.data.length} φιλτραρισμένα)`}
                        </p>
                    </div>
                </div>

                {/* Φίλτρα */}
                <div className="bg-white p-6 rounded-lg shadow mb-6">
                    <form onSubmit={handleSearch} className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            {/* Αναζήτηση */}
                            <div className="relative">
                                <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <Search className="h-5 w-5 text-gray-400" />
                                </div>
                                <input
                                    type="text"
                                    placeholder="Αναζήτηση στα έγγραφα..."
                                    className="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>

                            {/* Φίλτρο Ταξιδιού */}
                            <select
                                className="border border-gray-300 rounded-md px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500"
                                value={tripFilter}
                                onChange={(e) => setTripFilter(e.target.value)}
                            >
                                <option value="">Όλα τα ταξίδια</option>
                                {trips.map((trip) => (
                                    <option key={trip.id} value={trip.id}>
                                        {trip.title} - {trip.destination}
                                    </option>
                                ))}
                            </select>

                            {/* Φίλτρο Τύπου */}
                            <select
                                className="border border-gray-300 rounded-md px-3 py-2 bg-white focus:ring-blue-500 focus:border-blue-500"
                                value={documentTypeFilter}
                                onChange={(e) => setDocumentTypeFilter(e.target.value)}
                            >
                                <option value="">Όλοι οι τύποι</option>
                                <option value="manual">📘 Εγχειρίδια</option>
                                <option value="program">📅 Προγράμματα</option>
                                <option value="notes">📝 Σημειώσεις</option>
                                <option value="other">📎 Άλλα</option>
                            </select>

                            {/* Κουμπιά */}
                            <div className="flex space-x-2">
                                <button
                                    type="submit"
                                    className="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center flex-1"
                                >
                                    <Filter className="w-4 h-4 mr-2" />
                                    Φιλτράρισμα
                                </button>
                                {hasActiveFilters && (
                                    <button
                                        type="button"
                                        onClick={clearFilters}
                                        className="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md"
                                    >
                                        Καθαρισμός
                                    </button>
                                )}
                            </div>
                        </div>
                    </form>
                </div>

                {/* Λίστα εγγράφων */}
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    {documents.data.length === 0 ? (
                        <div className="p-8 text-center text-gray-500">
                            <FileText className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                            <p className="text-lg mb-2">
                                {hasActiveFilters ? 'Δεν βρέθηκαν έγγραφα με αυτά τα κριτήρια' : 'Δεν υπάρχουν έγγραφα'}
                            </p>
                            {hasActiveFilters ? (
                                <button
                                    onClick={clearFilters}
                                    className="text-blue-600 hover:text-blue-700"
                                >
                                    Καθαρισμός φίλτρων
                                </button>
                            ) : (
                                <p>Δημιουργήστε ένα ταξίδι και ανεβάστε έγγραφα</p>
                            )}
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-200">
                            {documents.data.map((document) => (
                                <div key={document.id} className="p-6 hover:bg-gray-50">
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-start space-x-4 flex-1">
                                            <div className="text-2xl">{document.file_icon}</div>
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center space-x-3 mb-2">
                                                    <h3 className="text-lg font-medium text-gray-900 truncate">
                                                        {document.title}
                                                    </h3>
                                                    <span className={`px-2 py-1 text-xs font-medium rounded-full ${getDocumentTypeColor(document.document_type)}`}>
                                                        {document.document_type_label}
                                                    </span>
                                                    {document.is_public && (
                                                        <span className="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                                            Δημόσιο
                                                        </span>
                                                    )}
                                                </div>

                                                {/* Trip Info */}
                                                <div className="flex items-center space-x-2 mb-2">
                                                    <MapPin className="w-4 h-4 text-gray-400" />
                                                    <Link
                                                        href={route('tenant.trip.documents.index', {
                                                            tenant_id,
                                                            trip: document.trip.id
                                                        })}
                                                        className="text-primary-600 hover:text-primary-700 font-medium text-sm"
                                                    >
                                                        {document.trip.title} - {document.trip.destination}
                                                    </Link>
                                                </div>

                                                {document.description && (
                                                    <p className="text-gray-600 mb-2">{document.description}</p>
                                                )}
                                                <div className="flex items-center space-x-4 text-sm text-gray-500">
                                                    <span>{document.file_name}</span>
                                                    <span>•</span>
                                                    <span>{document.file_size}</span>
                                                    <span>•</span>
                                                    <span>Ανέβηκε από {document.uploaded_by}</span>
                                                    <span>•</span>
                                                    <span>{document.created_at}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center space-x-2 ml-4">
                                            <a
                                                href={document.download_url}
                                                className="text-primary-500 hover:text-primary-600 p-2 rounded-md hover:bg-gray-100"
                                                title="Λήψη"
                                            >
                                                <Download className="w-4 h-4" />
                                            </a>
                                            <button
                                                onClick={() => {
                                                    if (confirm('Είστε βέβαιοι ότι θέλετε να διαγράψετε αυτό το έγγραφο;')) {
                                                        router.delete(route('tenant.trip.documents.destroy', {
                                                            tenant_id,
                                                            trip: document.trip.id,
                                                            document: document.id
                                                        }));
                                                    }
                                                }}
                                                className="text-red-500 hover:text-red-600 p-2 rounded-md hover:bg-gray-100"
                                                title="Διαγραφή"
                                            >
                                                <Trash2 className="w-4 h-4" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {documents.links && documents.links.length > 3 && (
                    <div className="mt-6 flex justify-center">
                        <div className="flex space-x-1">
                            {documents.links.map((link, index) => (
                                <div key={index}>
                                    {!link.url ? (
                                        <span className="px-4 py-2 text-gray-500">{link.label}</span>
                                    ) : (
                                        <Link
                                            href={link.url}
                                            className={`px-4 py-2 rounded ${link.active ? 'bg-blue-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-100'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    )}
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
            <Toaster position="top-right" />
        </AuthenticatedLayout>
    );
} 