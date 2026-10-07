<?php

namespace BookStack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware for SSO sessions opened in "embed" scope.
 *
 * The Doole backoffice shows a manual by iframing one read-only book version with "?embed=1".
 * That parameter alone is cosmetic: it can be stripped from the URL, which brings back the
 * site chrome and the "back to the current book" banner, letting the reader walk out into the
 * rest of the instance.
 *
 * When the SSO token carries an embed scope, the session gets pinned to that single version:
 *
 *  - The scope lives in the session, not in the URL, so stripping query params changes nothing.
 *  - "embed=1" is forced back onto the request, so the views keep rendering chrome-less
 *    (see BookVersionController, which reads it through $request->has('embed')).
 *  - Anything outside that version is rejected, the live book included.
 *
 * Optionally (see config('app.embed_require_iframe')) top-level browser navigations are rejected
 * too, which blocks the "copy the frame URL, open it on a new tab" route outright. That check is
 * fail-open: browsers not sending Sec-Fetch-Dest are let through, and stay contained by the path
 * allow-list below.
 */
class RestrictEmbedSession
{
    /**
     * Session key holding the ['book' => slug, 'version' => slug] the session is pinned to.
     */
    public const SESSION_KEY = 'sso_embed_scope';

    /**
     * Paths allowed within the scoped book version. The "{book}" and "{version}" placeholders
     * are replaced with the quoted slugs before matching.
     */
    protected array $scopedPatterns = [
        '#^/books/{book}/versions/{version}$#',
        '#^/books/{book}/versions/{version}/chapter/[^/]+$#',
        '#^/books/{book}/versions/{version}/page/[^/]+$#',
    ];

    /**
     * Supporting paths needed to render the content. Not version-specific, but each one applies
     * its own permission checks, same posture as RestrictViewerRoles.
     */
    protected array $supportPatterns = [
        '#^/uploads/images/.*$#',
        '#^/attachments/[0-9]+$#',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $scope = session(self::SESSION_KEY);

        if (!is_array($scope) || empty($scope['book']) || empty($scope['version'])) {
            return $next($request);
        }

        if (config('app.embed_require_iframe') && $this->isTopLevelNavigation($request)) {
            return $this->deny($request, null);
        }

        $path = '/' . ltrim($request->path(), '/');

        if (!$this->pathIsAllowed($path, $scope)) {
            return $this->deny($request, $this->versionUrl($scope));
        }

        // Taken from the session rather than the query string, so it cannot be turned off
        // by editing the URL.
        $request->query->set('embed', '1');

        return $next($request);
    }

    /**
     * Check whether the given path is reachable by a session pinned to the given version.
     */
    protected function pathIsAllowed(string $path, array $scope): bool
    {
        foreach ($this->supportPatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return true;
            }
        }

        $replacements = [
            '{book}'    => preg_quote($scope['book'], '#'),
            '{version}' => preg_quote($scope['version'], '#'),
        ];

        foreach ($this->scopedPatterns as $pattern) {
            $scopedPattern = strtr($pattern, $replacements);
            if (preg_match($scopedPattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect a top-level browser navigation (a real tab or window) as opposed to the document
     * being loaded within the iframe, a sub-resource, or an in-page fetch request.
     */
    protected function isTopLevelNavigation(Request $request): bool
    {
        return $request->headers->get('Sec-Fetch-Dest') === 'document';
    }

    /**
     * Build the embed URL of the version the session is pinned to.
     */
    protected function versionUrl(array $scope): string
    {
        return url('/books/' . urlencode($scope['book']) . '/versions/' . urlencode($scope['version']) . '?embed=1');
    }

    /**
     * Build the rejection response. A return link is only offered when the user could act on it:
     * if the navigation itself was rejected, following that link would be rejected as well.
     */
    protected function deny(Request $request, ?string $versionUrl): mixed
    {
        if ($request->wantsJson()) {
            return response()->json(['error' => trans('errors.permissionJson')], 403);
        }

        return response()->view('errors.embed-restricted', ['versionUrl' => $versionUrl], 403);
    }
}
