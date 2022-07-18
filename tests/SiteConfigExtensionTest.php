<?php

namespace NZTA\SiteBanner\Tests;

use NZTA\SiteBanner\Models\SiteBanner;
use NZTA\SiteBanner\Extensions\SiteConfigExtension;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\SiteConfig\SiteConfig;

class SiteConfigExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $required_extensions = [
        SiteConfig::class => [
            SiteConfigExtension::class
        ]
    ];

    public function testFiltersInactiveBanners(): void
    {
        $activeBanner = new SiteBanner();
        $activeBanner->Content = 'test';
        $activeBanner->write();

        $inactiveBanner = new SiteBanner();
        $inactiveBanner->Content = '';
        $inactiveBanner->write();

        $banners = singleton(SiteConfig::class)->getSiteBanners();
        $this->assertContains($activeBanner->ID, $banners->column('ID'));
        $this->assertNotContains($inactiveBanner->ID, $banners->column('ID'));
    }
}
