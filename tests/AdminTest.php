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
        // Assert can't access model admin without environment var
        $this->assertFalse($admin->canView());

        Environment::setEnv('SITEBANNER_MODELADMIN', 1);
        // Assert can access model admin with environment var
        $this->assertTrue($admin->canView());
    }

}
