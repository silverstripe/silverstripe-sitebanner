<?php

namespace NZTA\SiteBanner\Tests;

use NZTA\SiteBanner\Models\SiteBanner;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;

class SiteBannerTest extends SapphireTest
{

    public function testIsActiveWithoutEmbargoWithEmptyContent(): void
    {
        Config::inst()->merge(SiteBanner::class, 'embargo_enabled', false);
        $banner = new SiteBanner();
        $banner->Content = null;
        $this->assertFalse($banner->isActive());
    }

    public function testIsActiveWithoutEmbargoWithContent(): void
    {
        Config::inst()->merge('SiteBanner', 'embargo_enabled', false);
        $banner = new SiteBanner();
        $banner->Content = 'test';
        $this->assertTrue($banner->isActive());
    }

    public function testIsActiveWithEmbargoStartDate(): void
    {
        Config::inst()->merge('SiteBanner', 'embargo_enabled', true);
        $banner = new SiteBanner();
        $banner->Content = 'test';
        $banner->StartDate = '2017-01-01 12:00:00';

        DBDatetime::set_mock_now('2017-01-01 11:00:00');
        $this->assertFalse($banner->isActive());

        DBDatetime::set_mock_now('2017-01-01 13:00:00');
        $this->assertTrue($banner->isActive());
    }

    public function testIsActiveWithEmbargoEndDate(): void
    {
        Config::inst()->merge('SiteBanner', 'embargo_enabled', true);
        $banner = new SiteBanner();
        $banner->Content = 'test';
        $banner->EndDate = '2017-01-01 12:00:00';

        DBDatetime::set_mock_now('2017-01-01 11:00:00');
        $this->assertTrue($banner->isActive());

        DBDatetime::set_mock_now('2017-01-01 13:00:00');
        $this->assertFalse($banner->isActive());
    }
}
