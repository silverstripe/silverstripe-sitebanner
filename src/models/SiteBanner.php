<?php

namespace NZTA\SiteBanner\Models;

use DateTime;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\FieldList;

/**
 * Class \NZTA\SiteBanner\Models\SiteBanner
 *
 * @property string $Content
 * @property string $Type
 * @property string $StartDate
 * @property string $EndDate
 * @property int $Sort
 * @property int $Version
 * @mixin \SilverStripe\Versioned\Versioned
 */
class SiteBanner extends DataObject
{

    /**
     * @var array
     */
    private static $db = [
        'Content'   => 'HTMLText', // see getContent()
        'Type'      => 'Varchar(32)',
        'StartDate' => 'Datetime',
        'EndDate'   => 'Datetime',
        'Sort'      => 'Int', // only used when 'sortablegridfield' is installed
        'Dismiss'   => 'Boolean', // allows users to dismiss banners for the remainder of their session
    ];

    /**
     * @var array Map of a type identifier to a label visible in the CMS.
     * Type identifiers are commonly used for CSS classes.
     */
    private static $types = [
        'info'    => 'Info',
        'warning' => 'Warning',
        'alert'   => 'Alert',
    ];

    /**
     * @var bool Enforce start/end dates for banner
     * @config
     */
    private static $embargo_enabled = true;

    /**
     * @var array Will require at least one permission if multiple are provided
     * @config
     */
    private static $required_permission_codes = [
        'EDIT_SITECONFIG',
    ];

    /**
     * @var bool
     * @config
     */
    private static $allow_html = true;

    /**
     * @var string
     */
    private static $default_sort = 'Sort';

    /**
     * @var array
     */
    private static $summary_fields = [
        'Content.Summary' => 'Content',
    ];

    /**
     * @var string
     */
    private static $table_name = 'SiteBanner';

    /**
     * @var array
     */
    private static $extensions = [
        Versioned::class,
    ];

    public function fieldLabels($includerelations = true)
    {
        return array_merge(
            parent::fieldLabels($includerelations),
            [
                'Content'   => _t(self::class . '.ContentFieldLabel', 'Banner content'),
                'Type'      => _t(self::class . '.TypeFieldLabel', 'Banner type'),
                'StartDate' => _t(self::class . '.StartDateFieldLabel', 'Start date / time'),
                'EndDate'   => _t(self::class . '.EndDateFieldLabel', 'End date / time'),
                'Dismiss'   => _t(self::class . '.DismissLabel', 'Allow users to dismiss this banner'),
            ]
        );
    }

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->removeByName('Sort');

            $fields->dataFieldByName('Content')
                ->setRows(2)// indicate to authors that this should be kept short
                ->setDescription(_t(
                    self::class . '.ContentFieldDesc',
                    'Appears at the top of each page on the site. '
                    . 'The banner will not display until content has been added.'
                ));

            $fields->replaceField(
                'Type',
                DropdownField::create('Type', $this->fieldLabel('Type'), $this->getTypeSource())
            );

            if (!$this->config()->allow_html) {
                $fields->replaceField('Content', new TextField('Content', $this->fieldLabel('Content')));
            }

            if (static::config()->embargo_enabled) {
                $startDate = $fields->dataFieldByName('StartDate');
                $startDate->setDescription(_t(
                    self::class . '.StartDateFieldDesc',
                    'When to start showing the banner. Leave this blank to start showing the banner immediately.'
                ));

                $endDate = $fields->dataFieldByName('EndDate');
                $endDate->setDescription(_t(
                    self::class . '.EndDateFieldDesc',
                    'When to stop showing the banner. Leave this blank to show the banner indefinitely.'
                ));
            } else {
                $fields->removeByName('StartDate');
                $fields->removeByName('EndDate');
            }
        });

        return parent::getCMSFields();
    }

    /**
     * @return array
     */
    public function getTypeSource()
    {
        $localised = [
            'info'    => _t(self::class . '.TypeLabelInfo', 'Info'),
            'warning' => _t(self::class . '.TypeLabelWarning', 'Warning'),
            'alert'   => _t(self::class . '.TypeLabelAlert', 'Alert'),
        ];

        $source = [];
        foreach (static::config()->get('types') as $type => $title) {
            $source[$type] = array_key_exists($type, $localised) ? $localised[$type] : $title;
        }

        return $source;
    }

    /**
     * Check if the Site Banner should be displayed. It should be displayed if there is content
     * and the current date/time is within the start and end date/times for the banner.
     *
     * @return boolean
     * @throws \Exception
     */
    public function isActive()
    {
        $config = $this->config();

        // Never display the banner if there's no content.
        if (!$this->Content) {
            return false;
        }

        if (!$config->embargo_enabled) {
            return true;
        }

        $startDate = new DateTime($this->StartDate);
        $endDate   = new DateTime($this->EndDate);
        $now       = new DateTime(DBDatetime::now()->Format(DBDatetime::ISO_DATETIME));

        // Check if the current time falls between the start and end dates.
        return ((!$this->StartDate || $startDate <= $now) && (!$this->EndDate || $endDate >= $now));
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::checkMember($member, $this->config()->required_permission_codes);
    }

    /**
     * @param null|Member $member
     * @param array $context
     *
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::checkMember($member, $this->config()->required_permission_codes);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::checkMember($member, $this->config()->required_permission_codes);
    }

    /**
     * @param null|Member $member
     *
     * @return bool|int
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }

        return Permission::checkMember($member, $this->config()->required_permission_codes);
    }
}
