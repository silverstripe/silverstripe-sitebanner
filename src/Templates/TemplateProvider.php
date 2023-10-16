<?php

namespace NZTA\SiteBanner\Templates;

use NZTA\SiteBanner\Models\SiteBanner;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\Requirements;
use SilverStripe\View\TemplateGlobalProvider;

class TemplateProvider implements TemplateGlobalProvider
{
    public static function get_template_global_variables(): array
    {
        return [
            'SiteBanners' => 'getSiteBanners',
        ];
    }

    /**
     * Get all displayable site banners
     */
    public static function getSiteBanners(?int $pageId = null, ?string $className = SiteTree::class): ArrayList
    {
        Requirements::css('nzta/silverstripe-sitebanner: client/css/site-banner.css');
        Requirements::javascript('nzta/silverstripe-sitebanner: client/javascript/site-banner.js');

        return SiteBanner::getBanners($pageId, $className)->filterByCallback(static function ($banner) {
            return $banner->isActive();
        });
    }
}
