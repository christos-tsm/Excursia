import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function TenantDashboard({ auth }: PageProps) {
    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Πίνακας Εργασιών Γραφείου</h2>}
        >
            <Head title="Tenant Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h3 className="text-lg font-semibold mb-4">Καλωσόρισατε στο Διαχειριστικό σας, {auth.user.name}!</h3>

                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                                <div className="p-4 bg-blue-50 dark:bg-blue-900 rounded-lg shadow">
                                    <h4 className="font-semibold mb-2">Εκδρομές & Ταξίδια</h4>
                                    <p className="mb-4 text-sm">Διαχειριστείτε τις εκδρομές και τα ταξίδια του γραφείου σας.</p>
                                    <a
                                        href="#"
                                        className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                                    >
                                        Διαχείριση Ταξιδιών
                                    </a>
                                </div>

                                <div className="p-4 bg-green-50 dark:bg-green-900 rounded-lg shadow">
                                    <h4 className="font-semibold mb-2">Κρατήσεις</h4>
                                    <p className="mb-4 text-sm">Προβολή και διαχείριση κρατήσεων από τους πελάτες σας.</p>
                                    <a
                                        href="#"
                                        className="inline-block px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition"
                                    >
                                        Προβολή Κρατήσεων
                                    </a>
                                </div>

                                <div className="p-4 bg-purple-50 dark:bg-purple-900 rounded-lg shadow">
                                    <h4 className="font-semibold mb-2">Προφίλ Γραφείου</h4>
                                    <p className="mb-4 text-sm">Επεξεργαστείτε τα στοιχεία και το προφίλ του γραφείου σας.</p>
                                    <a
                                        href="#"
                                        className="inline-block px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700 transition"
                                    >
                                        Επεξεργασία Προφίλ
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