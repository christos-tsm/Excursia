<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TripDocument extends Model {
    use HasFactory;

    protected $fillable = [
        'trip_id',
        'uploaded_by',
        'title',
        'description',
        'content',
        'creation_type',
        'editor_metadata',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'document_type',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'file_size' => 'integer',
        'editor_metadata' => 'array',
    ];

    /**
     * Το ταξίδι στο οποίο ανήκει το έγγραφο
     */
    public function trip(): BelongsTo {
        return $this->belongsTo(Trip::class);
    }

    /**
     * Ο χρήστης που ανέβασε το έγγραφο
     */
    public function uploadedBy(): BelongsTo {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Λήψη του URL για download του αρχείου
     */
    public function getDownloadUrlAttribute(): string {
        return route('tenant.trip.documents.download', [
            'tenant_id' => $this->trip->tenant_id,
            'trip' => $this->trip_id,
            'document' => $this->id
        ]);
    }

    /**
     * Μορφοποίηση του μεγέθους αρχείου σε human-readable format
     */
    public function getFormattedFileSizeAttribute(): string {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Λήψη του icon που αντιστοιχεί στον τύπο αρχείου
     */
    public function getFileIconAttribute(): string {
        return match ($this->file_type) {
            'pdf' => '📄',
            'docx', 'doc' => '📝',
            default => '📎'
        };
    }

    /**
     * Λήψη της ετικέτας του τύπου εγγράφου
     */
    public function getDocumentTypeLabelAttribute(): string {
        return match ($this->document_type) {
            'manual' => 'Εγχειρίδιο',
            'program' => 'Πρόγραμμα',
            'notes' => 'Σημειώσεις',
            'other' => 'Άλλο',
            default => 'Άγνωστο'
        };
    }

    /**
     * Διαγραφή του αρχείου από το storage όταν διαγράφεται η εγγραφή
     */
    protected static function booted(): void {
        static::deleting(function (TripDocument $document) {
            if (Storage::disk('private')->exists($document->file_path)) {
                Storage::disk('private')->delete($document->file_path);
            }
        });
    }
}
