<?php

namespace NZTA\SiteBanner\Extensions;

use NZTA\SiteBanner\Models\SiteBanner;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridFieldEditButton;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\GridFieldArchiveAction;

/**
 * Extend site banner functionality to allow for limiting banner to selected pages
 *
 * @method SiteBanner getOwner()
 */
class PageSelectionExtension extends DataExtension
{

    use Configurable;

    /**
     * Whether or not this extension is enabled
     *
     * @config
     */
    private static bool $enabled = false;

    private static array $many_many = [
        'Pages' => SiteTree::class,
    ];

    private static array $cascade_duplicates = [
        'Pages',
    ];

    public function updateCMSFields(FieldList $fields): void
    {
        $fields->removeByName('Pages');

        if (!$this->getOwner()->isInDB() || !self::config()->get('enabled')) {
            return;
        }

        $config = GridFieldConfig_RelationEditor::create();
        $config->removeComponentsByType(GridFieldEditButton::class);
        $config->removeComponentsByType(GridFieldAddNewButton::class);
        $config->removeComponentsByType(GridFieldArchiveAction::class);

        $pagesField = GridField::create('Pages', 'Limit banner to pages', $this->getOwner()->Pages(), $config);
        $pagesField->setDescription(
            'Select pages that you would like the banner to be visible in. ' .
            'If none selected, then visible in all pages.',
        );
        $fields->addFieldToTab('Root.Pages', $pagesField);
    }

    /**
     * Modify the query that returns the banner to be visible to the public user
     */
    public function onFrontendQuery(DataList $query, ?int $pageId = null): DataList
    {
        // Skip implementation if no page is provided or extension disabled
        if (!$pageId || !self::config()->get('enabled')) {
            return $query;
        }

        $rightTable = DataObject::getSchema()->tableName(SiteBanner::class);
        $pagesRelation = DataObject::getSchema()->manyManyComponent(SiteBanner::class, 'Pages');
        $leftTable = $pagesRelation['join'];

        // Search for banners that must be visible in current page only
        $query1 = $query->innerJoin(
            $leftTable,
            sprintf('%s.%s = "%s"."ID"', $leftTable, $pagesRelation['parentField'], $rightTable),
            $leftTable,
        )->where([
            '"' . $pagesRelation['join'] . '"."SiteTreeID" = ?' => $pageId,
        ]);

        if ($query1->count()) {
            return $query1;
        }

        // If no banner selected for current page, then return banners not defined to specific page
        return $query->leftJoin(
            $leftTable,
            sprintf('%s.%s = "%s"."ID"', $leftTable, $pagesRelation['parentField'], $rightTable),
            $leftTable,
        )->where([
            ['"' . $pagesRelation['join'] . '"."SiteTreeID" IS NULL',],
        ]);
    }

}
