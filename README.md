# MuseumPlus for CraftCMS plugin for Craft CMS 4.x

Allows to import MuseumsPlus Collection data to Craft CMS and publish data.

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 4.7 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require /museum-plus-for-craft-cms

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for MuseumPlus for CraftCMS.

## MuseumPlus for CraftCMS Overview

MuseumPlus for CraftCMS automatically imports items from one or more collections and makes them available to Craft.

## Configuring MuseumPlus for CraftCMS

Once the plugin is installed, you can configure it in the Craft Control Panel under Settings → MuseumPlus for CraftCMS.
You will need to provide:
- the MuseumPlus classifier
- the hostname for the MuseumPlus API
- the MuseumPlus API username
- the MuseumPlus API password

After saving the settings you will see the list of collections you have access to: choose at least one collection and save the settings.
You should also choose the filesystem where the media will be stored: note that the two subfolders `Items` and `Multimedia` will be automatically created in the root of the filesystem.
You can specify which kind of files to import: the plugin will download the files and store them in the chosen filesystem.

The shell command `./craft museum-plus-for-craft-cms/collection/update-items` will import the data from the selected collections.
We advise to set up a cron job to run this command regularly.

## Using MuseumPlus for CraftCMS

Frontend example to display the items:
```twig
{% set items = craft.museumPlusForCraftCms.items.all() %}
{% for item in items %}
    <h2>{{ item.title }}</h2>
    <p>{{ item.description }}</p>
    <img src="{{ item.image.getUrl() }}" alt="{{ item.title }}">
{% endfor %}
```

Frontend example to display a single item:
```twig
{% set item = craft.museumPlusForCraftCms.items.one() %}
<h2>{{ item.title }}</h2>
```



## MuseumPlus for CraftCMS Roadmap

Some things to do, and ideas for potential features:

* Integrate Google Gemini for better titles and descriptions

Brought to you by [Furbo GmbH](https://furbo.ch)
