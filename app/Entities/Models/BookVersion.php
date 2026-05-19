<?php

namespace BookStack\Entities\Models;

use BookStack\Activity\Models\Loggable;
use BookStack\App\Model;
use BookStack\Users\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property int    $book_id
 * @property string $version_label
 * @property string $version_slug
 * @property string $book_name
 * @property ?string $book_description
 * @property ?string $book_description_html
 * @property int    $created_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BookVersion extends Model implements Loggable
{
    protected $table = 'book_versions';

    protected $fillable = ['version_label', 'version_slug', 'book_name', 'book_description', 'book_description_html'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(BookVersionChapter::class)->orderBy('priority');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(BookVersionPage::class)->orderBy('priority');
    }

    public function directPages(): HasMany
    {
        return $this->hasMany(BookVersionPage::class)->whereNull('book_version_chapter_id')->orderBy('priority');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getUrl(string $path = ''): string
    {
        $book = $this->book;
        $base = '/books/' . urlencode($book->slug) . '/versions/' . urlencode($this->version_slug);

        return url($base . '/' . ltrim($path, '/'));
    }

    public function logDescriptor(): string
    {
        return "v{$this->version_label} ({$this->book_name})";
    }
}
