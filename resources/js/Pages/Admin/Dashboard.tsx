import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function AdminDashboard({ auth }: PageProps) {
    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Πίνακας Διαχείρισης</h2>}
        >
            <Head title="Admin Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h3 className="text-lg font-semibold mb-4">Καλώς ήρθατε, {auth.user.name}!</h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                                <div className="p-4 bg-blue-50 dark:bg-blue-900 rounded-lg shadow">
                                    <h4 className="font-semibold mb-2">Διαχείριση Τουριστικών Γραφείων</h4>
                                    <p className="mb-4 text-sm">Προβολή, έγκριση, και διαχείριση των τουριστικών γραφείων στην πλατφόρμα.</p>
                                    <a
                                        href={route('admin.tenants.index')}
                                        className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                                    >
                                        Προβολή Γραφείων
                                    </a>
                                </div>

                                <div className="p-4 bg-green-50 dark:bg-green-900 rounded-lg shadow">
                                    <h4 className="font-semibold mb-2">Εκκρεμείς Εγκρίσεις</h4>
                                    <p className="mb-4 text-sm">Νέα τουριστικά γραφεία που περιμένουν έγκριση από τους διαχειριστές.</p>
                                    <a
                                        href="#"
                                        className="inline-block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition"
                                    >
                                        Προβολή Εκκρεμοτήτων
                                    </a>
                                </div>

                                <div className="p-4 bg-purple-50 dark:bg-purple-900 rounded-lg shadow">
                                    <h4 className="font-semibold mb-2">Στατιστικά Πλατφόρμας</h4>
                                    <p className="mb-4 text-sm">Προβολή στατιστικών στοιχείων και analytics για την πλατφόρμα.</p>
                                    <a
                                        href="#"
                                        className="inline-block px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition"
                                    >
                                        Προβολή Στατιστικών
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 