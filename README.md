# MuseumPlus for CraftCMS plugin for Craft CMS 4.x

Allows to import MuseumsPlus Collection data to Craft CMS and publish data.

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 4.7 or later and php 8.1 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require furbo/museum-plus-for-craft-cms

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for MuseumPlus for CraftCMS.

## Overview

MuseumPlus for CraftCMS automatically imports items from one or more collections and makes them available to Craft.

## Configuration

Once the plugin is installed, you can configure it in the Craft Control Panel under Settings → MuseumPlus for CraftCMS.
You will need to provide:
- the MuseumPlus classifier
- the hostname for the MuseumPlus API
- the MuseumPlus API username
- the MuseumPlus API password

After saving the settings you will see the list of collections you have access to: choose at least one collection and save the settings.
You should also choose the filesystem where the media will be stored: note that the two subfolders `Items` and `Multimedia` will be automatically created in the root of the filesystem.
You can specify which kind of files to import: the plugin will download the files and store them in the chosen filesystem.
In the settings there is a section to define the URI format and template to be used to show collection items in the frontend.

The shell command `./craft museum-plus-for-craft-cms/collection/update-items` will import the data from the selected collections.
We advise to set up a cron job to run this command regularly.

## Usage

In the backend you will see a new section called "Collection" where you can see the imported collections and their items

Frontend example to display the items:
```
{% set items = craft.museumPlusForCraftCms.items.all() %}
{% for item in items %}
    <h2>{{ item.title }}</h2>
    <p>{{ item.description }}</p>
    <img src="{{ item.image.getUrl() }}" alt="{{ item.title }}">
{% endfor %}
```

Frontend example to display a single item:
```
{% set attachment = entry.getAttachment() %}
{% if attachment %}
  {% set myAsset = attachment %}
  {% if myAsset %}
      {% set myAssetUrl = myAsset.getUrl() %}
      <img 
      src="{{ myAsset.getUrl("transformL") }}" alt="{{ myAsset.title }}" @click="$store.modal.updateModalContent('<img src=\'{{myAsset.getUrl()}}\'>', false)" class="cursor-pointer max-w-full max-h-[70vh]" />
  {% endif %}
{% endif %}
<h2>{{ item.title }}</h2>
```



## Roadmap

* Integrate Google Gemini for better titles and descriptions

Brought to you by [Furbo GmbH](https://furbo.ch)
