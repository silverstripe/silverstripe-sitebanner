<?php

namespace NZTA\SiteBanner\Tests;

use NZTA\SiteBanner\Extensions\SiteConfigExtension;
use SilverStripe\Core\Environment;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * @phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 */
class SiteConfigExtensionTest extends SapphireTest
{
    public function testIsSiteConfigGridFieldActive(): void
    {
        $extension = new SiteConfigExtension();

        $fields = (new SiteConfig())->getCMSFields();
        $extension->updateCMSFields($fields);

        $this->assertNull($fields->dataFieldByName('SiteBanners'));

        Environment::setEnv('SITEBANNER_SITECONFIG', 1);
        $extension->updateCMSFields($fields);

        $this->assertInstanceOf(GridField::class, $fields->dataFieldByName('SiteBanners'));
    }
}
