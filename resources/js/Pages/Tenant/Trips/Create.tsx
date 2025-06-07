import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm } from '@inertiajs/react'
import { FormEventHandler, useEffect } from 'react'
import { toast, Toaster } from 'react-hot-toast'

interface CreateProps {
    tenant_id: string
}

const Create = ({ tenant_id }: CreateProps) => {
    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        destination: '',
        price: '',
        duration: '',
        departure_date: '',
        return_date: '',
        is_published: false as boolean,
    })

    // Αυτόματος υπολογισμός duration όταν αλλάζουν οι ημερομηνίες
    useEffect(() => {
        if (data.departure_date && data.return_date) {
            const departureDate = new Date(data.departure_date)
            const returnDate = new Date(data.return_date)

            if (returnDate >= departureDate) {
                const diffTime = returnDate.getTime() - departureDate.getTime()
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1 // +1 για να περιλάβει και την ημέρα αναχώρησης
                setData('duration', diffDays.toString())
            }
        }
    }, [data.departure_date, data.return_date])

    const submit: FormEventHandler = (e) => {
        e.preventDefault()
        post(route('tenant.trips.store', { tenant_id }), {
            onSuccess: () => {
                toast.success('Το ταξίδι δημιουργήθηκε επιτυχώς!')
            },
            onError: () => {
                toast.error('Προέκυψε σφάλμα κατά τη δημιουργία του ταξιδιού')
            }
        })
    }

    return (
        <AuthenticatedLayout>
            <Head title="Δημιουργία Ταξιδιού" />

            <div className="py-12 px-8">
                <div className="max-w-2xl mx-auto">
                    <h1 className="text-2xl font-semibold text-gray-900 mb-8">Δημιουργία Ταξιδιού</h1>

                    <form onSubmit={submit} className="space-y-6 bg-white p-8 rounded-lg shadow">
                        {/* Τίτλος */}
                        <div>
                            <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
                                Τίτλος Ταξιδιού *
                            </label>
                            <input
                                id="title"
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="π.χ. Ταξίδι στην Αθήνα"
                                required
                            />
                            {errors.title && <div className="text-red-600 text-sm mt-1">{errors.title}</div>}
                        </div>

                        {/* Περιγραφή */}
                        <div>
                            <label htmlFor="description" className="block text-sm font-medium text-gray-700 mb-2">
                                Περιγραφή
                            </label>
                            <textarea
                                id="description"
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                rows={4}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Περιγράψτε το ταξίδι..."
                            />
                            {errors.description && <div className="text-red-600 text-sm mt-1">{errors.description}</div>}
                        </div>

                        {/* Προορισμός */}
                        <div>
                            <label htmlFor="destination" className="block text-sm font-medium text-gray-700 mb-2">
                                Προορισμός *
                            </label>
                            <input
                                id="destination"
                                type="text"
                                value={data.destination}
                                onChange={(e) => setData('destination', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="π.χ. Αθήνα, Ελλάδα"
                                required
                            />
                            {errors.destination && <div className="text-red-600 text-sm mt-1">{errors.destination}</div>}
                        </div>

                        {/* Ημερομηνίες */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label htmlFor="departure_date" className="block text-sm font-medium text-gray-700 mb-2">
                                    Ημερομηνία Αναχώρησης *
                                </label>
                                <input
                                    id="departure_date"
                                    type="date"
                                    value={data.departure_date}
                                    onChange={(e) => setData('departure_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required
                                />
                                {errors.departure_date && <div className="text-red-600 text-sm mt-1">{errors.departure_date}</div>}
                            </div>

                            <div>
                                <label htmlFor="return_date" className="block text-sm font-medium text-gray-700 mb-2">
                                    Ημερομηνία Επιστροφής *
                                </label>
                                <input
                                    id="return_date"
                                    type="date"
                                    value={data.return_date}
                                    onChange={(e) => setData('return_date', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    required
                                />
                                {errors.return_date && <div className="text-red-600 text-sm mt-1">{errors.return_date}</div>}
                            </div>
                        </div>

                        {/* Τιμή και Διάρκεια */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label htmlFor="price" className="block text-sm font-medium text-gray-700 mb-2">
                                    Τιμή / άτομο (€) *
                                </label>
                                <input
                                    id="price"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.price}
                                    onChange={(e) => setData('price', e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="0.00"
                                    required
                                />
                                {errors.price && <div className="text-red-600 text-sm mt-1">{errors.price}</div>}
                            </div>

                            <div>
                                <label htmlFor="duration" className="block text-sm font-medium text-gray-700 mb-2">
                                    Διάρκεια (ημέρες)
                                </label>
                                <input
                                    id="duration"
                                    type="number"
                                    value={data.duration}
                                    readOnly
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-600 cursor-not-allowed"
                                    placeholder="Υπολογίζεται αυτόματα"
                                />
                                <div className="text-xs text-gray-500 mt-1">
                                    Υπολογίζεται αυτόματα από τις ημερομηνίες
                                </div>
                                {errors.duration && <div className="text-red-600 text-sm mt-1">{errors.duration}</div>}
                            </div>
                        </div>

                        {/* Κατάσταση Δημοσίευσης */}
                        <div>
                            <label className="flex items-center space-x-3">
                                <input
                                    type="checkbox"
                                    checked={data.is_published}
                                    onChange={(e) => setData('is_published', e.target.checked)}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <span className="text-sm font-medium text-gray-700">
                                    Δημοσίευση ταξιδιού αμέσως
                                </span>
                            </label>
                            {errors.is_published && <div className="text-red-600 text-sm mt-1">{errors.is_published}</div>}
                        </div>

                        {/* Κουμπιά */}
                        <div className="flex items-center justify-end space-x-4 pt-6 border-t">
                            <button
                                type="button"
                                onClick={() => window.history.back()}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Ακύρωση
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Αποθήκευση...' : 'Δημιουργία Ταξιδιού'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <Toaster position="top-right" />
        </AuthenticatedLayout>
    )
}

export default Create