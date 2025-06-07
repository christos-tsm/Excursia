<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use App\Models\TripDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use Dompdf\Dompdf;
use Dompdf\Options;

class TripDocumentController extends Controller {
    /**
     * Εμφάνιση όλων των εγγράφων του tenant με φιλτράρισμα
     */
    public function allDocuments(Request $request) {
        $tenant_id = $request->route('tenant_id');

        // Λήψη όλων των ταξιδιών του tenant για το dropdown
        $trips = Trip::where('tenant_id', $tenant_id)
            ->select('id', 'title', 'destination')
            ->orderBy('title')
            ->get();

        $query = TripDocument::whereHas('trip', function ($q) use ($tenant_id) {
            $q->where('tenant_id', $tenant_id);
        })->with(['trip:id,title,destination', 'uploadedBy:id,name']);

        // Φιλτράρισμα με βάση το ταξίδι
        if ($request->has('trip_id') && !empty($request->trip_id)) {
            $query->where('trip_id', $request->trip_id);
        }

        // Φιλτράρισμα με βάση τον τύπο εγγράφου
        if ($request->has('document_type') && !empty($request->document_type)) {
            $query->where('document_type', $request->document_type);
        }

        // Φιλτράρισμα με βάση τον τίτλο
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $documents = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'description' => $document->description,
                    'file_name' => $document->file_name,
                    'file_type' => $document->file_type,
                    'file_size' => $document->formatted_file_size,
                    'file_icon' => $document->file_icon,
                    'document_type' => $document->document_type,
                    'document_type_label' => $document->document_type_label,
                    'is_public' => $document->is_public,
                    'uploaded_by' => $document->uploadedBy->name,
                    'created_at' => $document->created_at->format('d/m/Y H:i'),
                    'download_url' => $document->download_url,
                    'creation_type' => $document->creation_type,
                    'trip' => [
                        'id' => $document->trip->id,
                        'title' => $document->trip->title,
                        'destination' => $document->trip->destination,
                    ],
                ];
            });

        return Inertia::render('Tenant/Documents/Index', [
            'documents' => $documents,
            'trips' => $trips,
            'tenant_id' => $tenant_id,
            'filters' => [
                'search' => $request->search ?? '',
                'trip_id' => $request->trip_id ?? '',
                'document_type' => $request->document_type ?? '',
            ],
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Εμφάνιση λίστας εγγράφων ταξιδιού
     */
    public function index(Request $request, $tenant_id, Trip $trip) {

        $documents = $trip->documents()
            ->with('uploadedBy:id,name')
            ->latest()
            ->get()
            ->map(function ($document) {
                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'description' => $document->description,
                    'file_name' => $document->file_name,
                    'file_type' => $document->file_type,
                    'file_size' => $document->formatted_file_size,
                    'file_icon' => $document->file_icon,
                    'document_type' => $document->document_type,
                    'document_type_label' => $document->document_type_label,
                    'is_public' => $document->is_public,
                    'uploaded_by' => $document->uploadedBy->name,
                    'created_at' => $document->created_at->format('d/m/Y H:i'),
                    'download_url' => $document->download_url,
                    'creation_type' => $document->creation_type,
                ];
            });

        return Inertia::render('Tenant/Trips/Documents/Index', [
            'trip' => $trip,
            'documents' => $documents,
            'tenant_id' => $tenant_id,
            'success' => session('message'),
            'error' => session('error')
        ]);
    }

    /**
     * Εμφάνιση φόρμας ανεβάσματος εγγράφου
     */
    public function create(Request $request, $tenant_id, Trip $trip) {

        return Inertia::render('Tenant/Trips/Documents/Create', [
            'trip' => $trip,
            'tenant_id' => $tenant_id,
        ]);
    }

    /**
     * Εμφάνιση φόρμας δημιουργίας εγγράφου με editor
     */
    public function createEditor(Request $request, $tenant_id, Trip $trip) {

        return Inertia::render('Tenant/Trips/Documents/CreateEditor', [
            'trip' => $trip,
            'tenant_id' => $tenant_id,
        ]);
    }

    /**
     * Αποθήκευση νέου εγγράφου
     */
    public function store(Request $request, $tenant_id, Trip $trip) {
        try {

            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'document_type' => ['required', 'in:manual,program,notes,other'],
                'is_public' => ['boolean'],
                'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // 10MB max
            ]);

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            // Δημιουργία unique filename
            $fileName = time() . '_' . uniqid() . '.' . $extension;

            // Αποθήκευση στο private disk
            $filePath = $file->storeAs('trip-documents/' . $trip->id, $fileName, 'private');

            TripDocument::create([
                'trip_id' => $trip->id,
                'uploaded_by' => Auth::id(),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'file_name' => $originalName,
                'file_path' => $filePath,
                'file_type' => $extension,
                'file_size' => $file->getSize(),
                'document_type' => $validated['document_type'],
                'is_public' => $validated['is_public'] ?? false,
            ]);

            return redirect()->route('tenant.trip.documents.index', [
                'tenant_id' => $tenant_id,
                'trip' => $trip
            ])->with('message', 'Το έγγραφο ανέβηκε επιτυχώς');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Προέκυψε σφάλμα: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Αποθήκευση εγγράφου που δημιουργήθηκε με editor
     */
    public function storeEditor(Request $request, $tenant_id, Trip $trip) {
        try {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'document_type' => ['required', 'in:manual,program,notes,other'],
                'is_public' => ['boolean'],
                'content' => ['required', 'string'],
                'editor_metadata' => ['nullable', 'array'],
            ]);

            TripDocument::create([
                'trip_id' => $trip->id,
                'uploaded_by' => Auth::id(),
                'title' => $validated['title'],
                'description' => $validated['description'],
                'content' => $validated['content'],
                'creation_type' => 'editor',
                'editor_metadata' => $validated['editor_metadata'] ?? null,
                'document_type' => $validated['document_type'],
                'is_public' => $validated['is_public'] ?? false,
                'file_name' => $validated['title'] . '.html', // Προσωρινό όνομα αρχείου
                'file_type' => 'html',
            ]);

            return redirect()->route('tenant.trip.documents.index', [
                'tenant_id' => $tenant_id,
                'trip' => $trip
            ])->with('message', 'Το έγγραφο δημιουργήθηκε επιτυχώς');
        } catch (\Exception $e) {
            return back()
                ->with('error', 'Προέκυψε σφάλμα: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Export εγγράφου σε διάφορες μορφές
     */
    public function export(Request $request, $tenant_id, Trip $trip, TripDocument $document, $format) {
        // Έλεγχος ότι το έγγραφο ανήκει στο συγκεκριμένο trip
        if ($document->trip_id !== $trip->id) {
            abort(404);
        }

        // Έλεγχος ότι το έγγραφο έχει content για export
        if ($document->creation_type !== 'editor' || empty($document->content)) {
            return back()->with('error', 'Αυτό το έγγραφο δεν μπορεί να εξαχθεί');
        }

        try {
            if ($format === 'pdf') {
                return $this->exportToPdf($document);
            } elseif ($format === 'docx') {
                return $this->exportToDocx($document);
            } else {
                return back()->with('error', 'Μη υποστηριζόμενη μορφή αρχείου');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Σφάλμα κατά την εξαγωγή: ' . $e->getMessage());
        }
    }

    /**
     * Export σε PDF
     */
    private function exportToPdf(TripDocument $document) {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);

        // Προσθήκη CSS για καλύτερη εμφάνιση
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 12pt; line-height: 1.6; }
                h1, h2, h3 { color: #333; }
                .header { border-bottom: 2px solid #333; margin-bottom: 20px; padding-bottom: 10px; }
                .content { margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . htmlspecialchars($document->title) . '</h1>
                <p><strong>Περιγραφή:</strong> ' . htmlspecialchars($document->description ?: 'Χωρίς περιγραφή') . '</p>
                <p><strong>Ημερομηνία:</strong> ' . $document->created_at->format('d/m/Y H:i') . '</p>
            </div>
            <div class="content">
                ' . $document->content . '
            </div>
        </body>
        </html>';

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = str_replace([' ', '/'], '_', $document->title) . '.pdf';

        return response()->streamDownload(function () use ($dompdf) {
            echo $dompdf->output();
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Export σε DOCX
     */
    private function exportToDocx(TripDocument $document) {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Προσθήκη τίτλου
        $section->addTitle($document->title, 1);

        if ($document->description) {
            $section->addText('Περιγραφή: ' . $document->description);
            $section->addTextBreak();
        }

        $section->addText('Ημερομηνία: ' . $document->created_at->format('d/m/Y H:i'));
        $section->addTextBreak(2);

        // Προσθήκη περιεχομένου HTML
        Html::addHtml($section, $document->content);

        // Αποθήκευση σε προσωρινό αρχείο
        $tempFile = tempnam(sys_get_temp_dir(), 'document_');
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        $filename = str_replace([' ', '/'], '_', $document->title) . '.docx';

        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Download εγγράφου
     */
    public function download(Request $request, $tenant_id, Trip $trip, TripDocument $document) {
        // Έλεγχος ότι το έγγραφο ανήκει στο συγκεκριμένο trip
        if ($document->trip_id !== $trip->id) {
            abort(404);
        }

        // Έλεγχος ότι το αρχείο υπάρχει
        if (!Storage::disk('private')->exists($document->file_path)) {
            return back()->with('error', 'Το αρχείο δεν βρέθηκε');
        }

        return Storage::disk('private')->download($document->file_path, $document->file_name);
    }

    /**
     * Διαγραφή εγγράφου
     */
    public function destroy(Request $request, $tenant_id, Trip $trip, TripDocument $document) {
        try {

            // Έλεγχος ότι το έγγραφο ανήκει στο συγκεκριμένο trip
            if ($document->trip_id !== $trip->id) {
                abort(404);
            }

            $document->delete();

            return redirect()->route('tenant.trip.documents.index', [
                'tenant_id' => $tenant_id,
                'trip' => $trip
            ])->with('message', 'Το έγγραφο διαγράφηκε επιτυχώς');
        } catch (\Exception $e) {
            return back()->with('error', 'Προέκυψε σφάλμα κατά τη διαγραφή: ' . $e->getMessage());
        }
    }
}
