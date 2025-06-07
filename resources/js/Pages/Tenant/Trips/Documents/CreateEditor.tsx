import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, useForm, Link } from '@inertiajs/react'
import { FormEventHandler, useRef, useEffect } from 'react'
import { toast, Toaster } from 'react-hot-toast'
import { ArrowLeft, Save, FileText, Download } from 'lucide-react'

// Extend Window interface for TinyMCE
declare global {
    interface Window {
        tinymce: any;
    }
}

interface Trip {
    id: number
    title: string
    destination: string
}

interface CreateEditorProps {
    tenant_id: string
    trip: Trip
}

const CreateEditor = ({ tenant_id, trip }: CreateEditorProps) => {
    const editorRef = useRef<any>(null)

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        description: '',
        document_type: 'other' as 'manual' | 'program' | 'notes' | 'other',
        is_public: false as boolean,
        content: '',
        editor_metadata: {} as any,
    })

    useEffect(() => {
        // Load TinyMCE script
        const script = document.createElement('script')
        script.src = 'https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js'
        script.onload = () => {
            initializeEditor()
        }
        document.head.appendChild(script)

        return () => {
            // Cleanup
            if (window.tinymce) {
                window.tinymce.remove('#editor')
            }
            document.head.removeChild(script)
        }
    }, [])

    const initializeEditor = () => {
        window.tinymce.init({
            selector: '#editor',
            height: 500,
            language: 'el', // Ελληνικά
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount',
                'spellchecker', 'typography', 'pagebreak', 'nonbreaking'
            ],
            toolbar: [
                'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify',
                'bullist numlist outdent indent | removeformat | table link image media | preview code fullscreen help'
            ],
            menubar: 'file edit view insert format tools table help',
            content_style: `
                body { 
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                    font-size: 14px; 
                    line-height: 1.6;
                    color: #333;
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                }
                h1, h2, h3 { color: #2563eb; }
                blockquote { border-left: 4px solid #2563eb; padding-left: 16px; margin: 20px 0; }
            `,
            // Spell checking configuration
            spellchecker_languages: 'Ελληνικά=el,English=en',
            spellchecker_language: 'el',
            browser_spellcheck: true,
            // Save callback για auto-save
            setup: (editor: any) => {
                editorRef.current = editor
                editor.on('change', () => {
                    const content = editor.getContent()
                    setData('content', content)
                })
            },
            // File browser για εικόνες
            file_picker_types: 'image',
            image_title: true,
            automatic_uploads: true,
            file_picker_callback: (callback: any, value: any, meta: any) => {
                if (meta.filetype === 'image') {
                    const input = document.createElement('input')
                    input.setAttribute('type', 'file')
                    input.setAttribute('accept', 'image/*')
                    input.addEventListener('change', (e: any) => {
                        const file = e.target.files[0]
                        if (file) {
                            const reader = new FileReader()
                            reader.onload = () => {
                                callback(reader.result, { alt: file.name })
                            }
                            reader.readAsDataURL(file)
                        }
                    })
                    input.click()
                }
            }
        })
    }

    const submit: FormEventHandler = (e) => {
        e.preventDefault()

        if (!data.content.trim()) {
            toast.error('Παρακαλώ προσθέστε περιεχόμενο στο έγγραφο')
            return
        }

        // Λήψη metadata από editor
        if (editorRef.current) {
            setData('editor_metadata', {
                wordCount: editorRef.current.plugins.wordcount.getCount(),
                characterCount: editorRef.current.plugins.wordcount.getCount('characters'),
                lastModified: new Date().toISOString()
            })
        }

        post(route('tenant.trip.documents.store-editor', { tenant_id, trip: trip.id }), {
            onSuccess: () => {
                toast.success('Το έγγραφο δημιουργήθηκε επιτυχώς!')
            },
            onError: () => {
                toast.error('Προέκυψε σφάλμα κατά τη δημιουργία του εγγράφου')
            }
        })
    }

    const handlePreview = () => {
        if (editorRef.current) {
            editorRef.current.execCommand('mcePreview')
        }
    }

    return (
        <AuthenticatedLayout>
            <Head title={`Δημιουργία Εγγράφου - ${trip.title}`} />

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
                    <span className="text-gray-900">Δημιουργία</span>
                </div>

                <div className="max-w-6xl mx-auto">
                    <div className="mb-8">
                        <h1 className="text-2xl font-semibold text-gray-900">Δημιουργία Εγγράφου</h1>
                        <p className="text-gray-600 mt-1">{trip.title} - {trip.destination}</p>
                    </div>

                    <form onSubmit={submit} className="space-y-6">
                        {/* Metadata Section */}
                        <div className="bg-white p-6 rounded-lg shadow space-y-4">
                            <h2 className="text-lg font-medium text-gray-900">Πληροφορίες Εγγράφου</h2>

                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    rows={2}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Προαιρετική περιγραφή του εγγράφου..."
                                />
                                {errors.description && <div className="text-red-600 text-sm mt-1">{errors.description}</div>}
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
                        </div>

                        {/* Editor Section */}
                        <div className="bg-white p-6 rounded-lg shadow">
                            <div className="flex justify-between items-center mb-4">
                                <h2 className="text-lg font-medium text-gray-900">Περιεχόμενο Εγγράφου</h2>
                                <button
                                    type="button"
                                    onClick={handlePreview}
                                    className="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md flex items-center"
                                >
                                    <FileText className="w-4 h-4 mr-1" />
                                    Προεπισκόπηση
                                </button>
                            </div>

                            <div className="border border-gray-300 rounded-md">
                                <textarea
                                    id="editor"
                                    placeholder="Αρχίστε να γράφετε το έγγραφό σας εδώ..."
                                    className="w-full"
                                />
                            </div>
                            {errors.content && <div className="text-red-600 text-sm mt-1">{errors.content}</div>}

                            <div className="mt-2 text-xs text-gray-500">
                                💡 Συμβουλή: Χρησιμοποιήστε Ctrl+S για αυτόματη αποθήκευση, F11 για πλήρη οθόνη, ή το εικονίδιο της προεπισκόπησης για να δείτε πώς θα φαίνεται το έγγραφο.
                            </div>
                        </div>

                        {/* Κουμπιά */}
                        <div className="flex items-center justify-end space-x-4 pt-6">
                            <Link
                                href={route('tenant.trip.documents.index', { tenant_id, trip: trip.id })}
                                className="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            >
                                Ακύρωση
                            </Link>
                            <button
                                type="submit"
                                disabled={processing || !data.content.trim()}
                                className="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                            >
                                {processing ? (
                                    <>
                                        <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                                        Αποθήκευση...
                                    </>
                                ) : (
                                    <>
                                        <Save className="w-4 h-4 mr-2" />
                                        Δημιουργία Εγγράφου
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

export default CreateEditor 