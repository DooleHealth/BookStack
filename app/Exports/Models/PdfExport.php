<?php

namespace BookStack\Exports\Models;

use BookStack\App\Model;
use BookStack\Users\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PdfExport extends Model
{
    protected $table = 'pdf_exports';

    protected $fillable = [
        'user_id',
        'entity_type',
        'entity_id',
        'entity_name',
        'file_name',
        'storage_path',
        'status',
        'error_message',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isAvailable(): bool
    {
        return $this->status === 'completed' && !$this->isExpired();
    }

    public function getDownloadUrl(): ?string
    {
        if (!$this->isAvailable() || !$this->storage_path) {
            return null;
        }

        try {
            return Storage::disk('exports')->temporaryUrl($this->storage_path, now()->addHours(1));
        } catch (\Exception $e) {
            return null;
        }
    }
}
