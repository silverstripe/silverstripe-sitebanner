<?php

namespace NZTA\SiteBanner\Extensions;

use NZTA\SiteBanner\Models\SiteBanner;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Core\Environment;
use SilverStripe\View\Requirements;

/**
 * Allows editing of site banner data "globally".
 *
 * @property \SilverStripe\SiteConfig\SiteConfig|\NZTA\SiteBanner\Extensions\SiteConfigExtension $owner
 */
class SiteConfigExtension extends DataExtension
{

    public function updateCMSFields(FieldList $fields)
    {
        $fields->findOrMakeTab(
            'Root.SiteBanner',
            _t(self::class . '.TabTitle', 'Site Banners')
        );

        $gridConfig = GridFieldConfig_RecordEditor::create();
        $grid       = GridField::create('SiteBanners', null, SiteBanner::get())
            ->setConfig($gridConfig);

        $gridConfig->removeComponentsByType(GridFieldPaginator::class);
        $gridConfig->removeComponentsByType(GridFieldPageCount::class);
        $gridConfig->removeComponentsByType(GridFieldDeleteAction::class);

        if (class_exists('Symbiote\GridFieldExtensions\GridFieldOrderableRows')) {
            $grid->getConfig()->addComponent(new \Symbiote\GridFieldExtensions\GridFieldOrderableRows('Sort'));
        }

        $fields->addFieldToTab(
            'Root.SiteBanner',
            $grid
        );
    }

    /**
     * Get all displayable site banners
     *
     * @return ArrayList
     */
    public function getSiteBanners()
    {
        Requirements::css('nzta/silverstripe-sitebanner: client/css/site-banner.css');
        Requirements::javascript('nzta/silverstripe-sitebanner: client/javascript/site-banner.js');

        return SiteBanner::get()->filterByCallback(function ($banner) {
            return $banner->isActive();
        });
    }
}
