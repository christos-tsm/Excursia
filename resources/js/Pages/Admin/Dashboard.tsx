import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { PageProps } from '@/types';
import AdminLayout from '@/Layouts/AdminLayout';

export default function AdminDashboard({ auth }: PageProps) {
    return (
        <AdminLayout>
            <Head title="Πίνακας Διαχείρισης" />
            <div className="p-4">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6">
                        <h3 className="text-lg font-semibold mb-4">Καλώς ήρθατε, {auth.user.name}!</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
                            <div className="p-4 bg-blue-50 rounded-lg shadow">
                                <h4 className="font-semibold mb-2">Διαχείριση Τουριστικών Γραφείων</h4>
                                <p className="mb-4 text-sm">Προβολή, έγκριση, και διαχείριση των τουριστικών γραφείων στην πλατφόρμα.</p>
                                <Link
                                    href={route('admin.tenants.index')}
                                    className="inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                                >
                                    Προβολή Γραφείων
                                </Link>
                            </div>

                            <div className="p-4 bg-green-50 rounded-lg shadow">
                                <h4 className="font-semibold mb-2">Εκκρεμείς Εγκρίσεις</h4>
                                <p className="mb-4 text-sm">Νέα τουριστικά γραφεία που περιμένουν έγκριση από τους διαχειριστές.</p>
                                <Link
                                    href="#!"
                                    className="inline-block px-4 py-2 bg-green-600 text-white rounded transition"
                                >
                                    Προβολή Εκκρεμοτήτων
                                </Link>
                            </div>

                            <div className="p-4 bg-purple-50 rounded-lg shadow">
                                <h4 className="font-semibold mb-2">Στατιστικά Πλατφόρμας</h4>
                                <p className="mb-4 text-sm">Προβολή στατιστικών στοιχείων και analytics για την πλατφόρμα.</p>
                                <Link
                                    href="#!"
                                    className="inline-block px-4 py-2 bg-purple-600 text-white rounded transition"
                                >
                                    Προβολή Στατιστικών
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
} 