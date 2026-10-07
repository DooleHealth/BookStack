<?php

namespace Tests\Access;

use Tests\TestCase;

/**
 * The restriction is off by default under test (see RESTRICTED_VIEWER_ROLES in phpunit.xml),
 * since upstream's suite exercises the stock "Viewer" role as an ordinary one. These tests turn
 * it on explicitly to cover the deployed behaviour.
 */
class RestrictViewerRolesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['app.restricted_viewer_roles' => 'Viewer,Viewer-Admin,Viewer-MS']);
    }

    public function test_viewer_roles_can_read_content()
    {
        $book = $this->entities->bookHasChaptersAndPages();
        $page = $book->directPages()->first();

        $this->actingAs($this->users->viewer());

        $this->get('/')->assertOk();
        $this->get($book->getUrl())->assertOk();
        $this->get($page->getUrl())->assertOk();
        $this->get('/search?term=cat')->assertOk();
    }

    public function test_viewer_roles_are_blocked_from_everything_else()
    {
        $book = $this->entities->book();
        $this->actingAs($this->users->viewer());

        foreach (['/settings', '/create-book', $book->getUrl('/edit'), '/my-account/profile'] as $path) {
            $this->get($path)->assertRedirect('/');
        }
    }

    public function test_viewer_roles_get_a_json_error_on_ajax_requests()
    {
        $this->actingAs($this->users->viewer())
            ->getJson('/settings')
            ->assertStatus(403);
    }

    public function test_other_roles_are_untouched()
    {
        $this->actingAs($this->users->editor())->get('/create-book')->assertOk();
    }

    public function test_empty_config_lifts_the_restriction()
    {
        config(['app.restricted_viewer_roles' => '']);

        $this->assertFalse($this->users->viewer()->isViewerRole());
    }

    public function test_restriction_follows_the_configured_role_names()
    {
        config(['app.restricted_viewer_roles' => 'Some-Other-Role']);

        $this->assertFalse($this->users->viewer()->isViewerRole());
    }
}
