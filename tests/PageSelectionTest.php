<?php

namespace NZTA\SiteBanner\Tests;

use NZTA\SiteBanner\Extensions\PageSelectionExtension;
use NZTA\SiteBanner\Models\SiteBanner;
use NZTA\SiteBanner\Templates\TemplateProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLInsert;

/**
 * @phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
 */
class PageSelectionTest extends SapphireTest
{
    protected static $fixture_file = 'PageSelectionTest.yml';

    public function testVisibleInHomepageOnly(): void
    {
        PageSelectionExtension::config()->set('enabled', true);

        // All banners - we have 3
        $banners = TemplateProvider::getSiteBanners();

        // Pick a banner
        $first = $banners->first();

        // Fake page ID
        $pageId = 3;

        // Add the picked banner to be visible in $pageId
        $pagesRelation = DataObject::getSchema()->manyManyComponent(SiteBanner::class, 'Pages');
        SQLInsert::create($pagesRelation['join'], [
            'SiteBannerID' => $first->ID,
            'SiteTreeID' => $pageId,
        ])->execute();

        // Assert on a page we see 2 banners as one visible in $pageId only
        self::assertEquals(2, TemplateProvider::getSiteBanners(4)->count());

        // Assert one banner visible in $pageId
        self::assertEquals(1, TemplateProvider::getSiteBanners($pageId)->count());

        // Assert banners other than the picked one are visible in other pages
        self::assertEquals(
            $banners->exclude('ID', $first->ID)->column('ID'),
            TemplateProvider::getSiteBanners(4)->column('ID'),
        );

        // Move banners to $pageId
        foreach ($banners as $banner) {
            SQLInsert::create($pagesRelation['join'], [
                'SiteBannerID' => $banner->ID,
                'SiteTreeID' => $pageId,
            ])->execute();
        }

        // No banner to display
        self::assertEquals(0, TemplateProvider::getSiteBanners(4)->count());

        // All banners visible in $pageId
        self::assertEquals($banners->count(), TemplateProvider::getSiteBanners($pageId)->count());

        // Disable extension
        PageSelectionExtension::config()->set('enabled', false);

        // banners visible in all pages
        self::assertEquals(3, TemplateProvider::getSiteBanners(4)->count());
    }

    public function testPagesField(): void
    {
        PageSelectionExtension::config()->set('enabled', false);

        // Assert new records does not show grid field for pages
        $siteBanner1 = SiteBanner::create();
        $this->assertNull($siteBanner1->getCMSFields()->dataFieldByName('Pages'));

        // Assert existing records does not show grid field for pages if extension not added
        $siteBanner2 = $this->objFromFixture(SiteBanner::class, 'banner1');
        $this->assertTrue($siteBanner2->exists());
        $this->assertNull($siteBanner2->getCMSFields()->dataFieldByName('Pages'));

        // Assert existing records does show grid field for pages if extension added
        PageSelectionExtension::config()->set('enabled', true);
        $this->assertInstanceOf(GridField::class, $siteBanner2->getCMSFields()->dataFieldByName('Pages'));
    }
}
