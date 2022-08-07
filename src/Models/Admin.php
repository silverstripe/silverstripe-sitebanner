<?php

namespace NZTA\SiteBanner\Models;

use SilverStripe\Admin\ModelAdmin;
use SilverStripe\Core\Environment;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Security\Member;

class Admin extends ModelAdmin
{
    private static array $managed_models = [
        'banners' => [
            'title' => 'Site banners',
            'dataClass' => SiteBanner::class,
        ],
    ];

    private static string $url_segment = 'site-banners';

    private static float $menu_priority = 1;

    private static string $menu_title = 'Site banners';

    private static string $menu_icon_class = 'font-icon-attention';

    /**
     * @param Member|null $member
     * @return bool
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
     */
    public function canView($member = null)
    {
        return Environment::getEnv('SITEBANNER_MODELADMIN') && parent::canView($member);
    }

    public function getGridFieldConfig(): GridFieldConfig
    {
        $config = parent::getGridFieldConfig();

        if (class_exists('Symbiote\GridFieldExtensions\GridFieldOrderableRows')) {
            $config->addComponent(new \Symbiote\GridFieldExtensions\GridFieldOrderableRows('Sort'));
        }

        return $config;
    }

}
