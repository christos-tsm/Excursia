import GuestLayout from '@/Layouts/GuestLayout'
import { Head, Link } from '@inertiajs/react'
import React from 'react'

const Pending = () => {
    return (
        <GuestLayout>
            <Head title="Pending" />
            <div className="flex flex-col items-center justify-center gap-4">
                <h1 className="text-2xl font-bold">Η εγγραφή σας είναι υπό εξέταση.</h1>
                <p className="text-sm text-gray-500">Θα ενημερωθείτε σύντομα για την ολοκλήρωση της εγγραφής σας.</p>
                <p className="text-sm text-gray-500">Σας ευχαριστούμε για την κατανόηση.</p>
                <Link href={route('welcome')} className="text-sm text-primary-400 underline">Πατήστε εδώ για να επιστρέψετε στην σελίδα είσοδου.</Link>
            </div>
        </GuestLayout>
    )
}

export default Pending