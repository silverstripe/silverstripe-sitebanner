<?php

namespace NZTA\SiteBanner\Extensions;

use NZTA\SiteBanner\Models\SiteBanner;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use SilverStripe\Forms\GridField\GridFieldPageCount;
use SilverStripe\Forms\GridField\GridFieldPaginator;
use SilverStripe\ORM\DataExtension;

/**
 * Allows editing of site banner data "globally".
 *
 * @property \SilverStripe\SiteConfig\SiteConfig|\NZTA\SiteBanner\Extensions\SiteConfigExtension $owner
 */
class SiteConfigExtension extends DataExtension
{

    public function updateCMSFields(FieldList $fields): void
    {
        if (!Environment::getEnv('SITEBANNER_SITECONFIG')) {
            return;
        }

        $fields->findOrMakeTab(
            'Root.SiteBanner',
            _t(self::class . '.TabTitle', 'Site Banners'),
        );

        $gridConfig = GridFieldConfig_RecordEditor::create();
        $grid = GridField::create('SiteBanners', null, SiteBanner::get())
            ->setConfig($gridConfig);

        $gridConfig->removeComponentsByType(GridFieldPaginator::class);
        $gridConfig->removeComponentsByType(GridFieldPageCount::class);
        $gridConfig->removeComponentsByType(GridFieldDeleteAction::class);

        if (class_exists('Symbiote\GridFieldExtensions\GridFieldOrderableRows')) {
            $grid->getConfig()->addComponent(\Symbiote\GridFieldExtensions\GridFieldOrderableRows::create('Sort'));
        }

        $fields->addFieldToTab('Root.SiteBanner', $grid);
    }

}
