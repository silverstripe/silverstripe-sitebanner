<?php
/**
 * Allows editing of site banner data "globally".
 */
class SiteBannerSiteConfigExtension extends DataExtension
{

    public function updateCMSFields(FieldList $fields)
    {
        $fields->findOrMakeTab(
            'Root.SiteBanner',
            _t('SiteBanner.TabTitle', 'Site Banners')
        );

        $gridConfig = GridFieldConfig_RecordEditor::create();
        $grid = GridField::create('SiteBanners', null, SiteBanner::get())
            ->setConfig($gridConfig);

        $gridConfig->removeComponentsByType('GridFieldPaginator');
        $gridConfig->removeComponentsByType('GridFieldPageCount');

        if (class_exists('GridFieldSortableRows')) {
            $grid->getConfig()->addComponent(new GridFieldSortableRows('Sort'));
        }

        if (class_exists('Heyday\VersionedDataObjects\VersionedDataObjectDetailsForm')) {
            $gridConfig->removeComponentsByType('GridFieldDetailForm');
            $gridConfig->addComponent(new Heyday\VersionedDataObjects\VersionedDataObjectDetailsForm());
        }

        $fields->addFieldToTab(
            'Root.SiteBanner',
            $grid
        );
    }

    /**
     * Get all displayable site banners
     *
     * @return DataList
     */
    public function getSiteBanners()
    {
        Requirements::css(SITE_BANNER_DIR . '/css/site-banner.css');
        Requirements::javascript(SITE_BANNER_DIR . '/javascript/site-banner.js');

        return SiteBanner::get()->filterByCallback(function ($banner) {
            return $banner->isActive();
        });
    }
}
