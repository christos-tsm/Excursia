import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { PageProps } from '@/types';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';

interface Invitation {
    id: number;
    email: string;
    name: string | null;
    token: string;
    role: string;
    expires_at: string;
}

interface AcceptInvitationProps extends PageProps {
    invitation: Invitation;
    email: string;
    name?: string;
    error?: string;
}

export default function Accept({ invitation, email, name, error }: AcceptInvitationProps) {
    const { data, setData, post, processing, errors } = useForm({
        name: name || invitation.name || '',
        password: '',
        password_confirmation: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('invitation.accept', invitation.token));
    };

    if (error) {
        return (
            <div className="min-h-screen flex flex-col items-center justify-center bg-gray-100">
                <Head title="Πρόσκληση" />
                <div className="w-full max-w-md">
                    <div className="bg-white p-6 rounded-lg shadow-md">
                        <h2 className="text-xl font-semibold mb-4">Λυπούμαστε!</h2>
                        <div className="bg-red-50 border border-red-300 text-red-800 p-4 rounded-md mb-6">
                            <div className="font-medium">Σφάλμα</div>
                            <div>{error}</div>
                        </div>
                        <PrimaryButton
                            className="w-full"
                            onClick={() => window.location.href = '/login'}
                        >
                            Επιστροφή στην αρχική σελίδα
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen flex flex-col items-center justify-center bg-gray-100">
            <Head title="Αποδοχή Πρόσκλησης" />

            <div className="w-full max-w-md">
                <div className="bg-white p-6 rounded-lg shadow-md">
                    <h2 className="text-xl font-semibold mb-6">Αποδοχή Πρόσκλησης</h2>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={invitation.email || email}
                                disabled
                            />
                        </div>

                        <div>
                            <InputLabel htmlFor="name" value="Ονοματεπώνυμο" />
                            <TextInput
                                id="name"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                                required
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="password" value="Κωδικός πρόσβασης" />
                            <TextInput
                                id="password"
                                type="password"
                                className="mt-1 block w-full"
                                value={data.password}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('password', e.target.value)}
                                required
                            />
                            <InputError message={errors.password} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="password_confirmation" value="Επιβεβαίωση κωδικού" />
                            <TextInput
                                id="password_confirmation"
                                type="password"
                                className="mt-1 block w-full"
                                value={data.password_confirmation}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('password_confirmation', e.target.value)}
                                required
                            />
                        </div>

                        <div>
                            <p className="text-sm text-gray-500">
                                Έχετε προσκληθεί ως {invitation.role === 'guide' ? 'Οδηγός' : 'Προσωπικό'}.
                                Η πρόσκληση λήγει στις {new Date(invitation.expires_at).toLocaleDateString()}.
                            </p>
                        </div>

                        <PrimaryButton
                            className="w-full"
                            disabled={processing}
                        >
                            Αποδοχή Πρόσκλησης
                        </PrimaryButton>
                    </form>
                </div>
            </div>
        </div>
    );
} 