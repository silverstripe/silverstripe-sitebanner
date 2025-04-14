<?php

namespace NZTA\SiteBanner\Models;

use DateTime;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Versioned\Versioned;

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
 * @phpcs:disable SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingAnyTypeHint
 * @phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
 * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
 */
class SiteBanner extends DataObject
{

    /**
     * Constants of the supported banner types
     */
    protected const string TYPE_INFO = 'info';
    protected const string TYPE_WARNING = 'warning';
    protected const string TYPE_ALERT = 'alert';

    private static array $db = [
        'Content' => 'HTMLText', // see getContent()
        'Type' => 'Varchar(32)',
        'StartDate' => 'Datetime',
        'EndDate' => 'Datetime',
        'Sort' => 'Int', // only used when 'sortablegridfield' is installed
        'Dismiss' => 'Boolean', // allows users to dismiss banners for the remainder of their session
    ];

    /**
     * Map of a type identifier to a label visible in the CMS.
     * Type identifiers are commonly used for CSS classes.
     */
    private static array $types = [
        self::TYPE_INFO => 'Info',
        self::TYPE_WARNING => 'Warning',
        self::TYPE_ALERT => 'Alert',
    ];

    /**
     * Enforce start/end dates for banner
     *
     * @config
     */
    private static bool $embargo_enabled = true;

    /**
     * Will require at least one permission if multiple are provided
     *
     * @config
     */
    private static array $required_permission_codes = [
        'EDIT_SITECONFIG',
    ];

    /**
     * @config
     */
    private static bool $allow_html = true;

    private static string $default_sort = 'Sort';

    private static array $summary_fields = [
        'Type.UpperCase' => 'Type',
        'Content.Summary' => 'Content',
    ];

    private static string $table_name = 'SiteBanner';

    private static array $extensions = [
        Versioned::class,
    ];

    /**
     * @param bool $includerelations
     * @return array|string[]
     */
    public function fieldLabels($includerelations = true)
    {
        return array_merge(
            parent::fieldLabels($includerelations),
            [
                'Content' => _t(self::class . '.ContentFieldLabel', 'Banner content'),
                'Type' => _t(self::class . '.TypeFieldLabel', 'Banner type'),
                'StartDate' => _t(self::class . '.StartDateFieldLabel', 'Start date / time'),
                'EndDate' => _t(self::class . '.EndDateFieldLabel', 'End date / time'),
                'Dismiss' => _t(self::class . '.DismissLabel', 'Allow users to dismiss this banner'),
            ],
        );
    }

    public function getCMSFields(): FieldList
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields): void {
            $this->buildMainFields($fields);
            $this->buildEmbargoFields($fields);
        });

        return parent::getCMSFields();
    }

    /**
     * Build CMS fields related to main/standard fields
     */
    protected function buildMainFields(FieldList $fields): void
    {
        $fields->removeByName('Sort');

        $fields->dataFieldByName('Content')
            ->setRows(2)// indicate to authors that this should be kept short
            ->setDescription(_t(
                self::class . '.ContentFieldDesc',
                'Appears at the top of each page on the site. '
                . 'The banner will not display until content has been added.',
            ));

        $fields->replaceField(
            'Type',
            DropdownField::create('Type', $this->fieldLabel('Type'), $this->getTypeSource()),
        );

        // Use plain text or keep the HTML editor field for content
        if ($this->config()->get('allow_html')) {
            return;
        }

        $fields->replaceField('Content', TextField::create('Content', $this->fieldLabel('Content')));
    }

    /**
     * Build CMS fields related to Embargo feature
     */
    protected function buildEmbargoFields(FieldList $fields): void
    {
        if (!static::config()->embargo_enabled) {
            $fields->removeByName('StartDate');
            $fields->removeByName('EndDate');

            return;
        }

        $startDate = $fields->dataFieldByName('StartDate');
        $startDate->setDescription(_t(
            self::class . '.StartDateFieldDesc',
            'When to start showing the banner. Leave this blank to start showing the banner immediately.',
        ));

        $endDate = $fields->dataFieldByName('EndDate');
        $endDate->setDescription(_t(
            self::class . '.EndDateFieldDesc',
            'When to stop showing the banner. Leave this blank to show the banner indefinitely.',
        ));
    }

    public function getTypeSource(): array
    {
        $localised = [
            static::TYPE_INFO => _t(self::class . '.TypeLabelInfo', 'Info'),
            static::TYPE_WARNING => _t(self::class . '.TypeLabelWarning', 'Warning'),
            static::TYPE_ALERT => _t(self::class . '.TypeLabelAlert', 'Alert'),
        ];

        $source = [];

        foreach (static::config()->get('types') as $type => $title) {
            $source[$type] = array_key_exists($type, $localised)
                ? $localised[$type]
                : $title;
        }

        return $source;
    }

    /**
     * Check if the Site Banner should be displayed. It should be displayed if there is content
     * and the current date/time is within the start and end date/times for the banner.
     *
     * @throws \Exception
     */
    public function isActive(): bool
    {
        $config = $this->config();

        // Never display the banner if there's no content.
        if (!$this->Content) {
            return false;
        }

        if (!$config->embargo_enabled) {
            return true;
        }

        $isoFormat = DBDatetime::now()->Format(DBDatetime::ISO_DATETIME);
        $startDate = new DateTime($this->StartDate ?? $isoFormat);
        $endDate = new DateTime($this->EndDate ?? $isoFormat);
        $now = new DateTime($isoFormat);

        // Check if the current time falls between the start and end dates.
        return (!$this->StartDate || $startDate <= $now) && (!$this->EndDate || $endDate >= $now);
    }

    /**
     * @param Member|null $member
     * @return bool|int
     */
    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);

        return $extended ?? Permission::checkMember($member, $this->config()->required_permission_codes);
    }

    /**
     * @param Member|null $member
     * @param array $context
     * @return bool|int
     */
    public function canCreate($member = null, $context = [])
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);

        return $extended ?? Permission::checkMember($member, $this->config()->required_permission_codes);
    }

    /**
     * @param Member|null $member
     * @return bool|int
     */
    public function canEdit($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);

        return $extended ?? Permission::checkMember($member, $this->config()->required_permission_codes);
    }

    /**
     * @param Member|null $member
     * @return bool|int
     */
    public function canDelete($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);

        return $extended ?? Permission::checkMember($member, $this->config()->required_permission_codes);
    }

}
