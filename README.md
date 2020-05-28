# Site Wide Banners

[![Build Status](https://secure.travis-ci.org/NZTA/silverstripe-sitebanner.png?branch=master)](http://travis-ci.org/NZTA/silverstripe-sitebanner)

Allows CMS authors to create site-wide banners,
to alert visitors of important information regardless
of the page they're viewing.

## Features

 * Themeable templates
 * Configure type of alert (defaults to "info", "warning" and "alert")
 * Multiple concurrent alerts
 * Set start/end dates for alert
 * Permission controls
 * Localisation of CMS UI controls and labels
 * CMS users can make banners "dismissible", allowing users to hide banners after reading.
 * Optional: Rich-text editing (insert links and images)
 * Optional: Preview and publish through [versioneddataobjects](https://github.com/heyday/silverstripe-versioneddataobjects)
 * Optional: Sorting through [sortablegridfield](https://github.com/UndefinedOffset/SortableGridField)
 * Support for [subsites](https://github.com/silverstripe/silverstripe-subsites)

## Screenshot

![CMS Preview](docs/_img/cms.png)

CMS editing screen (with [versioneddataobjects](https://github.com/heyday/silverstripe-versioneddataobjects) enabled)

## Installation

	composer require nzta/silverstripe-sitebanner

## Configuration

Add the following to your YAML config to activate the module:

	SiteConfig:
	  extensions:
	    - SiteBannerSiteConfigExtension

The site banner can be configured in `admin/settings` now.

## Templates

In order to show the banners, you need to add them to your template:

	<% loop $SiteConfig.SiteBanners %>
        <div id="site-banner-$ID" class="site-banner site-banner-$Type" role="alert" data-id="$ID" aria-hidden="true">
            $Content
            <% if $Dismiss %>
                <button class="site-banner-close" aria-label="Close" data-id="$ID">×</button>
            <% end_if %>
        </div>
	<% end_loop %>

## Bootstrap Styles

If you're using Bootstrap, it's easy to get useful default styles for alerts
through a combination of [contextual backgrounds](http://getbootstrap.com/css/#helper-classes-backgrounds)
and [icons](http://getbootstrap.com/components/#glyphicons).

	<% loop $SiteConfig.SiteBanners %>
        <% if $Type == 'info' %>
            <p class="bg-info" role="alert">
                <span class="glyphicon glyphicon-info-sign" aria-hidden="true" />
                $Content
            </p>
        <% end_if %>
        <% if $Type == 'warning' %>
            <p class="bg-warning" role="alert">
                <span class="glyphicon glyphicon-warning-sign" aria-hidden="true" />
                $Content
            </p>
        <% end_if %>
        <% if $Type == 'alert' %>
            <p class="bg-danger" role="alert">
                <span class="glyphicon glyphicon-warning-sign" aria-hidden="true" />
                $Content
            </p>
        <% end_if %>
	<% end_loop %>

Examples on the SilverStripe default theme:

![Info styling](docs/_img/info.png)

![Warning styling](docs/_img/warning.png)

![Alert styling](docs/_img/alert.png)

## Permissions

By default, every author with access to the "Settings" section (`EDIT_SITECONFIG` permission code)
can set alerts. You can customise this by YAML configuration:

	SiteBanner:
	  required_permission_codes:
	    - ADMIN

## Sorting

You can allow authors to sort multiple alerts by installing
the [sortablegridfield](https://github.com/UndefinedOffset/SortableGridField) module.
It'll get automatically picked up by the code.

## Versioning and Previews

In case you want to make your status messages previewable by authors,
install the [versioneddataobjects](https://github.com/heyday/silverstripe-versioneddataobjects) module.
Then add the following code to your YAML configuration:

    SiteBanner:
      extensions:
        - 'Heyday\VersionedDataObjects\VersionedDataObject'


## Limitations

 * Does not trigger republish when [staticpublisher](https://github.com/silverstripe/silverstripe-staticpublisher) is used
