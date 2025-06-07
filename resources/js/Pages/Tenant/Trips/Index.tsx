import { Head, Link, router } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { FormEvent, useState, useEffect } from 'react';
import { Toaster, toast } from 'react-hot-toast';
import { Edit, Plus, Search, Trash2 } from 'lucide-react';
import SelectInput from '@/Components/SelectInput';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';

interface Trip {
    id: number;
    title: string;
    destination: string;
    price: number;
    duration: number;
    is_published: boolean;
    departure_date: string | null;
    return_date: string | null;
}

interface Props extends PageProps {
    trips: {
        data: Trip[];
        links: any[];
        total: number;
    };
    tenant_id: number;
    filters: {
        search: string;
        status: string;
    };
    success?: string;
    error?: string;
}

export default function Index({ auth, trips, tenant_id, filters, success, error }: Props) {
    const [searchTerm, setSearchTerm] = useState(filters.search);
    const [statusFilter, setStatusFilter] = useState(filters.status);

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
        router.get(route('tenant.trips.index', { tenant_id }), {
            search: searchTerm,
            status: statusFilter,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const formatDate = (dateString: string | null) => {
        if (!dateString) return '-';
        const date = new Date(dateString);
        return date.toLocaleDateString('el-GR');
    };

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('el-GR', {
            style: 'currency',
            currency: 'EUR',
        }).format(price);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Διαχείριση Ταξιδιών" />

            <div className="py-4 px-8">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-2xl font-semibold text-gray-900">Ταξίδια</h1>
                    <Link href={route('tenant.trips.create', { tenant_id })} className="bg-primary-400 text-sm hover:bg-primary-500 text-white py-2 px-4 rounded flex items-center">
                        <Plus className="w-4 h-4 mr-2" />
                        Προσθήκη
                    </Link>
                </div>

                {/* Φίλτρα αναζήτησης */}
                <div className="bg-white p-4 rounded shadow mb-6">
                    <form onSubmit={handleSearch} className="flex gap-4">
                        <div className="relative flex-grow">
                            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <Search className="h-5 w-5 text-gray-400" />
                            </div>
                            <TextInput
                                type="text"
                                placeholder="Αναζήτηση..."
                                className="pl-10 pr-4 py-2 w-full border rounded focus:ring-blue-500 focus:border-blue-500"
                                value={searchTerm}
                                onChange={(e) => setSearchTerm(e.target.value)}
                            />
                        </div>
                        <SelectInput
                            className="border rounded px-4 py-2 bg-white focus:ring-blue-500 focus:border-blue-500"
                            value={statusFilter}
                            onChange={(e) => setStatusFilter(e.target.value)}
                        >
                            <option value="">Όλα</option>
                            <option value="published">Δημοσιευμένα</option>
                            <option value="draft">Πρόχειρα</option>
                        </SelectInput>
                        <PrimaryButton
                            type="submit"
                            className="bg-gray-100 hover:bg-gray-200 py-2 px-4 rounded"
                        >
                            Φιλτράρισμα
                        </PrimaryButton>
                    </form>
                </div>

                {/* Πίνακας ταξιδιών */}
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Τίτλος</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Προορισμός</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Τιμή</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Διάρκεια</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Αναχώρηση</th>
                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Κατάσταση</th>
                                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Ενέργειες</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {trips.data.length === 0 ? (
                                <tr>
                                    <td colSpan={7} className="px-6 py-4 text-center text-gray-500">
                                        Δεν βρέθηκαν ταξίδια
                                    </td>
                                </tr>
                            ) : (
                                trips.data.map((trip) => (
                                    <tr key={trip.id}>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <Link
                                                href={route('tenant.trips.show', { tenant_id, trip: trip.id })}
                                                className="text-primary-500 font-medium hover:underline"
                                            >
                                                {trip.title}
                                            </Link>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">{trip.destination}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">{formatPrice(trip.price)}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">{trip.duration} ημέρες</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">{formatDate(trip.departure_date)}</td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm">
                                            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${trip.is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                                                {trip.is_published ? 'Δημοσιευμένο' : 'Πρόχειρο'}
                                            </span>
                                        </td>
                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-sm font-medium space-x-2">
                                            <Link
                                                href={route('tenant.trips.edit', { tenant_id, trip: trip.id })}
                                                className="text-primary-400 hover:text-primary-500 inline-flex items-center"
                                            >
                                                <Edit className="w-4 h-4 mr-1" />
                                            </Link>
                                            <button
                                                onClick={() => {
                                                    if (confirm('Είστε βέβαιοι ότι θέλετε να διαγράψετε αυτό το ταξίδι;')) {
                                                        router.delete(route('tenant.trips.destroy', { tenant_id, trip: trip.id }));
                                                    }
                                                }}
                                                className="text-red-600 hover:text-red-900 inline-flex items-center"
                                            >
                                                <Trash2 className="w-4 h-4 mr-1" />
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                {/* Pagination Links */}
                {trips.links && trips.links.length > 3 && (
                    <div className="mt-4 flex justify-center">
                        <div className="flex space-x-1">
                            {trips.links.map((link, index) => (
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