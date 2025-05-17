import React from 'react';
import { Head, Link } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';

interface Props {
    tenant: {
        name: string;
        email: string;
    };
}

export default function RegisterSuccess({ tenant }: Props) {
    return (
        <GuestLayout>
            <Head title="Επιτυχής Εγγραφή" />

            <div className="text-center">
                <div className="mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-20 w-20 mx-auto text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <h1 className="text-2xl font-semibold mb-4">Η εγγραφή σας ολοκληρώθηκε!</h1>

                <p className="mb-6 text-gray-600">
                    Ευχαριστούμε για την εγγραφή της επιχείρησής σας <strong>{tenant.name}</strong>.
                    Η αίτησή σας έχει ληφθεί και βρίσκεται υπό έγκριση από τους διαχειριστές μας.
                </p>

                <div className="bg-slate-50 p-4 rounded mb-6 text-center">
                    <p className="text-sm text-gray-600 mb-2">
                        Θα λάβετε ένα email στο <strong>{tenant.email}</strong> όταν η αίτησή σας εγκριθεί.
                    </p>
                    <p className="text-sm text-gray-600">
                        Μετά την έγκριση, θα μπορείτε να συνδεθείτε στο σύστημα διαχείρισης του τουριστικού σας γραφείου.
                    </p>
                </div>

                <div className="mt-6">
                    <Link
                        href={route('login')}
                        className="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Επιστροφή στην αρχική
                    </Link>
                </div>
            </div>
        </GuestLayout>
    );
} 