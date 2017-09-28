=== TranslatePress - Translate Multilingual sites ===
Contributors: cozmoslabs, razvan.mo, madalin.ungureanu, cristophor
Donate link: https://www.cozmoslabs.com/
Tags: translate, translation, multilingual, automatic translation, front-end translation, google translate
Requires at least: 3.1.0
Tested up to: 4.8.2
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily translate your entire site and go multilingual, with full support for WooCommerce, complex themes and site builders. Integrates with Google Translate.
 
== Description ==

**Experience a better way to translate your WordPress site and go multilingual, directly from the front-end using a friendly user interface.**

The interface allows you to translate the entire page at once, including output from shortcodes, forms and page builders. It also works out of the box with WooCommerce.

Built the WordPress way, TranslatePress - Multilingual is a GPL and self hosted plugin, meaning you'll own all your translations, forever.

= Multilingual Features =

* Translate all your website content directly from the front-end, in a friendly user interface.
* Live preview of your translated pages, as you edit your translations.
* Support for both manual and automatic translation (via Google Translate)
* Ability to translate dynamic strings (gettext) added by WordPress, plugins and themes.
* Integrates with Google Translate, allowing you to set up Automatic Translation using your own Google API key.
* Place language switchers anywhere using shortcode **[language-switcher]**, WP menu item or as a floating dropdown.
* Editorial control allowing you to publish your language only when all your translations are done

Note: this plugin uses the Google Translation API to translate the strings on your site. This feature can be enabled or disabled according to your preferences.

Users with administrator rights have access to the following translate settings:

* select default language of the website and one translation language.
* choose whether language switcher should display languages in their native names or English name
* force custom links to open in current language
* enable or disable url subdirectory for the default language
* enable automatic translation via Google Translate

= Powerful Add-ons =

TranslatePress - Multilingual has a range of premium [Add-ons](https://translatepress.com/?utm_source=wp.org&utm_medium=tp-description-page&utm_campaign=TPFree) that allow you to extend the power of the translation plugin:

**Pro Add-ons** (available in the [PRO version](https://translatepress.com/?utm_source=wp.org&utm_medium=tp-description-page&utm_campaign=TPFree) only)

* [Extra Languages](https://translatepress.com/?utm_source=wp.org&utm_medium=tp-description-page&utm_campaign=TPFree) - allows you to add an unlimited number of translation languages, with the possibility to publish languages later after you complete the translation
* [SEO Pack](https://translatepress.com/?utm_source=wp.org&utm_medium=tp-description-page&utm_campaign=TPFree) - allows you to translate meta information (like page title, description, url slug, image alt tag, Twitter and Facebook Social Graph tags & more) for boosting your website's SEO and increase traffic


= Website =

[translatepress.com](https://translatepress.com/?utm_source=wp.org&utm_medium=tp-description-page&utm_campaign=TPFree)

= Documentation =

[Visit our documentation page](https://translatepress.com/docs/translatepress/?utm_source=wp.org&utm_medium=tp-description-page&utm_campaign=TPFree)

= Add-ons =

[Add-ons](https://translatepress.com/docs/translatepress/?utm_source=wp.org&utm_medium=tp-description-page&utm_campaign=TPFree)

= Demo Site =

You can test out TranslatePress - Multilingual by [visiting our demo site](https://demo.translatepress.com/)

== Installation ==

1. Upload the translatepress folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings -> TranslatePress and choose a translation language.
4. Open the front-end translation editor from the admin bar to translate your site.

== Frequently Asked Questions ==

= Where are my translations stored? =

All the translation are stored locally in your server's database.

= What types of content can I translate? =

TranslatePress - Multilingual works out of the box with WooCommerce, custom post types, complex themes and site builders, so you'll be able to translate any type of content.

= How is it different from other multilingual plugins? =

TranslatePress is easier to use and more intuitive altogether. No more switching between the editor, string translation interfaces or badly translated plugins. You can now translate the full page content directly from the front-end.

= How do I start to translate my site? =

After installing the plugin, select your secondary language and click "Translate Site" to start translating your entire site exactly as it looks in the front-end.

= Where can I find out more information? =

For more information please check out [TranslatePress documentation](https://translatepress.com/docs/translatepress/?utm_source=wp.org&utm_medium=tp-description-page&utm_campaign=TPFree).


== Screenshots ==
1. Front-end translation editor used to translate the entire page content
2. How to translate a Dynamic String (gettext) using TranslatePress - Multilingual
3. Translate Woocommerce Shop Page
4. Settings Page for TranslatePress - Multilingual
5. Floating Language Switcher added by TranslatePress - Multilingual
6. Menu Language Switcher

== Changelog ==

= 1.0.1 =
* Fixed incorrect blog prefix name for Multisite subsites on admin_bar gettext hook.
* Fixed Translate Page admin bar button sometimes not having the correct url for entering TP Editor Mode
* Skipped dynamic strings that have only numbers and special characters.
* Fixed order of categories in Editor dropdown. (Meta Information, String List..)
* Fixed JS error Uncaught Error: Syntax error, unrecognized expression

= 1.0.0 =
* Initial release.