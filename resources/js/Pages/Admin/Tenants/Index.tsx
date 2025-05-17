import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Tenant } from '@/types/models';

interface TenantsIndexProps extends PageProps {
    tenants: {
        data: Tenant[];
        links: any[];
        current_page: number;
        from: number;
        to: number;
        total: number;
    };
}

export default function TenantsIndex({ auth, tenants }: TenantsIndexProps) {
    return (
        <AuthenticatedLayout>
            <Head title="Τουριστικά Γραφεία" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6 flex justify-between items-center">
                        <h3 className="text-lg font-semibold">Λίστα Τουριστικών Γραφείων</h3>
                        <Link
                            href={route('admin.dashboard')}
                            className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition"
                        >
                            Επιστροφή στον Πίνακα
                        </Link>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            {tenants.data.length === 0 ? (
                                <div className="text-center py-8">
                                    <p>Δεν υπάρχουν ακόμα εγγεγραμμένα τουριστικά γραφεία.</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Όνομα
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Email
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Ιδιοκτήτης
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Κατάσταση
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Domains
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Ενέργειες
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {tenants.data.map((tenant) => (
                                                <tr key={tenant.id}>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900">
                                                            {tenant.name}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-500">
                                                            {tenant.email}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {tenant.owner ? (
                                                            <div className="text-sm text-gray-500">
                                                                {tenant.owner.name}
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-red-500">Δεν έχει οριστεί</span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {tenant.is_active ? (
                                                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                                Ενεργό
                                                            </span>
                                                        ) : (
                                                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                                Σε αναμονή έγκρισης
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {tenant.domains_count || 0}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div className="flex space-x-2">
                                                            <Link
                                                                href={route('admin.tenants.show', tenant.id)}
                                                                className="text-blue-600 hover:text-blue-900"
                                                            >
                                                                Προβολή
                                                            </Link>
                                                            <Link
                                                                href={route('admin.tenants.edit', tenant.id)}
                                                                className="text-indigo-600 hover:text-indigo-900"
                                                            >
                                                                Επεξεργασία
                                                            </Link>
                                                            {!tenant.is_active && (
                                                                <Link
                                                                    href={route('admin.tenants.approve', tenant.id)}
                                                                    method="post"
                                                                    as="button"
                                                                    className="text-green-600 hover:text-green-900"
                                                                >
                                                                    Έγκριση
                                                                </Link>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Pagination */}
                            {tenants.data.length > 0 && (
                                <div className="mt-6 flex justify-between items-center">
                                    <div className="text-sm text-gray-600">
                                        Προβολή {tenants.from} έως {tenants.to} από {tenants.total} εγγραφές
                                    </div>
                                    <div className="flex space-x-1">
                                        {tenants.links.map((link, i) => (
                                            <Link
                                                key={i}
                                                href={link.url || '#'}
                                                className={`px-4 py-2 border rounded ${link.active
                                                    ? 'bg-blue-600 text-white border-blue-600'
                                                    : 'bg-white text-gray-700 border-gray-300'
                                                    } ${!link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'}`}
                                                dangerouslySetInnerHTML={{ __html: link.label }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 