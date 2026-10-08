<?php

namespace Tests\Access;

use BookStack\Entities\Models\Book;
use BookStack\Entities\Models\BookVersion;
use BookStack\Entities\Repos\BookVersionRepo;
use BookStack\Http\Middleware\RestrictEmbedSession;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmbedSessionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.jwt_secret' => 'test-embed-secret-value-long-enough-for-hs256']);
    }

    /**
     * Snapshot a book and return [$book, $version].
     */
    protected function versionedBook(string $label = 'v1'): array
    {
        $book = $this->entities->bookHasChaptersAndPages();
        $version = app(BookVersionRepo::class)->createVersion($book, $label, $this->users->admin());

        return [$book, $version];
    }

    protected function pinTo(Book $book, BookVersion $version): void
    {
        $this->actingAs($this->users->editor())->withSession([
            RestrictEmbedSession::SESSION_KEY => [
                'book'    => $book->slug,
                'version' => $version->version_slug,
            ],
        ]);
    }

    protected function versionUrl(Book $book, BookVersion $version, string $suffix = ''): string
    {
        return '/books/' . $book->slug . '/versions/' . $version->version_slug . $suffix;
    }

    protected function ssoUrl(array $claims = [], string $redirect = '/'): string
    {
        $payload = array_merge([
            'email' => 'embed-user@example.com',
            'name'  => 'Embed User',
            'jti'   => Str::random(16),
            'exp'   => time() + 60,
        ], $claims);

        $token = JWT::encode($payload, config('app.jwt_secret'), 'HS256');

        return '/sso/login?token=' . $token . '&redirect=' . urlencode($redirect);
    }

    public function test_sso_token_with_embed_scope_pins_the_session_to_the_version()
    {
        [$book, $version] = $this->versionedBook();
        $target = $this->versionUrl($book, $version) . '?embed=1';

        $this->get($this->ssoUrl([
            'scope'   => 'embed',
            'book'    => $book->slug,
            'version' => $version->version_slug,
        ], $target))
            ->assertRedirect($target)
            ->assertSessionHas(RestrictEmbedSession::SESSION_KEY, [
                'book'    => $book->slug,
                'version' => $version->version_slug,
            ]);
    }

    public function test_sso_token_without_embed_scope_clears_an_existing_scope()
    {
        $this->withSession([RestrictEmbedSession::SESSION_KEY => ['book' => 'a-book', 'version' => 'v1']])
            ->get($this->ssoUrl())
            ->assertSessionMissing(RestrictEmbedSession::SESSION_KEY);
    }

    public function test_embed_scoped_token_requires_valid_book_and_version_slugs()
    {
        $this->get($this->ssoUrl(['scope' => 'embed', 'book' => 'a-book']))->assertStatus(400);
        $this->get($this->ssoUrl(['scope' => 'embed', 'version' => 'v1']))->assertStatus(400);
        $this->get($this->ssoUrl(['scope' => 'embed', 'book' => '../x', 'version' => 'v1']))->assertStatus(400);
    }

    public function test_embed_session_can_reach_its_own_version()
    {
        [$book, $version] = $this->versionedBook();
        $page = $version->pages()->first();
        $chapter = $version->chapters()->first();

        $this->pinTo($book, $version);

        $this->get($this->versionUrl($book, $version))->assertOk();
        $this->get($this->versionUrl($book, $version, '/page/' . $page->slug))->assertOk();
        $this->get($this->versionUrl($book, $version, '/chapter/' . $chapter->slug))->assertOk();
    }

    public function test_stripping_the_embed_param_still_renders_without_chrome()
    {
        [$book, $version] = $this->versionedBook();
        $this->pinTo($book, $version);

        // Note the URL carries no "embed" param at all.
        $resp = $this->get($this->versionUrl($book, $version));
        $resp->assertOk();
        $html = $this->withHtml($resp);

        // The middleware forced it back on, so the view keeps building embed links...
        $html->assertElementExists('a[href$="' . $this->versionUrl($book, $version) . '?embed=1"]');

        // ...and the plain layout carries no header, nor a way back to the live book.
        $html->assertElementNotExists('header#header');
        $html->assertElementNotExists('a[href$="' . $book->slug . '"]');
    }

    public function test_embed_session_cannot_reach_anything_outside_its_version()
    {
        [$book, $version] = $this->versionedBook();
        [$otherBook, $otherVersion] = $this->versionedBook('v2');

        $this->pinTo($book, $version);

        $paths = [
            '/',
            '/books',
            '/shelves',
            '/search?term=cat',
            $book->getUrl(),                                    // the live book
            '/books/' . $book->slug . '/versions',              // the version listing
            $this->versionUrl($otherBook, $otherVersion),       // another book's version
        ];

        foreach ($paths as $path) {
            $resp = $this->get($path);
            $resp->assertStatus(403);
            $resp->assertSee('This manual can only be read from within the Doole application.');
        }
    }

    public function test_embed_session_cannot_reach_another_version_of_the_same_book()
    {
        [$book, $version] = $this->versionedBook();
        $otherVersion = app(BookVersionRepo::class)->createVersion($book, 'v9', $this->users->admin());

        $this->pinTo($book, $version);

        $this->get($this->versionUrl($book, $otherVersion))->assertStatus(403);
    }

    public function test_top_level_navigation_is_rejected_when_required()
    {
        config(['app.embed_require_iframe' => true]);
        [$book, $version] = $this->versionedBook();
        $this->pinTo($book, $version);

        // Opening the copied URL on a new tab.
        $this->withHeader('Sec-Fetch-Dest', 'document')
            ->get($this->versionUrl($book, $version))->assertStatus(403);

        // The same URL loaded within the backoffice iframe.
        $this->withHeader('Sec-Fetch-Dest', 'iframe')
            ->get($this->versionUrl($book, $version))->assertOk();

        // Browsers that do not send the header are let through.
        $this->get($this->versionUrl($book, $version))->assertOk();
    }

    public function test_top_level_navigation_check_can_be_disabled()
    {
        config(['app.embed_require_iframe' => false]);
        [$book, $version] = $this->versionedBook();
        $this->pinTo($book, $version);

        $this->withHeader('Sec-Fetch-Dest', 'document')
            ->get($this->versionUrl($book, $version))->assertOk();
    }

    public function test_a_walled_embed_session_can_log_out_and_sign_in_normally()
    {
        config(['app.embed_require_iframe' => true]);
        [$book, $version] = $this->versionedBook();
        $this->pinTo($book, $version);

        // Somebody holding an embed session (an admin who opened a manual from the backoffice)
        // opens docs.doole.io on a tab of its own, and hits the wall.
        $resp = $this->withHeader('Sec-Fetch-Dest', 'document')->get('/');
        $resp->assertStatus(403);

        // /login is no use while authenticated: it bounces straight back into the wall.
        $this->get('/login')->assertRedirect('/');

        // So the wall has to offer a way out.
        $this->withHtml($resp)->assertElementExists('form[action$="/logout"] button');

        $this->post('/logout');
        $this->assertFalse(auth()->check());
        $this->get('/')->assertRedirect('/login');
    }

    public function test_sessions_without_embed_scope_are_untouched()
    {
        [$book, $version] = $this->versionedBook();

        $this->asEditor()->get('/')->assertOk();
        $this->get($book->getUrl())->assertOk();

        // Without the scope, the version view still honours the query param as before.
        $resp = $this->get($this->versionUrl($book, $version));
        $resp->assertOk();
        $this->withHtml($resp)->assertElementExists('header#header');
    }
}
