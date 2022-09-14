<?php

namespace NZTA\SiteBanner\Tests;

use NZTA\SiteBanner\Models\Admin;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;

/**
 * @phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 */
class AdminTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testIsModelAdminActive(): void
    {
        $this->logInWithPermission('ADMIN');

        $admin = new Admin();

        // Assert can access model admin accessible by default
        $this->assertTrue($admin->canView());

        Environment::setEnv('SITEBANNER_SITECONFIG', true);

        // Assert can't access model admin when site config environment var is true
        $this->assertFalse($admin->canView());
    }

}
