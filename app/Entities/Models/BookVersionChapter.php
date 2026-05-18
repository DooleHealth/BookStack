<?php

namespace BookStack\Entities\Models;

use BookStack\App\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property int    $book_version_id
 * @property ?int   $original_chapter_id
 * @property string $name
 * @property string $slug
 * @property ?string $description
 * @property ?string $description_html
 * @property int    $priority
 */
class BookVersionChapter extends Model
{
    protected $table = 'book_version_chapters';

    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'description', 'description_html', 'priority', 'original_chapter_id'];

    public function bookVersion(): BelongsTo
    {
        return $this->belongsTo(BookVersion::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(BookVersionPage::class)->orderBy('priority');
    }

    public function getUrl(BookVersion $version): string
    {
        return $version->getUrl('chapter/' . urlencode($this->slug));
    }
}
