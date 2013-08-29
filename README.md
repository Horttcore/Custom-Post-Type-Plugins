# Custom Post Type Plugins

## Description

A WordPress custom post type for plugins. It fetches all information from WordPress.org and github

## Installation

* Put the plugin file in your plugin directory and activate it in your WP backend.

## Screenshots

![](https://raw.github.com/Horttcore/Custom-Post-Type-Plugins/master/screenshot-1.png)
![](https://raw.github.com/Horttcore/Custom-Post-Type-Plugins/master/screenshot-2.png)

## Notes

* External readme is cached for 24 hours in the frontend, visiting the plugin in the backend will refresh the cache

## Extendable

### Available Filters

*download-stats*
(array) Download stats from WordPress

*get_wordpress_plugin_information*
(obj) Plugin information pulled from WordPress.org except sections and compability

*plugin_information*
(obj) Plugin information pulled from WordPress.org except sections and compability used in the template tag

*plugin_information_$key*
(str) Filter title for a specific plugin_information key

*readme-html*
(str) Filtered readme in HTML format

*readme-markdown*
(str) Filtered readme in Markdown format

*readme-include-before*
(bool) Should the readme injected before the content
	Default: False

*readme-include-after*
(bool) Should the readme injected before the content
	Default: True

*the-readme-content*
(str) Readme content injected by the_content or by a shortcode

*wordpress-section-headline*
(str) Headline tag for WordPress headline
	Default: h2

*wordpress-section-title*
(str) The headline for the WordPress section

### Available Hooks

*plugin-repositories*
Called in the meta box for the plugin repositories after the github row

*plugin-repository-readme*
Called in the meta box for the readme selection after github

*meta_box_statistcs*
Called in the meta box for statistics after WordPress.org

## Frequently Asked Questions

### I've found a bug, what to do?

* Please report bugs and wishes at github ( https://github.com/Horttcore/Custom-Post-Type-Plugins/issues )

## Language Support

* english
* german

## Changelog

### 1.0
* First release
