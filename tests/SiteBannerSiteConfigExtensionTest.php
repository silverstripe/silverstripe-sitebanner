<?php
class SiteBannerSiteConfigExtensionTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected $requiredExtensions = [
        'SiteConfig' => [
            'SiteBannerSiteConfigExtension'
        ]
    ];

    public function testFiltersInactiveBanners()
    {
        $activeBanner = new SiteBanner();
        $activeBanner->Content = 'test';
        $activeBanner->write();

        $inactiveBanner = new SiteBanner();
        $inactiveBanner->Content = '';
        $inactiveBanner->write();

        $banners = singleton('SiteConfig')->getSiteBanners();
        $this->assertContains($activeBanner->ID, $banners->column('ID'));
        $this->assertNotContains($inactiveBanner->ID, $banners->column('ID'));
    }
}
