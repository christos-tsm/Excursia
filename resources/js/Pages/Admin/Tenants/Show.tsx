import { useState } from 'react';
import { ArrowLeft } from 'lucide-react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react'
import AdminLayout from '@/Layouts/AdminLayout'
import { Tenant } from '@/types/models'
import TextInput from '@/Components/TextInput';
import SecondaryButton from '@/Components/SecondaryButton';
import TextArea from '@/Components/TextArea';
import Message from '@/Components/common/Message';
import { formatDay } from '@/lib/formatDay';

const TenantShow = ({ tenant }: { tenant: Tenant }) => {
    const page = usePage();
    const [isEditMode, setIsEditMode] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const success = page.props.success as string | undefined;
    const error = page.props.error as string | undefined;
    const { data, setData, put, errors, processing } = useForm({
        name: tenant.name,
        email: tenant.email,
        phone: tenant.phone,
        description: tenant.description,
        logo: tenant.logo,
        is_active: tenant.is_active,
        created_at: tenant.created_at,
        updated_at: tenant.updated_at,
    });

    const handleSave = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        setIsSubmitting(true)
        put(route('admin.tenants.update', tenant.id), {
            onSuccess: () => {
                setIsSubmitting(false)
                setIsEditMode(false)
            },
            onError: () => {
                setIsSubmitting(false)
            }
        })
    }
    // console.log(errors)
    return (
        <AdminLayout>
            <Head title={`${tenant.name} - Επιχειρηματικό Γραφείο`} />
            <div className="p-4">
                {success && (
                    <Message message={success} type='success' className='mb-4' />
                )}

                <div className="mb-6 flex gap-4 items-center">
                    <Link
                        href={route('admin.tenants.index')}
                        className="px-4 py-2 text-sm inline-flex gap-2 items-center transition bg-primary-300 rounded-md hover:bg-primary-400 text-white"
                    >
                        <ArrowLeft size={16} strokeWidth={1.5} />
                        Επιστροφή
                    </Link>
                    <h3 className="text-lg font-semibold">{tenant.name}</h3>
                </div>
                <div className="flex gap-4 mb-4">
                    <div className="px-4 py-2 bg-white rounded-md shadow-sm cursor-pointer text-sm" onClick={() => setIsEditMode(!isEditMode)}>
                        {isEditMode ? 'Ακύρωση' : 'Ενεργοποίηση επεξεργασίας'}
                    </div>
                </div>
                <form className='p-4 bg-white rounded-md shadow-sm' onSubmit={handleSave}>
                    <div className="flex flex-col gap-4">
                        <div className="flex flex-col gap-2">
                            <label htmlFor="name" className="block text-sm font-medium">Όνομα</label>
                            <TextInput
                                name="name"
                                value={data.name}
                                readOnly={!isEditMode}
                                onChange={(e) => setData('name', e.target.value)}
                            />
                            {errors.name && <Message message={errors.name} type='error' className='px-0 py-0 bg-transparent border-none text-xs' />}
                        </div>
                        <div className="flex gap-4 items-start">
                            <div className="flex flex-col gap-2">
                                <label htmlFor="email" className="block text-sm font-medium">Email</label>
                                <TextInput
                                    name="email"
                                    value={data.email}
                                    readOnly={!isEditMode}
                                    onChange={(e) => setData('email', e.target.value)}
                                />
                                {errors.email && <Message message={errors.email} type='error' className='px-0 py-0 bg-transparent border-none text-xs' />}
                            </div>
                            <div className="flex flex-col gap-2">
                                <label htmlFor="phone" className="block text-sm font-medium">Τηλέφωνο</label>
                                <TextInput
                                    name="phone"
                                    type='tel'
                                    value={data.phone}
                                    readOnly={!isEditMode}
                                    onChange={(e) => setData('phone', e.target.value)}
                                />
                                {errors.phone && <Message message={errors.phone} type='error' className='px-0 py-0 bg-transparent border-none text-xs' />}
                            </div>
                        </div>
                        <div className="flex flex-col gap-2">
                            <label htmlFor="description" className="block text-sm font-medium">Περιγραφή</label>
                            <TextArea
                                name="description"
                                value={data.description}
                                readOnly={!isEditMode}
                                onChange={(e) => setData('description', e.target.value)}
                            />
                            {errors.description && <Message message={errors.description} type='error' className='px-0 py-0 bg-transparent border-none text-xs' />}
                        </div>

                        {isEditMode ?
                            <SecondaryButton type="submit" className="w-fit" disabled={isSubmitting}>
                                {isSubmitting ? 'Αποθήκευση...' : 'Αποθήκευση'}
                            </SecondaryButton>
                            : null}
                    </div>
                </form>
                <div className='p-4 border-t border-slate-200 mt-4 bg-white rounded-md shadow-sm'>
                    <div className="flex gap-4">
                        <div className="flex flex-col gap-2 flex-1">
                            <label htmlFor="database" className="block text-sm font-medium">Database</label>
                            <TextInput
                                name="database"
                                value={tenant.database}
                                readOnly={true}
                            />
                        </div>
                        <div className="flex flex-col gap-2 flex-1">
                            <label htmlFor="created_at" className="block text-sm font-medium">Ημερομηνία Εγγραφής</label>
                            <TextInput
                                name="created_at"
                                value={formatDay(tenant.created_at, 'd/m/Y H:i')}
                                readOnly={true}
                            />
                        </div>
                        <div className="flex flex-col gap-2 flex-1">
                            <label htmlFor="updated_at" className="block text-sm font-medium">Τελευταία ενημέρωση</label>
                            <TextInput
                                name="updated_at"
                                value={formatDay(tenant.updated_at, 'd/m/Y H:i')}
                                readOnly={true}
                            />
                        </div>
                    </div>
                </div>
            </div>

        </AdminLayout>
    )
}

export default TenantShow