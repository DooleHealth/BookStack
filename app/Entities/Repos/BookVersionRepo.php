<?php

namespace BookStack\Entities\Repos;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\BookVersion;
use BookStack\Entities\Models\BookVersionChapter;
use BookStack\Entities\Models\BookVersionPage;
use BookStack\Entities\Tools\BookContents;
use BookStack\Users\Models\User;
use Illuminate\Support\Str;

class BookVersionRepo
{
    /**
     * Create a new version snapshot for the given book.
     */
    public function createVersion(Book $book, string $versionLabel, User $user): BookVersion
    {
        $version = new BookVersion();
        $version->book_id = $book->id;
        $version->version_label = $versionLabel;
        $version->version_slug = Str::slug($versionLabel);
        $version->book_name = $book->name;
        $version->book_description = $book->description ?? '';
        $version->book_description_html = $book->description_html ?? '';
        $version->created_by = $user->id;
        $version->save();

        // Snapshot chapters
        $chapters = $book->chapters()->orderBy('priority')->get();
        $chapterIdMap = [];

        foreach ($chapters as $chapter) {
            $versionChapter = new BookVersionChapter();
            $versionChapter->book_version_id = $version->id;
            $versionChapter->original_chapter_id = $chapter->id;
            $versionChapter->name = $chapter->name;
            $versionChapter->slug = $chapter->slug;
            $versionChapter->description = $chapter->description ?? '';
            $versionChapter->description_html = $chapter->description_html ?? '';
            $versionChapter->priority = $chapter->priority ?? 0;
            $versionChapter->save();

            $chapterIdMap[$chapter->id] = $versionChapter->id;
        }

        // Snapshot pages
        $pages = $book->pages()->orderBy('priority')->get();

        foreach ($pages as $page) {
            $versionPage = new BookVersionPage();
            $versionPage->book_version_id = $version->id;
            $versionPage->original_page_id = $page->id;
            $versionPage->name = $page->name;
            $versionPage->slug = $page->slug;
            $versionPage->html = $page->html ?? '';
            $versionPage->markdown = $page->markdown ?? '';
            $versionPage->priority = $page->priority ?? 0;

            if ($page->chapter_id && isset($chapterIdMap[$page->chapter_id])) {
                $versionPage->book_version_chapter_id = $chapterIdMap[$page->chapter_id];
            }

            $versionPage->save();
        }

        return $version;
    }

    /**
     * Get all versions for a book, ordered by newest first.
     */
    public function getVersionsForBook(Book $book)
    {
        return $book->versions()->with('createdBy')->orderBy('created_at', 'desc')->get();
    }

    /**
     * Find a version by its slug within a book.
     */
    public function getVersionBySlug(Book $book, string $versionSlug): ?BookVersion
    {
        return $book->versions()->where('version_slug', $versionSlug)->first();
    }

    /**
     * Delete a version and all its associated data (cascades via FK).
     */
    public function deleteVersion(BookVersion $version): void
    {
        $version->delete();
    }
}
