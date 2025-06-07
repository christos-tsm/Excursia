import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { useEffect } from 'react';
import { toast, Toaster } from 'react-hot-toast';
import { Download, FileText, Plus, Trash2, ArrowLeft, FileDown } from 'lucide-react';

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
    creation_type?: string;
}

interface Props extends PageProps {
    trip: Trip;
    documents: Document[];
    tenant_id: number;
    success?: string;
    error?: string;
}

export default function Index({ trip, documents, tenant_id, success, error }: Props) {
    // Εμφάνιση μηνυμάτων επιτυχίας ή σφάλματος
    useEffect(() => {
        if (success) {
            toast.success(success);
        }
        if (error) {
            toast.error(error);
        }
    }, [success, error]);

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

    return (
        <AuthenticatedLayout>
            <Head title={`Έγγραφα - ${trip.title}`} />

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
                    <Link
                        href={route('tenant.trips.show', { tenant_id, trip: trip.id })}
                        className="text-primary-500 hover:text-primary-600"
                    >
                        {trip.title}
                    </Link>
                    <span className="text-gray-500">/</span>
                    <span className="text-gray-900">Έγγραφα</span>
                </div>

                <div className="flex justify-between items-center mb-6">
                    <div>
                        <h1 className="text-2xl font-semibold text-gray-900">Έγγραφα Ταξιδιού</h1>
                        <p className="text-gray-600 mt-1">{trip.title} - {trip.destination}</p>
                    </div>
                    <div className="flex items-center space-x-2">
                        <Link
                            href={route('tenant.trip.documents.create-editor', { tenant_id, trip: trip.id })}
                            className="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded flex items-center text-sm"
                        >
                            <FileText className="w-4 h-4 mr-2" />
                            Δημιουργία Εγγράφου
                        </Link>
                        <Link
                            href={route('tenant.trip.documents.create', { tenant_id, trip: trip.id })}
                            className="bg-primary-400 hover:bg-primary-500 text-white py-2 px-4 rounded flex items-center text-sm"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            Ανέβασμα Αρχείου
                        </Link>
                    </div>
                </div>

                {/* Λίστα εγγράφων */}
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    {documents.length === 0 ? (
                        <div className="p-8 text-center text-gray-500">
                            <FileText className="w-12 h-12 mx-auto mb-4 text-gray-300" />
                            <p className="text-lg mb-2">Δεν υπάρχουν έγγραφα</p>
                            <p>Ανεβάστε το πρώτο έγγραφο για αυτό το ταξίδι</p>
                        </div>
                    ) : (
                        <div className="divide-y divide-gray-200">
                            {documents.map((document) => (
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
                                            {/* Κουμπιά export για έγγραφα που δημιουργήθηκαν με editor */}
                                            {document.creation_type === 'editor' && (
                                                <div className="flex items-center space-x-1">
                                                    <a
                                                        href={route('tenant.trip.documents.export', {
                                                            tenant_id,
                                                            trip: trip.id,
                                                            document: document.id,
                                                            format: 'pdf'
                                                        })}
                                                        className="text-red-500 hover:text-red-600 p-2 rounded-md hover:bg-gray-100"
                                                        title="Export PDF"
                                                    >
                                                        <FileDown className="w-4 h-4" />
                                                    </a>
                                                    <a
                                                        href={route('tenant.trip.documents.export', {
                                                            tenant_id,
                                                            trip: trip.id,
                                                            document: document.id,
                                                            format: 'docx'
                                                        })}
                                                        className="text-blue-500 hover:text-blue-600 p-2 rounded-md hover:bg-gray-100"
                                                        title="Export DOCX"
                                                    >
                                                        <FileDown className="w-4 h-4" />
                                                    </a>
                                                </div>
                                            )}

                                            {/* Κουμπί λήψης για uploaded αρχεία */}
                                            {document.creation_type !== 'editor' && (
                                                <a
                                                    href={document.download_url}
                                                    className="text-primary-500 hover:text-primary-600 p-2 rounded-md hover:bg-gray-100"
                                                    title="Λήψη"
                                                >
                                                    <Download className="w-4 h-4" />
                                                </a>
                                            )}

                                            <button
                                                onClick={() => {
                                                    if (confirm('Είστε βέβαιοι ότι θέλετε να διαγράψετε αυτό το έγγραφο;')) {
                                                        router.delete(route('tenant.trip.documents.destroy', {
                                                            tenant_id,
                                                            trip: trip.id,
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
            </div>
            <Toaster position="top-right" />
        </AuthenticatedLayout>
    );
} 