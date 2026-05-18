<?php

namespace BookStack\Entities\Models;

use BookStack\App\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property int    $book_version_id
 * @property ?int   $book_version_chapter_id
 * @property ?int   $original_page_id
 * @property string $name
 * @property string $slug
 * @property string $html
 * @property ?string $markdown
 * @property int    $priority
 */
class BookVersionPage extends Model
{
    protected $table = 'book_version_pages';

    public $timestamps = false;

    protected $fillable = ['name', 'slug', 'html', 'markdown', 'priority', 'original_page_id'];

    public function bookVersion(): BelongsTo
    {
        return $this->belongsTo(BookVersion::class);
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(BookVersionChapter::class, 'book_version_chapter_id');
    }

    public function getUrl(BookVersion $version): string
    {
        return $version->getUrl('page/' . urlencode($this->slug));
    }
}
