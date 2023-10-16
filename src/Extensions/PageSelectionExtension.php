<?php

namespace NZTA\SiteBanner\Extensions;

use NZTA\SiteBanner\Models\SiteBanner;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Forms\Tab;
use SilverStripe\Forms\TabSet;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DB;
use SilverStripe\Versioned\GridFieldArchiveAction;

/**
 * Extension to allow banners to be limited to specific pages or dataobjects.
 *
 * Provides CMS UI for selecting pages/dataobjects and visibility mode, and
 * query logic for frontend filtering based on selection.
 *
 * @method SiteBanner getOwner()
 */
class PageSelectionExtension extends DataExtension
{

    use Configurable;

    /**
     * Banner visibility mode constants.
     * - all: show on all pages
     * - include: show only on selected pages
     * - exclude: show on all except selected pages
     */
    protected const VISIBILITY_ALL = 'all';
    protected const VISIBILITY_INCLUDE = 'include';
    protected const VISIBILITY_EXCLUDE = 'exclude';

    /**
     * Enable/disable this extension via config.
     *
     * @config
     */
    private static bool $enabled = false;

    private static array $db = [
        'VisibilityMode' => 'Varchar',
    ];

    private static array $many_many = [
        'Pages' => SiteTree::class,
    ];

    private static array $defaults = [
        'VisibilityMode' => self::VISIBILITY_ALL,
    ];

    private static array $cascade_duplicates = [
        'Pages',
    ];

    /**
     * Update CMS fields to allow selection of pages and visibility mode for banners.
     */
    public function updateCMSFields(FieldList $fields): void
    {
        // Remove auto-generated grid fields for configured dataobjects (if any)
        $dataobjects = (array)static::config()->get('dataobjects');

        foreach ($dataobjects as $name => $className) {
            $fields->removeByName($name);
        }

        $fields->removeByName(['Pages', 'VisibilityMode']);

        // Only show fields if the record is in the database and the feature is enabled
        if (!$this->getOwner()->isInDB() || !self::config()->get('enabled')) {
            return;
        }

        // Create a tab set for page selection and related dataobjects
        $tabSet = TabSet::create('Pages');

        // Main tab for direct page selection - Add grid field for selecting pages
        $tabSet->push($tab = Tab::create('PagesTab', 'Pages'));
        $tab->push($this->createGridField('Pages', 'pages'));

        // Add additional tabs for each configured dataobject (if any)
        $dataobjects = (array)static::config()->get('dataobjects');

        foreach ($dataobjects as $name => $className) {
            $title = Injector::inst()->get($className)->i18n_plural_name();
            $tab = Tab::create(sprintf('%sTab', $name), $title);
            $tabSet->push($tab);
            $tab->push($this->createGridField($name, $title));
        }

        // Add the visibility mode options and the tab set to the CMS
        $fields->addFieldsToTab('Root.Pages', [
            OptionsetField::create('VisibilityMode', 'Visibility mode', $this->getVisibilityModeOptions()),
            $tabSet,
        ]);
    }

    /**
     * Get the available options for visibility mode.
     *
     * @return array
     */
    protected function getVisibilityModeOptions(): array
    {
        return [
            self::VISIBILITY_ALL => 'All — Banner is visible on all pages.',
            self::VISIBILITY_INCLUDE => 'Include — Banner is visible only on the selected pages.',
            self::VISIBILITY_EXCLUDE => 'Exclude — Banner is visible on all pages except the selected ones.',
        ];
    }

    /**
     * Create a grid field for selecting related objects (pages or dataobjects).
     *
     * @param string $name The relation name
     * @param string $title The grid field title
     */
    protected function createGridField(string $name, string $title): GridField
    {
        $config = GridFieldConfig_RelationEditor::create();
        // Remove edit, add new, and archive actions for this grid (read-only selection)
        $config->removeComponentsByType(GridFieldEditButton::class);
        $config->removeComponentsByType(GridFieldAddNewButton::class);
        $config->removeComponentsByType(GridFieldArchiveAction::class);

        $gridField = GridField::create($name, $title, $this->getOwner()->getManyManyComponents($name), $config);
        $gridField->setDescription(sprintf(
            'Select the %s you would like to include or exclude this banner from. ' .
            'If none are selected, the banner will be visible on all %s.',
            strtolower($title),
            strtolower($title),
        ));

        return $gridField;
    }

    /**
     * Modify the query that returns the banner to be visible to the public user.
     *
     * This method applies the visibility mode logic for banners:
     * - All: visible everywhere
     * - Include: visible only on selected pages/dataobjects
     * - Exclude: visible everywhere except selected pages/dataobjects
     *
     * @param DataList $query The original query (passed by reference)
     * @param int|null $pageId The current page ID
     * @param string|null $className The related class name (e.g. SiteTree::class)
     * @phpcs:disable SlevomatCodingStandard.PHP.DisallowReference.DisallowedPassingByReference
     */
    public function onFrontendQuery(DataList &$query, ?int $pageId = null, ?string $className = null): DataList
    {
        // If no page or class or extension is disabled, skip filtering
        if (!$pageId || !$className || !self::config()->get('enabled')) {
            return $query;
        }

        // Find the many_many relation name for the given class
        // If no such relation, return original query
        $manyMany = $this->getOwner()->manyMany();
        $relation = array_search($className, $manyMany, true);

        if ($relation === false) {
            return $query;
        }

        // Get join table and field info for the relation
        $rightTable = DataObject::getSchema()->tableName(SiteBanner::class);
        $pagesRelation = DataObject::getSchema()->manyManyComponent(SiteBanner::class, $relation);
        $leftTable = $pagesRelation['join'];
        $includeAlias = 'SB_Include';
        $excludeAlias = 'SB_Exclude';

        // LEFT JOIN for include mode: join only if banner is set to include and page matches
        $query = $query->leftJoin(
            $leftTable,
            sprintf(
                '%s.%s = "%s"."ID" AND "%s".VisibilityMode = ? AND %s.%s = ?',
                $includeAlias,
                $pagesRelation['parentField'],
                $rightTable,
                $rightTable,
                $includeAlias,
                $pagesRelation['childField'],
            ),
            $includeAlias,
            20,
            [
                self::VISIBILITY_INCLUDE,
                $pageId,
            ],
        );

        // LEFT JOIN for exclude mode: join only if banner is set to exclude and page matches
        $query = $query->leftJoin(
            $leftTable,
            sprintf(
                '%s.%s = "%s"."ID" AND "%s".VisibilityMode = ? AND %s.%s = ?',
                $excludeAlias,
                $pagesRelation['parentField'],
                $rightTable,
                $rightTable,
                $excludeAlias,
                $pagesRelation['childField'],
            ),
            $excludeAlias,
            20,
            [
                self::VISIBILITY_EXCLUDE,
                $pageId,
            ],
        );

        // WHERE logic:
        // - All: show if mode is all
        // - Include: show if mode is include and join found (banner is for this page)
        // - Exclude: show if mode is exclude and join not found (banner is NOT for this page)
        $query = $query->whereAny([
            'VisibilityMode = ?' => self::VISIBILITY_ALL,
            sprintf('VisibilityMode = ? AND %s.ID IS NOT NULL', $includeAlias) => self::VISIBILITY_INCLUDE,
            sprintf('VisibilityMode = ? AND %s.ID IS NULL', $excludeAlias) => self::VISIBILITY_EXCLUDE,
        ]);

        return $query;
    }

    /**
     * Add extra many_many config for additional dataobjects if configured.
     * This method is called by Silverstripe during dev/build.
     *
     * @inheritDoc
     * @phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public static function get_extra_config($class = null, $extensionClass = null, $args = null): array
    {
        $dataobjects = (array)static::config()->get('dataobjects');

        if (!count($dataobjects)) {
            return [];
        }

        return [
            'many_many' => $dataobjects,
        ];
    }

    /**
     * Ensure database indexes exist for all many_many relations (including Pages).
     *
     * This method is called by Silverstripe during dev/build to add indexes for
     * all configured dataobjects and the Pages relation, improving query performance
     * for banner lookups by relation.
     */
    public function augmentDatabase(): void
    {
        $dataobjects = (array)static::config()->get('dataobjects');
        $dataobjects['Pages'] = SiteTree::class;

        foreach ($dataobjects as $name => $className) {
            $dbTable = DataObject::getSchema()->baseDataTable($className);

            DB::require_index(
                sprintf('SiteBanner_%s', $name),
                sprintf('Banner%sCombined', $name),
                [
                    'type' => 'index',
                    'columns' => ['SiteBannerID', sprintf('%sID', $dbTable)],
                ],
            );
        }
    }
}
