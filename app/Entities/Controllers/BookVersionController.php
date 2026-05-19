<?php

namespace BookStack\Entities\Controllers;

use BookStack\Activity\ActivityType;
use BookStack\Entities\Models\BookVersion;
use BookStack\Entities\Queries\BookQueries;
use BookStack\Entities\Repos\BookVersionRepo;
use BookStack\Exceptions\NotFoundException;
use BookStack\Facades\Activity;
use BookStack\Http\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BookVersionController extends Controller
{
    public function __construct(
        protected BookQueries $bookQueries,
        protected BookVersionRepo $versionRepo,
    ) {
    }

    /**
     * Show the list of versions for a book.
     */
    public function index(string $bookSlug)
    {
        $book = $this->bookQueries->findVisibleBySlugOrFail($bookSlug);
        $this->checkPermission('settings-manage');

        $versions = $this->versionRepo->getVersionsForBook($book);

        $this->setPageTitle(trans('entities.book_versions') . ' - ' . $book->getShortName());

        return view('book-versions.index', [
            'book' => $book,
            'versions' => $versions,
        ]);
    }

    /**
     * Show the form to create/tag a new version.
     */
    public function create(string $bookSlug)
    {
        $book = $this->bookQueries->findVisibleBySlugOrFail($bookSlug);
        $this->checkPermission('settings-manage');

        $this->setPageTitle(trans('entities.book_version_create') . ' - ' . $book->getShortName());

        return view('book-versions.create', [
            'book' => $book,
        ]);
    }

    /**
     * Store a new version (snapshot) of the book.
     */
    public function store(Request $request, string $bookSlug)
    {
        $book = $this->bookQueries->findVisibleBySlugOrFail($bookSlug);
        $this->checkPermission('settings-manage');

        $validated = $this->validate($request, [
            'version_label' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) use ($book) {
                    $slug = \Illuminate\Support\Str::slug($value);
                    $exists = $book->versions()->where('version_slug', $slug)->exists();
                    if ($exists) {
                        $fail(trans('entities.book_version_unique'));
                    }
                },
            ],
        ]);

        $version = $this->versionRepo->createVersion($book, $validated['version_label'], user());

        Activity::add(ActivityType::BOOK_VERSION_CREATE, $version);

        return redirect($book->getUrl('/versions'))->with('success', trans('entities.book_version_created', ['label' => $version->version_label]));
    }

    /**
     * Show a specific version of the book (read-only book view).
     */
    public function show(Request $request, string $bookSlug, string $versionSlug)
    {
        $book = $this->bookQueries->findVisibleBySlugOrFail($bookSlug);
        $version = $this->versionRepo->getVersionBySlug($book, $versionSlug);

        if (!$version) {
            throw new NotFoundException(trans('entities.book_version_not_found'));
        }

        $chapters = $version->chapters()->with('pages')->get();
        $directPages = $version->directPages()->get();

        $embedMode = $request->has('embed');
        $urlBase = $this->buildUrlBase($book, $version);
        $embedQuery = $embedMode ? '?embed=1' : '';

        $this->setPageTitle($version->book_name . ' - ' . trans('entities.book_version_label', ['label' => $version->version_label]));

        return view('book-versions.show', [
            'book' => $book,
            'version' => $version,
            'chapters' => $chapters,
            'directPages' => $directPages,
            'embedMode' => $embedMode,
            'urlBase' => $urlBase,
            'embedQuery' => $embedQuery,
        ]);
    }

    /**
     * Show a versioned chapter.
     */
    public function showChapter(Request $request, string $bookSlug, string $versionSlug, string $chapterSlug)
    {
        $book = $this->bookQueries->findVisibleBySlugOrFail($bookSlug);
        $version = $this->versionRepo->getVersionBySlug($book, $versionSlug);

        if (!$version) {
            throw new NotFoundException(trans('entities.book_version_not_found'));
        }

        $chapter = $version->chapters()->where('slug', $chapterSlug)->first();

        if (!$chapter) {
            throw new NotFoundException(trans('entities.book_version_chapter_not_found'));
        }

        $pages = $chapter->pages()->get();

        // Calculate next/previous
        $flatList = $this->buildFlatList($version);
        [$previous, $next] = $this->getNeighbours($flatList, 'chapter', $chapter->id);

        $embedMode = $request->has('embed');
        $urlBase = $this->buildUrlBase($book, $version);
        $embedQuery = $embedMode ? '?embed=1' : '';

        $this->setPageTitle($chapter->name . ' - ' . $version->book_name . ' v' . $version->version_label);

        return view('book-versions.show-chapter', [
            'book' => $book,
            'version' => $version,
            'chapter' => $chapter,
            'pages' => $pages,
            'next' => $next,
            'previous' => $previous,
            'embedMode' => $embedMode,
            'urlBase' => $urlBase,
            'embedQuery' => $embedQuery,
        ]);
    }

    /**
     * Show a versioned page.
     */
    public function showPage(Request $request, string $bookSlug, string $versionSlug, string $pageSlug)
    {
        $book = $this->bookQueries->findVisibleBySlugOrFail($bookSlug);
        $version = $this->versionRepo->getVersionBySlug($book, $versionSlug);

        if (!$version) {
            throw new NotFoundException(trans('entities.book_version_not_found'));
        }

        $page = $version->pages()->where('slug', $pageSlug)->first();

        if (!$page) {
            throw new NotFoundException(trans('entities.book_version_page_not_found'));
        }

        $chapter = $page->chapter;

        // Calculate next/previous
        $flatList = $this->buildFlatList($version);
        [$previous, $next] = $this->getNeighbours($flatList, 'page', $page->id);

        $embedMode = $request->has('embed');
        $urlBase = $this->buildUrlBase($book, $version);
        $embedQuery = $embedMode ? '?embed=1' : '';

        // Rewrite internal links to point to versioned URLs
        $pageHtml = $this->rewriteInternalLinks($page->html, $version, $urlBase, $embedQuery);

        $this->setPageTitle($page->name . ' - ' . $version->book_name . ' v' . $version->version_label);

        return view('book-versions.show-page', [
            'book' => $book,
            'version' => $version,
            'chapter' => $chapter,
            'page' => $page,
            'pageHtml' => $pageHtml,
            'next' => $next,
            'previous' => $previous,
            'embedMode' => $embedMode,
            'urlBase' => $urlBase,
            'embedQuery' => $embedQuery,
        ]);
    }

    /**
     * Delete a version.
     */
    public function destroy(string $bookSlug, string $versionSlug)
    {
        $book = $this->bookQueries->findVisibleBySlugOrFail($bookSlug);
        $this->checkPermission('settings-manage');

        $version = $this->versionRepo->getVersionBySlug($book, $versionSlug);

        if (!$version) {
            throw new NotFoundException(trans('entities.book_version_not_found'));
        }

        $this->versionRepo->deleteVersion($version);

        Activity::add(ActivityType::BOOK_VERSION_DELETE, $version);

        return redirect($book->getUrl('/versions'))
            ->with('success', trans('entities.book_version_deleted', ['label' => $version->version_label]));
    }

    /**
     * Build the URL base path for version links.
     */
    protected function buildUrlBase($book, BookVersion $version): string
    {
        return '/books/' . urlencode($book->slug) . '/versions/' . urlencode($version->version_slug);
    }

    /**
     * Rewrite internal BookStack links in page HTML to point to versioned URLs.
     * Matches links to pages and chapters that exist in the current version.
     */
    protected function rewriteInternalLinks(string $html, BookVersion $version, string $urlBase, string $embedQuery): string
    {
        if (empty($html)) {
            return $html;
        }

        // Collect all page and chapter slugs in this version for quick lookup
        $pageSlugs = $version->pages()->pluck('slug')->flip();
        $chapterSlugs = $version->chapters()->pluck('slug')->flip();

        $baseUrl = rtrim(url('/'), '/');
        $escapedBase = preg_quote($baseUrl, '/');

        // Rewrite page links: /books/{bookSlug}/page/{pageSlug}
        $html = preg_replace_callback(
            '/href=["\'](' . $escapedBase . ')?\/books\/[\w-]+\/page\/([\w-]+)([#?][^"\']*)?["\']/i',
            function ($matches) use ($pageSlugs, $urlBase, $embedQuery) {
                $pageSlug = $matches[2];
                $fragment = $matches[3] ?? '';

                if ($pageSlugs->has($pageSlug)) {
                    $newUrl = url($urlBase . '/page/' . urlencode($pageSlug) . $embedQuery) . $fragment;
                    return 'href="' . e($newUrl) . '"';
                }

                // Not in this version, keep original
                return $matches[0];
            },
            $html
        );

        // Rewrite chapter links: /books/{bookSlug}/chapter/{chapterSlug}
        $html = preg_replace_callback(
            '/href=["\'](' . $escapedBase . ')?\/books\/[\w-]+\/chapter\/([\w-]+)([#?][^"\']*)?["\']/i',
            function ($matches) use ($chapterSlugs, $urlBase, $embedQuery) {
                $chapterSlug = $matches[2];
                $fragment = $matches[3] ?? '';

                if ($chapterSlugs->has($chapterSlug)) {
                    $newUrl = url($urlBase . '/chapter/' . urlencode($chapterSlug) . $embedQuery) . $fragment;
                    return 'href="' . e($newUrl) . '"';
                }

                return $matches[0];
            },
            $html
        );

        return $html;
    }

    /**
     * Build a flat ordered list of all chapters and pages in user reading order.
     */
    protected function buildFlatList(BookVersion $version): Collection
    {
        $chapters = $version->chapters()->with('pages')->get();
        $directPages = $version->directPages()->get();

        $allTopLevel = $directPages->map(fn($p) => (object)['item' => $p, 'type' => 'page', 'priority' => $p->priority])
            ->concat($chapters->map(fn($c) => (object)['item' => $c, 'type' => 'chapter', 'priority' => $c->priority]))
            ->sortBy('priority');

        $flat = collect();
        foreach ($allTopLevel as $entry) {
            if ($entry->type === 'chapter') {
                $flat->push(['type' => 'chapter', 'id' => $entry->item->id, 'name' => $entry->item->name, 'slug' => $entry->item->slug]);
                foreach ($entry->item->pages as $page) {
                    $flat->push(['type' => 'page', 'id' => $page->id, 'name' => $page->name, 'slug' => $page->slug]);
                }
            } else {
                $flat->push(['type' => 'page', 'id' => $entry->item->id, 'name' => $entry->item->name, 'slug' => $entry->item->slug]);
            }
        }

        return $flat;
    }

    /**
     * Get previous and next items from the flat list for the given type+id.
     */
    protected function getNeighbours(Collection $flatList, string $type, int $id): array
    {
        $index = $flatList->search(fn($item) => $item['type'] === $type && $item['id'] === $id);

        if ($index === false) {
            return [null, null];
        }

        $previous = $index > 0 ? $flatList->get($index - 1) : null;
        $next = $flatList->get($index + 1);

        return [$previous, $next];
    }
}
