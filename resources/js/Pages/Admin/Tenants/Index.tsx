import AdminLayout from '@/Layouts/AdminLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { Tenant } from '@/types/models';
import { ArrowLeft, Check, Eye, Pencil, X } from 'lucide-react';
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import SelectInput from '@/Components/SelectInput';
import Message from '@/Components/common/Message';

interface TenantsIndexProps extends PageProps {
    tenants: {
        data: Tenant[];
        links: any[];
        current_page: number;
        from: number;
        to: number;
        total: number;
    };
    filters: {
        search: string;
        status: string;
    };
    success?: string;
    error?: string;
}

// Επέκταση του PageProps τύπου για τα flash μηνύματα
interface PagePropsWithFlash extends PageProps {
    flash: {
        message?: string;
        error?: string;
    };
}

export default function TenantsIndex({ auth, tenants, filters }: TenantsIndexProps) {
    const page = usePage();
    const success = page.props.success as string | undefined;
    const error = page.props.error as string | undefined;

    return (
        <AdminLayout>
            <Head title="Τουριστικά Γραφεία" />
            <div className="p-4">
                {success && (
                    <Message message={success} type='success' className='mb-4' />
                )}

                {error && (
                    <Message message={error} type='error' className='mb-4' />
                )}

                <div className="mb-6 flex gap-4 items-center">
                    <Link
                        href={route('admin.dashboard')}
                        className="px-4 py-2 text-sm inline-flex gap-2 items-center transition bg-primary-300 rounded-md hover:bg-primary-400 text-white"
                    >
                        <ArrowLeft size={16} strokeWidth={1.5} />
                        Επιστροφή
                    </Link>
                    <h3 className="text-lg font-semibold">Λίστα Τουριστικών Γραφείων</h3>

                </div>

                {/* Φόρμα Φίλτρων */}
                <div className="bg-white p-4 mb-4 shadow-sm sm:rounded-lg">
                    <form className="flex flex-wrap gap-4">
                        <div className="flex-1 min-w-[200px]">
                            <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
                                Αναζήτηση με όνομα/email
                            </label>
                            <TextInput
                                type="text"
                                name="search"
                                id="search"
                                defaultValue={filters.search}
                                className="w-full rounded-md "
                                placeholder="Αναζήτηση..."
                            />
                        </div>

                        <div className="flex-1 min-w-[200px]">
                            <label htmlFor="status" className="block text-sm font-medium mb-1">
                                Κατάσταση
                            </label>
                            <SelectInput
                                id="status"
                                name="status"
                                defaultValue={filters.status}
                                className="w-full"
                            >
                                <option value="">Όλες οι καταστάσεις</option>
                                <option value="approved">Εγκεκριμένα</option>
                                <option value="pending">Σε αναμονή έγκρισης</option>
                            </SelectInput>
                        </div>

                        <div className="flex items-end">
                            <SecondaryButton
                                type="submit"
                                className="h-10 px-4 py-2 text-sm font-medium text-white bg-primary-500 border border-transparent rounded-md shadow-sm hover:bg-primary-600 focus:outline-none"
                            >
                                Φιλτράρισμα
                            </SecondaryButton>
                            {(filters.search || filters.status) && (
                                <Link
                                    href={route('admin.tenants.index')}
                                    className="ml-2 h-10 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-200 border border-transparent rounded-md shadow-sm hover:bg-gray-300 focus:outline-none"
                                >
                                    Επαναφορά
                                </Link>
                            )}
                        </div>
                    </form>
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
                                                            className="text-primary-500 hover:text-primary-500"
                                                        >
                                                            <Eye size={16} strokeWidth={1.5} />
                                                        </Link>
                                                        <Link
                                                            href={route('admin.tenants.edit', tenant.id)}
                                                            className="text-primary-500 hover:text-primary-500"
                                                        >
                                                            <Pencil size={16} strokeWidth={1.5} />
                                                        </Link>
                                                        {tenant.is_active ?
                                                            <Link
                                                                href={route('admin.tenants.reject', tenant.id)}
                                                                method="post"
                                                                as="button"
                                                                className="text-red-600 hover:text-red-900"
                                                            >
                                                                <X size={16} strokeWidth={1.5} />
                                                            </Link>
                                                            :
                                                            <Link
                                                                href={route('admin.tenants.approve', tenant.id)}
                                                                method="post"
                                                                as="button"
                                                                className="text-green-600 hover:text-green-900"
                                                            >
                                                                <Check size={16} strokeWidth={1.5} />
                                                            </Link>}
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
                                            className={`px-4 py-2 border rounded duration-300 overflow-hidden text-sm ${link.active
                                                ? 'bg-typo-300 text-white border-typo-300'
                                                : 'bg-white text-typo-300 border-gray-300 hover:!bg-gray-200'
                                                } ${!link.url ? 'opacity-50 cursor-not-allowed' : 'hover:bg-typo-400'}`}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
} 