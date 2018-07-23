<?php

class SiteBanner extends DataObject
{

    private static $db = [
        'Content'     => 'HTMLText', // see getContent()
        'Type'        => 'Varchar(32)',
        'StartDate'   => 'SS_Datetime',
        'EndDate'     => 'SS_Datetime',
        'Sort'        => 'Int', // only used when 'sortablegridfield' is installed
        'Dismiss'     => 'Boolean', // allows users to dismiss banners for the remainder of their session
    ];

    /**
     * @var array Map of a type identifier to a label visible in the CMS.
     * Type identifiers are commonly used for CSS classes.
     */
    private static $types = [
        'info' => 'Info',
        'warning' => 'Warning',
        'alert' => 'Alert'
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
        'EDIT_SITECONFIG'
    ];

    /**
     * @var bool
     * @config
     */
    private static $allow_html = true;

    private static $default_sort = 'Sort';

    private static $summary_fields = [
        'Content' => 'Content.Summary'
    ];

    public function fieldLabels($includerelations = true)
    {
        return array_merge(
            parent::fieldLabels($includerelations),
            [
                'Content' => _t(
                    'SiteBanner.ContentFieldLabel',
                    'Banner content'
                ),
                'Type' => _t(
                    'SiteBanner.TypeFieldLabel',
                    'Banner type'
                ),
                'StartDate' => _t(
                    'SiteBanner.StartDateFieldLabel',
                    'Start date / time'
                ),
                'EndDate' => _t(
                    'SiteBanner.EndDateFieldLabel',
                    'End date / time'
                ),
                'Dismiss' => _t(
                    'SiteBanner.DismissLabel',
                    'Allow users to temporarily dismiss this banner in their browser session'
                )
            ]
        );
    }

    /**
     * @param $type String
     * @return String
     */
    public function getTypeSource()
    {
        $localised = [
            'info' => _t('SiteBanner.TypeLabelInfo', 'Info'),
            'warning' => _t('SiteBanner.TypeLabelWarning', 'Warning'),
            'alert' => _t('SiteBanner.TypeLabelAlert', 'Alert')
        ];

        $source = [];
        foreach ($this->config()->types as $type => $title) {
            $source[$type] = array_key_exists($type, $localised) ? $localised[$type] : $title;
        }

        return $source;
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('Sort');

        $fields->dataFieldByName('Content')
            ->setRows(2) // indicate to authors that this should be kept short
            ->setDescription(_t(
                'SiteBanner.ContentFieldDesc',
                'Appears at the top of each page on the site. The banner will not display until content has been added.'
            ));

        $fields->replaceField(
            'Type',
            DropdownField::create('Type', $this->fieldLabel('Type'), $this->getTypeSource())
        );

        if (!$this->config()->allow_html) {
            $fields->replaceField('Content', new TextField('Content', $this->fieldLabel('Content')));
        }

        if (SiteBanner::config()->embargo_enabled) {
            $startDate = $fields->dataFieldByName('StartDate');
            $startDate->setDescription(_t(
                'SiteBanner.StartDateFieldDesc',
                'When to start showing the banner. Leave this blank to start showing the banner immediately.'
            ));
            $startDate->getDateField()
                ->setConfig('showcalendar', 1)->setDescription('Date');
            $startDate->getTimeField()
                ->setAttribute('placeholder', '12:30 pm')->setDescription('Time e.g. 12:30 pm');

            $endDate = $fields->dataFieldByName('EndDate');
            $endDate->setDescription(_t(
                'SiteBanner.EndDateFieldDesc',
                'When to stop showing the banner. Leave this blank to show the banner indefinitely.'
            ));
            $endDate->getDateField()
                ->setConfig('showcalendar', 1)->setDescription('Date');
            $endDate->getTimeField()
                ->setAttribute('placeholder', '12:30 pm')->setDescription('Time e.g. 12:30 pm');
        } else {
            $fields->removeByName('StartDate');
            $fields->removeByName('EndDate');
        }

        return $fields;
    }

    /**
     * Check if the Site Banner should be displayed. It should be displayed if there is content
     * and the current date/time is within the start and end date/times for the banner.
     *
     * @return boolean
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
        $endDate = new DateTime($this->EndDate);
        $now = new DateTime(SS_Datetime::now()->Format(DateTime::ISO8601));

        // Check if the current time falls between the start and end dates.
        if ((!$this->StartDate || $startDate <= $now) && (!$this->EndDate || $endDate >= $now)) {
            return true;
        }

        return false;
    }

    /**
     * @param null|Member $member
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
     * @return bool|int
     */
    public function canCreate($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
        if ($extended !== null) {
            return $extended;
        }
        return Permission::checkMember($member, $this->config()->required_permission_codes);
    }

    /**
     * @param null|Member $member
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
