import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm, Link } from '@inertiajs/react'
import { FormEventHandler, useState } from 'react'
import { toast, Toaster } from 'react-hot-toast'
import { ArrowLeft, Upload, FileText } from 'lucide-react'

interface Trip {
    id: number
    title: string
    destination: string
}

interface CreateProps {
    tenant_id: string
    trip: Trip
}

const Create = ({ tenant_id, trip }: CreateProps) => {
    const [selectedFile, setSelectedFile] = useState<File | null>(null)
    const [dragOver, setDragOver] = useState(false)

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        document_type: 'other' as 'manual' | 'program' | 'notes' | 'other',
        is_public: false as boolean,
        file: null as File | null,
    })

    const submit: FormEventHandler = (e) => {
        e.preventDefault()
        if (!selectedFile) {
            toast.error('Παρακαλώ επιλέξτε ένα αρχείο')
            return
        }

        // Χρήση του post με το αρχείο
        post(route('tenant.trip.documents.store', { tenant_id, trip: trip.id }), {
            onSuccess: () => {
                toast.success('Το έγγραφο ανέβηκε επιτυχώς!')
            },
            onError: () => {
                toast.error('Προέκυψε σφάλμα κατά το ανέβασμα του εγγράφου')
            }
        })
    }

    const handleFileSelect = (file: File) => {
        // Έλεγχος τύπου αρχείου
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        if (!allowedTypes.includes(file.type)) {
            toast.error('Επιτρέπονται μόνο αρχεία PDF, DOC και DOCX')
            return
        }

        // Έλεγχος μεγέθους (10MB)
        if (file.size > 10 * 1024 * 1024) {
            toast.error('Το αρχείο δεν μπορεί να υπερβαίνει τα 10MB')
            return
        }

        setSelectedFile(file)
        setData('file', file)

        // Auto-συμπλήρωση τίτλου αν είναι κενός
        if (!data.title) {
            const nameWithoutExtension = file.name.replace(/\.[^/.]+$/, "")
            setData('title', nameWithoutExtension)
        }
    }

    const handleDrop = (e: React.DragEvent) => {
        e.preventDefault()
        setDragOver(false)

        const files = e.dataTransfer.files
        if (files.length > 0) {
            handleFileSelect(files[0])
        }
    }

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault()
        setDragOver(true)
    }

    const handleDragLeave = (e: React.DragEvent) => {
        e.preventDefault()
        setDragOver(false)
    }

    const getFileIcon = (type: string) => {
        if (type === 'application/pdf') return '📄'
        if (type.includes('word')) return '📝'
        return '📎'
    }

    const formatFileSize = (bytes: number) => {
        if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB'
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB'
        } else {
            return bytes + ' bytes'
        }
    }

    return (
        <AuthenticatedLayout>
            <Head title={`Ανέβασμα Εγγράφου - ${trip.title}`} />

            <div className="py-4 px-8">
                {/* Breadcrumb */}
                <div className="flex items-center space-x-2 mb-6">
                    <Link
                        href={route('tenant.trips.index', { tenant_id })}
                        className="text-primary-500 hover:text-primary-600 flex items-center"
                    >
                        <ArrowLeft className="w-4 h-4 mr-1" />
                        Ταξίδια
                    </Link>
                    <span className="text-gray-500">/</span>
                    <Link
                        href={route('tenant.trips.show', { tenant_id, trip: trip.id })}
                        className="text-primary-500 hover:text-primary-600"
                    >
                        {trip.title}
                    </Link>
                    <span className="text-gray-500">/</span>
                    <Link
                        href={route('tenant.trip.documents.index', { tenant_id, trip: trip.id })}
                        className="text-primary-500 hover:text-primary-600"
                    >
                        Έγγραφα
                    </Link>
                    <span className="text-gray-500">/</span>
                    <span className="text-gray-900">Ανέβασμα</span>
                </div>

                <div className="max-w-2xl mx-auto">
                    <div className="mb-8">
                        <h1 className="text-2xl font-semibold text-gray-900">Ανέβασμα Εγγράφου</h1>
                        <p className="text-gray-600 mt-1">{trip.title} - {trip.destination}</p>
                    </div>

                    <form onSubmit={submit} className="space-y-6 bg-white p-8 rounded-lg shadow">
                        {/* File Upload Area */}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Αρχείο *
                            </label>
                            <div
                                className={`border-2 border-dashed rounded-lg p-6 text-center transition-colors ${dragOver
                                    ? 'border-primary-400 bg-primary-50'
                                    : selectedFile
                                        ? 'border-green-400 bg-green-50'
                                        : 'border-gray-300 hover:border-gray-400'
                                    }`}
                                onDrop={handleDrop}
                                onDragOver={handleDragOver}
                                onDragLeave={handleDragLeave}
                            >
                                {selectedFile ? (
                                    <div className="space-y-3">
                                        <div className="text-4xl">{getFileIcon(selectedFile.type)}</div>
                                        <div>
                                            <p className="font-medium text-gray-900">{selectedFile.name}</p>
                                            <p className="text-sm text-gray-500">{formatFileSize(selectedFile.size)}</p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setSelectedFile(null)
                                                setData('file', null)
                                            }}
                                            className="text-sm text-red-600 hover:text-red-700"
                                        >
                                            Αφαίρεση αρχείου
                                        </button>
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        <Upload className="w-8 h-8 mx-auto text-gray-400" />
                                        <div>
                                            <p className="text-gray-600">Σύρετε το αρχείο εδώ ή</p>
                                            <label className="cursor-pointer text-primary-600 hover:text-primary-700 font-medium">
                                                επιλέξτε αρχείο
                                                <input
                                                    type="file"
                                                    className="hidden"
                                                    accept=".pdf,.doc,.docx"
                                                    onChange={(e) => {
                                                        const file = e.target.files?.[0]
                                                        if (file) handleFileSelect(file)
                                                    }}
                                                />
                                            </label>
                                        </div>
                                        <p className="text-xs text-gray-500">
                                            Επιτρέπονται αρχεία PDF, DOC, DOCX μέχρι 10MB
                                        </p>
                                    </div>
                                )}
                            </div>
                            {errors.file && <div className="text-red-600 text-sm mt-1">{errors.file}</div>}
                        </div>

                        {/* Τίτλος */}
                        <div>
                            <label htmlFor="title" className="block text-sm font-medium text-gray-700 mb-2">
                                Τίτλος Εγγράφου *
                            </label>
                            <input
                                id="title"
                                type="text"
                                value={data.title}
                                onChange={(e) => setData('title', e.target.value)}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="π.χ. Εγχειρίδιο Ταξιδιού Βιέννης"
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
                                rows={3}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                placeholder="Προαιρετική περιγραφή του εγγράφου..."
                            />
                            {errors.description && <div className="text-red-600 text-sm mt-1">{errors.description}</div>}
                        </div>

                        {/* Τύπος Εγγράφου */}
                        <div>
                            <label htmlFor="document_type" className="block text-sm font-medium text-gray-700 mb-2">
                                Τύπος Εγγράφου *
                            </label>
                            <select
                                id="document_type"
                                value={data.document_type}
                                onChange={(e) => setData('document_type', e.target.value as 'manual' | 'program' | 'notes' | 'other')}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                                <option value="manual">📘 Εγχειρίδιο</option>
                                <option value="program">📅 Πρόγραμμα</option>
                                <option value="notes">📝 Σημειώσεις</option>
                                <option value="other">📎 Άλλο</option>
                            </select>
                            {errors.document_type && <div className="text-red-600 text-sm mt-1">{errors.document_type}</div>}
                        </div>

                        {/* Δημόσια Διαθεσιμότητα */}
                        <div>
                            <label className="flex items-center space-x-3">
                                <input
                                    type="checkbox"
                                    checked={data.is_public}
                                    onChange={(e) => setData('is_public', e.target.checked)}
                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                />
                                <div>
                                    <span className="text-sm font-medium text-gray-700">
                                        Δημόσιο έγγραφο
                                    </span>
                                    <p className="text-xs text-gray-500">
                                        Θα είναι διαθέσιμο σε όλους τους χρήστες του tenant
                                    </p>
                                </div>
                            </label>
                            {errors.is_public && <div className="text-red-600 text-sm mt-1">{errors.is_public}</div>}
                        </div>

                        {/* Κουμπιά */}
                        <div className="flex items-center justify-end space-x-4 pt-6 border-t">
                            <Link
                                href={route('tenant.trip.documents.index', { tenant_id, trip: trip.id })}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Ακύρωση
                            </Link>
                            <button
                                type="submit"
                                disabled={processing || !selectedFile}
                                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                            >
                                {processing ? (
                                    <>
                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Ανέβασμα...
                                    </>
                                ) : (
                                    <>
                                        <Upload className="w-4 h-4 mr-2" />
                                        Ανέβασμα Εγγράφου
                                    </>
                                )}
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