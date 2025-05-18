import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import SelectInput from '@/Components/SelectInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { getCurrentTenantDomain } from '@/Utils/tenant';

interface CreateInvitationProps extends PageProps { }

export default function Create({ auth }: CreateInvitationProps) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        name: '',
        role: 'staff',
    });

    // Παίρνουμε το domain από το URL
    const domain = getCurrentTenantDomain();

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        const storeUrl = `/tenant/${domain}/invitations`;
        post(storeUrl);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Δημιουργία Πρόσκλησης" />
            <div className="p-4">
                <div className="bg-white overflow-hidden  sm:rounded-lg p-4">
                    <h2 className="text-lg font-semibold mb-4">Νέα Πρόσκληση</h2>
                    <p className="mb-6 text-gray-600">
                        Προσκαλέστε νέο μέλος στην ομάδα σας.
                    </p>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                className="mt-1 block w-full"
                                value={data.email}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('email', e.target.value)}
                                required
                            />
                            <InputError message={errors.email} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="name" value="Όνομα (προαιρετικό)" />
                            <TextInput
                                id="name"
                                type="text"
                                className="mt-1 block w-full"
                                value={data.name}
                                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setData('name', e.target.value)}
                            />
                            <InputError message={errors.name} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="role" value="Ρόλος" />
                            <SelectInput
                                id="role"
                                className="mt-1 block w-full"
                                value={data.role}
                                onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setData('role', e.target.value)}
                            >
                                <option value="guide">Οδηγός</option>
                                <option value="staff">Προσωπικό</option>
                            </SelectInput>
                            <InputError message={errors.role} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-end mt-6">
                            <SecondaryButton
                                type="button"
                                className="mr-2"
                                onClick={() => window.history.back()}
                            >
                                Ακύρωση
                            </SecondaryButton>
                            <PrimaryButton
                                type="submit"
                                disabled={processing}
                            >
                                Αποστολή Πρόσκλησης
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
} 