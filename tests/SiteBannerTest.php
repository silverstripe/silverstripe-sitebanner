<?php
class SiteBannerTest extends SapphireTest
{

    public function testIsActiveWithoutEmbargoWithEmptyContent()
    {
        Config::inst()->update('SiteBanner', 'embargo_enabled', false);
        $banner = new SiteBanner();
        $banner->Content = null;
        $this->assertFalse($banner->isActive());
    }

    public function testIsActiveWithoutEmbargoWithContent()
    {
        Config::inst()->update('SiteBanner', 'embargo_enabled', false);
        $banner = new SiteBanner();
        $banner->Content = 'test';
        $this->assertTrue($banner->isActive());
    }

    public function testIsActiveWithEmbargoStartDate()
    {
        Config::inst()->update('SiteBanner', 'embargo_enabled', true);
        $banner = new SiteBanner();
        $banner->Content = 'test';
        $banner->StartDate = '2017-01-01 12:00:00';

        SS_Datetime::set_mock_now('2017-01-01 11:00:00');
        $this->assertFalse($banner->isActive());

        SS_Datetime::set_mock_now('2017-01-01 13:00:00');
        $this->assertTrue($banner->isActive());
    }

    public function testIsActiveWithEmbargoEndDate()
    {
        Config::inst()->update('SiteBanner', 'embargo_enabled', true);
        $banner = new SiteBanner();
        $banner->Content = 'test';
        $banner->EndDate = '2017-01-01 12:00:00';

        SS_Datetime::set_mock_now('2017-01-01 11:00:00');
        $this->assertTrue($banner->isActive());

        SS_Datetime::set_mock_now('2017-01-01 13:00:00');
        $this->assertFalse($banner->isActive());
    }
}
