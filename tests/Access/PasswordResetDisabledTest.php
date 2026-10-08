<?php

namespace Tests\Access;

use Tests\TestCase;

/**
 * The reset flow is enabled under test (see PASSWORD_RESET_ENABLED in phpunit.xml) so upstream's
 * own reset tests keep working. These turn it off, which is how Doole deploys it.
 */
class PasswordResetDisabledTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['auth.password_reset_enabled' => false]);
    }

    public function test_reset_routes_are_not_reachable()
    {
        $this->get('/password/email')->assertStatus(404);
        $this->post('/password/email', ['email' => 'admin@admin.com'])->assertStatus(404);
        $this->get('/password/reset/some-token')->assertStatus(404);
        $this->post('/password/reset', ['email' => 'admin@admin.com'])->assertStatus(404);
    }

    public function test_login_form_does_not_offer_the_reset_link()
    {
        $resp = $this->get('/login');
        $resp->assertOk();
        $this->withHtml($resp)->assertElementNotExists('a[href$="/password/email"]');
    }

    public function test_the_link_comes_back_when_enabled()
    {
        config(['auth.password_reset_enabled' => true]);

        $resp = $this->get('/login');
        $this->withHtml($resp)->assertElementExists('a[href$="/password/email"]');
        $this->get('/password/email')->assertOk();
    }
}
