import React, { FormEventHandler, useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '@/Layouts/GuestLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import TextArea from '@/Components/TextArea';

// Φάσεις της διαδικασίας εγγραφής
enum RegistrationStep {
    OwnerDetails = 0,
    BusinessDetails = 1,
}

export default function Register() {
    // State για τα βήματα
    const [currentStep, setCurrentStep] = useState<RegistrationStep>(RegistrationStep.OwnerDetails);

    const { data, setData, post, processing, errors, reset } = useForm({
        // Στοιχεία ιδιοκτήτη (Βήμα 1)
        owner_name: '',
        owner_email: '',
        owner_password: '',
        owner_password_confirmation: '',

        // Στοιχεία επιχείρησης (Βήμα 2)
        name: '',
        email: '',
        phone: '',
        domain: '',
        description: '',
    });

    // Προχωράει στο επόμενο βήμα
    const nextStep = () => {
        if (currentStep === RegistrationStep.OwnerDetails) {
            // Επικύρωση πεδίων βήματος 1
            if (!data.owner_name || !data.owner_email || !data.owner_password || !data.owner_password_confirmation) {
                return; // Αν λείπουν τα υποχρεωτικά πεδία, μην προχωράς
            }

            if (data.owner_password !== data.owner_password_confirmation) {
                return; // Αν οι κωδικοί δεν ταιριάζουν, μην προχωράς
            }

            setCurrentStep(RegistrationStep.BusinessDetails);
        }
    };

    // Επιστρέφει στο προηγούμενο βήμα
    const prevStep = () => {
        if (currentStep === RegistrationStep.BusinessDetails) {
            setCurrentStep(RegistrationStep.OwnerDetails);
        }
    };

    // Υποβολή της φόρμας
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tenant.register'));
    };

    // Περιεχόμενο του τρέχοντος βήματος
    const renderStepContent = () => {
        switch (currentStep) {
            case RegistrationStep.OwnerDetails:
                return (
                    <>
                        <div className="mb-6">
                            <h2 className="text-lg font-medium border-b pb-2 mb-4">Στοιχεία Ιδιοκτήτη</h2>

                            <div className="mb-4">
                                <InputLabel htmlFor="owner_name" value="Ονοματεπώνυμο" />
                                <TextInput
                                    id="owner_name"
                                    type="text"
                                    name="owner_name"
                                    value={data.owner_name}
                                    className="mt-1 block w-full"
                                    autoComplete="name"
                                    isFocused={true}
                                    onChange={(e) => setData('owner_name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.owner_name} className="mt-2" />
                            </div>

                            <div className="mb-4">
                                <InputLabel htmlFor="owner_email" value="Email" />
                                <TextInput
                                    id="owner_email"
                                    type="email"
                                    name="owner_email"
                                    value={data.owner_email}
                                    className="mt-1 block w-full"
                                    autoComplete="email"
                                    onChange={(e) => setData('owner_email', e.target.value)}
                                    required
                                />
                                <InputError message={errors.owner_email} className="mt-2" />
                            </div>

                            <div className="mb-4">
                                <InputLabel htmlFor="owner_password" value="Κωδικός" />
                                <TextInput
                                    id="owner_password"
                                    type="password"
                                    name="owner_password"
                                    value={data.owner_password}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    onChange={(e) => setData('owner_password', e.target.value)}
                                    required
                                />
                                <InputError message={errors.owner_password} className="mt-2" />
                            </div>

                            <div className="mb-4">
                                <InputLabel htmlFor="owner_password_confirmation" value="Επιβεβαίωση Κωδικού" />
                                <TextInput
                                    id="owner_password_confirmation"
                                    type="password"
                                    name="owner_password_confirmation"
                                    value={data.owner_password_confirmation}
                                    className="mt-1 block w-full"
                                    autoComplete="new-password"
                                    onChange={(e) => setData('owner_password_confirmation', e.target.value)}
                                    required
                                />
                                <InputError message={errors.owner_password_confirmation} className="mt-2" />
                            </div>
                        </div>

                        <div className="flex items-center justify-between mt-6 text-sm">
                            <Link
                                href={route('login')}
                                className="text-primary-400 hover:text-primary-500"
                            >
                                Έχετε ήδη λογαριασμό;
                            </Link>

                            <PrimaryButton onClick={nextStep} type="button" className="ml-4">
                                Επόμενο
                            </PrimaryButton>
                        </div>
                    </>
                );

            case RegistrationStep.BusinessDetails:
                return (
                    <>
                        <div className="mb-6">
                            <h2 className="text-lg font-medium border-b pb-2 mb-4">Στοιχεία Επιχείρησης</h2>

                            <div className="mb-4">
                                <InputLabel htmlFor="name" value="Επωνυμία" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    name="name"
                                    value={data.name}
                                    className="mt-1 block w-full"
                                    autoComplete="organization"
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div className="mb-4">
                                <InputLabel htmlFor="email" value="Email Επιχείρησης" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="mt-1 block w-full"
                                    autoComplete="organization-email"
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                />
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div className="mb-4">
                                <InputLabel htmlFor="phone" value="Τηλέφωνο Επιχείρησης" />
                                <TextInput
                                    id="phone"
                                    type="text"
                                    name="phone"
                                    value={data.phone}
                                    className="mt-1 block w-full"
                                    autoComplete="tel"
                                    onChange={(e) => setData('phone', e.target.value)}
                                />
                                <InputError message={errors.phone} className="mt-2" />
                            </div>

                            <div className="mb-4">
                                <InputLabel htmlFor="domain" value="Επιθυμητό Subdomain (προαιρετικό)" />
                                <div className="flex items-center">
                                    <TextInput
                                        id="domain"
                                        type="text"
                                        name="domain"
                                        value={data.domain}
                                        className="mt-1 block w-full"
                                        onChange={(e) => setData('domain', e.target.value)}
                                    />
                                    <span className="ml-2 text-gray-600">.excursia.com</span>
                                </div>
                                <p className="mt-1 text-sm text-gray-500">Αν αφεθεί κενό, θα δημιουργηθεί αυτόματα από το όνομα της επιχείρησης.</p>
                                <InputError message={errors.domain} className="mt-2" />
                            </div>

                            <div className="mb-4">
                                <InputLabel htmlFor="description" value="Περιγραφή Επιχείρησης" />
                                <TextArea
                                    id="description"
                                    name="description"
                                    value={data.description}
                                    className="mt-1 block w-full"
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={4}
                                />
                                <InputError message={errors.description} className="mt-2" />
                            </div>
                        </div>

                        <div className="flex items-center justify-between mt-6">
                            <button
                                type="button"
                                onClick={prevStep}
                                className="text-sm text-gray-600 hover:text-gray-900"
                            >
                                Προηγούμενο
                            </button>

                            <PrimaryButton type="submit" className="ml-4" disabled={processing}>
                                Ολοκλήρωση Εγγραφής
                            </PrimaryButton>
                        </div>
                    </>
                );

            default:
                return null;
        }
    };

    // Δείκτης προόδου (progress bar)
    const renderProgressBar = () => {
        return (
            <div className="mb-8">
                <div className="flex items-center justify-between mb-2">
                    <div className="w-full bg-gray-200 rounded-full h-2.5">
                        <div
                            className="bg-primary-300 h-2.5 rounded-full"
                            style={{ width: `${((currentStep + 1) / 2) * 100}%` }}
                        ></div>
                    </div>
                </div>
                <div className="flex justify-between text-xs text-gray-500">
                    <span className={currentStep >= RegistrationStep.OwnerDetails ? "text-primary-300 font-medium" : ""}>
                        Στοιχεία Λογαριασμού
                    </span>
                    <span className={currentStep >= RegistrationStep.BusinessDetails ? "text-primary-300 font-medium" : ""}>
                        Στοιχεία Επιχείρησης
                    </span>
                </div>
            </div>
        );
    };

    return (
        <GuestLayout>
            <Head title="Εγγραφή Επιχείρησης" />

            <div className="">
                <h1 className="text-2xl font-semibold mb-6 text-center">Εγγραφή Τουριστικού Γραφείου</h1>

                <form onSubmit={submit}>
                    {renderProgressBar()}
                    {renderStepContent()}
                </form>
            </div>
        </GuestLayout>
    );
} 