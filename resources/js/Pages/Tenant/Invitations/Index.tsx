import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import { useEffect } from 'react';
import { getCurrentTenantDomain } from '@/Utils/tenant';

interface Invitation {
    id: number;
    email: string;
    name: string | null;
    role: string;
    token: string;
    invited_by: number;
    accepted_at: string | null;
    expires_at: string;
    created_at: string;
    updated_at: string;
    inviter: {
        id: number;
        name: string;
    };
}

interface InvitationsPageProps extends PageProps {
    invitations: {
        data: Invitation[];
        links: any;
    };
    success?: string;
    error?: string;
}

export default function Index({ auth, invitations, success, error }: InvitationsPageProps) {
    const { delete: destroy, post } = useForm();
    const page = usePage();

    // Παίρνουμε το domain από το URL
    const domain = getCurrentTenantDomain();

    useEffect(() => {
        if (success && (window as any).toast) {
            (window as any).toast.success(success);
        }
        if (error && (window as any).toast) {
            (window as any).toast.error(error);
        }
    }, [success, error]);

    const handleDelete = (id: number) => {
        if (confirm('Είστε σίγουροι ότι θέλετε να ακυρώσετε αυτή την πρόσκληση;')) {
            const deleteUrl = `/tenant/${domain}/invitations/${id}`;
            destroy(deleteUrl);
        }
    };

    const handleResend = (id: number) => {
        const resendUrl = `/tenant/${domain}/invitations/${id}/resend`;
        post(resendUrl);
    };

    const getStatusBadge = (invitation: Invitation) => {
        if (invitation.accepted_at) {
            return <span className="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Αποδεκτή</span>;
        }

        const expiresAt = new Date(invitation.expires_at);
        if (expiresAt < new Date()) {
            return <span className="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">Έληξε</span>;
        }

        return <span className="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs font-semibold">Σε αναμονή</span>;
    };

    return (
        <AuthenticatedLayout>
            <Head title="Προσκλήσεις" />
            <div className="p-4">
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div className="p-6 text-gray-900">
                        <div className="flex justify-between items-center mb-6">
                            <h2 className="text-lg font-semibold">Λίστα Προσκλήσεων</h2>
                            <Link href={`/tenant/${domain}/invitations/create`}>
                                <PrimaryButton>Νέα Πρόσκληση</PrimaryButton>
                            </Link>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Όνομα</th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ρόλος</th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Κατάσταση</th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Αποστολέας</th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ημερομηνία</th>
                                        <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ενέργειες</th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white divide-y divide-gray-200">
                                    {invitations.data.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="text-center py-4">
                                                Δεν υπάρχουν προσκλήσεις
                                            </td>
                                        </tr>
                                    )}

                                    {invitations.data.map((invitation) => (
                                        <tr key={invitation.id}>
                                            <td className="px-6 py-4 whitespace-nowrap">{invitation.email}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">{invitation.name || '-'}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {invitation.role === 'guide' ? 'Οδηγός' : 'Προσωπικό'}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">{getStatusBadge(invitation)}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">{invitation.inviter.name}</td>
                                            <td className="px-6 py-4 whitespace-nowrap">{new Date(invitation.created_at).toLocaleDateString()}</td>
                                            <td className="px-6 py-4 whitespace-nowrap space-x-2">
                                                {!invitation.accepted_at && (
                                                    <>
                                                        <SecondaryButton
                                                            className="text-xs"
                                                            onClick={() => handleResend(invitation.id)}
                                                        >
                                                            Αποστολή ξανά
                                                        </SecondaryButton>
                                                        <DangerButton
                                                            className="text-xs"
                                                            onClick={() => handleDelete(invitation.id)}
                                                        >
                                                            Ακύρωση
                                                        </DangerButton>
                                                    </>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination links would go here */}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 