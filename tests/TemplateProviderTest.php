<?php

namespace NZTA\SiteBanner\Tests;

use NZTA\SiteBanner\Models\SiteBanner;
use NZTA\SiteBanner\Templates\TemplateProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;

/**
 * @phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingAnyTypeHint
 */
class TemplateProviderTest extends SapphireTest
{
    public function testFiltersInactiveBanners(): void
    {
        // Mock data list with 3 site banners 2 active, 1 inactive
        $mockDataList = new class(SiteBanner::class) extends DataList {
            public function filterByCallback($callback)
            {
                return ArrayList::create([
                    SiteBanner::create([
                        'Content' => 'test',
                    ]),
                    SiteBanner::create([
                        'Content' => 'test2',
                    ]),
                    SiteBanner::create([
                        'Content' => '',
                    ]),
                ])->filterByCallback($callback);
            }
        };
        Injector::inst()->registerService($mockDataList, DataList::class);

        $banners = TemplateProvider::getSiteBanners();

        // Assert that active site banner returned
        self::assertContains('test', $banners->column('Content'));
        $this->assertCount(2, $banners);
    }
}
