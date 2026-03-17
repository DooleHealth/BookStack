<?php

namespace BookStack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to restrict users with viewer-type roles (Viewer, Viewer-Admin, Viewer-MS)
 * so they can only view shelves, books, chapters, pages, and use favourites/search.
 * All other routes (editing, settings, user profiles, etc.) are blocked.
 */
class RestrictViewerRoles
{
    /**
     * Routes (regex patterns) that viewer roles ARE allowed to access.
     */
    protected array $allowedPatterns = [
        // Home
        '#^/$#',
        '#^/home$#',

        // View shelves (but not edit/delete/permissions/etc.)
        '#^/shelves/?$#',
        '#^/shelves/[^/]+$#',

        // View books (but not edit/delete/sort/copy/permissions/etc.)
        '#^/books/?$#',
        '#^/books/[^/]+$#',

        // View chapters
        '#^/books/[^/]+/chapter/[^/]+$#',

        // View pages
        '#^/books/[^/]+/page/[^/]+$#',

        // Favourites
        '#^/favourites$#',
        '#^/favourites/add$#',
        '#^/favourites/remove$#',

        // Search
        '#^/search$#',
        '#^/search/suggest$#',
        '#^/search/book/[^/]+$#',
        '#^/search/chapter/[^/]+$#',
        '#^/search/entity/siblings$#',
        '#^/search/entity-selector$#',
        '#^/search/entity-selector-templates$#',

        // Images (needed for viewing content)
        '#^/uploads/images/.*$#',

        // Attachments (view only)
        '#^/attachments/[^/]+$#',

        // Links (redirects)
        '#^/link/[^/]+$#',

        // User preferences (dark mode, view changes, etc.)
        '#^/preferences/.*$#',

        // AJAX page content (needed for viewing)
        '#^/ajax/page/[^/]+$#',

        // Tags (view)
        '#^/tags$#',

        // API docs (read-only)
        '#^/api/?$#',
        '#^/api/docs$#',

        // Status/meta
        '#^/status$#',
        '#^/robots\.txt$#',
        '#^/favicon\.ico$#',
        '#^/manifest\.json$#',
        '#^/licenses$#',
        '#^/opensearch\.xml$#',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        // Only restrict authenticated users with viewer-type roles
        if ($user && $user->isViewerRole()) {
            $path = '/' . ltrim($request->path(), '/');

            // Check if the current path matches any allowed pattern
            $isAllowed = false;
            foreach ($this->allowedPatterns as $pattern) {
                if (preg_match($pattern, $path)) {
                    $isAllowed = true;
                    break;
                }
            }

            // Also allow GET requests only for the allowed routes
            // POST to favourites/add and favourites/remove are explicitly allowed above
            if (!$isAllowed) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => trans('errors.permissionJson')], 403);
                }

                session()->flash('error', trans('errors.permission'));

                return redirect('/');
            }
        }

        return $next($request);
    }
}
