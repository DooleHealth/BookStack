<?php

namespace Tests\Entity;

use BookStack\Entities\Repos\BookVersionRepo;
use BookStack\Entities\Repos\PageRepo;
use Tests\TestCase;

class BookVersionTest extends TestCase
{
    public function test_snapshot_leaves_out_draft_pages()
    {
        $book = $this->entities->bookHasChaptersAndPages();

        // This is what BookStack creates the moment someone clicks to add a page and walks away.
        $draft = app(PageRepo::class)->getNewDraftPage($book);
        $this->assertTrue($draft->draft);

        $version = app(BookVersionRepo::class)->createVersion($book, '3.5.0', $this->users->admin());

        $this->assertNotContains($draft->name, $version->pages()->pluck('name')->all());
        $this->assertEquals(
            $book->pages()->where('draft', '=', false)->count(),
            $version->pages()->count()
        );
    }

    public function test_snapshot_still_keeps_published_pages_and_chapters()
    {
        $book = $this->entities->bookHasChaptersAndPages();
        app(PageRepo::class)->getNewDraftPage($book);

        $version = app(BookVersionRepo::class)->createVersion($book, '3.5.0', $this->users->admin());

        $this->assertGreaterThan(0, $version->pages()->count());
        $this->assertEquals($book->chapters()->count(), $version->chapters()->count());

        foreach ($book->pages()->where('draft', '=', false)->get() as $page) {
            $this->assertContains($page->slug, $version->pages()->pluck('slug')->all());
        }
    }

    public function test_the_draft_does_not_show_on_the_version_page()
    {
        $book = $this->entities->bookHasChaptersAndPages();
        $draft = app(PageRepo::class)->getNewDraftPage($book);
        $version = app(BookVersionRepo::class)->createVersion($book, '3.5.0', $this->users->admin());

        $resp = $this->asAdmin()->get('/books/' . $book->slug . '/versions/' . $version->version_slug);
        $resp->assertOk();
        $resp->assertDontSee($draft->name);
    }
}
