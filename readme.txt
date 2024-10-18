=== Jeero ===
Contributors: slimndap
Tags: calendar, tickets, events, veezi, ticketmaster, audienceview, stager
Requires PHP: 7.2
Tested up to: 6.6
Requires at least: 4.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Imports events and tickets from your existing ticketing solution to The Events Calendar and other popular calendar plugins.

== Description ==

Jeero bridges the gap between your ticketing solution and your website.

Tired of manually copying your event data from your external ticketing solution into WordPress? Jeero handles this for you, keeping your website fully updated with showtimes, prices, descriptions, images, and deep links to your online ticketing interface.

https://www.youtube.com/watch?v=6r4GhOKRU-o

Ticketing solutions that are supported:

* [ActiveTickets](https://jeero.ooo/publish-activetickets-events-on-wordpress/)
* [AudienceView Professional](https://jeero.ooo/publish-audienceview-events-on-wordpress/)
* [Billeto](https://jeero.ooo/publish-bravobase-performances-on-wordpress/)
* [Billetweb](https://jeero.ooo/publish-billetweb-events-on-wordpress/)
* [BravoBase](https://jeero.ooo/publish-bravobase-performances-on-wordpress/)
* [Brown Paper Tickets](https://jeero.ooo/publish-brown-paper-tickets-events-on-wordpress/)
* [Chaplin](https://jeero.ooo/publish-chaplin-films-on-wordpress/)
* [Ciné Office](https://jeero.ooo/publish-cine-office-shows-on-wordpress/)
* [Ents24](https://jeero.ooo/publish-ents24-events-on-wordpress/)
* [Eventive](https://jeero.ooo/publish-eventive-films-on-wordpress/)
* [Fienta](https://jeero.ooo/publish-fienta-events-on-wordpress/)
* [KinoTickets Online](https://jeero.ooo/publish-kinotickets-online-films-on-wordpress/)
* [Ovatic](https://jeero.ooo/publish-ovatic-events-on-wordpress/)
* [OvationTix](https://jeero.ooo/publish-ovationtix-events-on-wordpress/)
* [PatronBase](https://jeero.ooo/publish-patronbase-performances-on-wordpress/)
* [Place2Book](https://jeero.ooo/publish-place2book-events-on-wordpress/)
* [Reservix](https://jeero.ooo/publish-reservix-events-on-wordpress/)
* [RTS](https://jeero.ooo/publish-rts-events-on-wordpress/)
* [Sirius](https://jeero.ooo/publish-sirius-events-on-wordpress/)
* [Stager](https://jeero.ooo/publish-stager-events-on-wordpress/)
* [ThunderTix](https://jeero.ooo/publish-thundertix-events-on-wordpress/)
* [TicketingCiné](https://jeero.ooo/publish-ticketingcine-events-on-wordpress/)
* [Ticketlab](https://jeero.ooo/publish-ticketlab-events-on-wordpress/)
* [Ticketmaster](https://jeero.ooo/publish-ticketmaster-events-on-wordpress/)
* [Ticketmatic](https://jeero.ooo/publish-ticketmatic-events-on-wordpress/)
* [Ticketportal](https://jeero.ooo/publish-ticketportal_cz-events-on-wordpress/)
* [TicketSolve](https://jeero.ooo/publish-ticketsolve-events-on-wordpress/)
* [TicketSource](https://jeero.ooo/publish-ticketsource-events-on-wordpress/)
* [TicketWeb](https://jeero.ooo/publish-ticketweb-events-on-wordpress/)
* [Tixly](https://jeero.ooo/publish-tix-events-on-wordpress/)
* [Veezi](https://jeero.ooo/publish-veezi-events-on-wordpress/)

Calendar/event plugins that are supported:

* [All In One Event Calendar](/plugins/all-in-one-event-calendar/)
* All [Goodlayers](https://www.goodlayers.com) themes that use their Event Post Type plugin
* [EventON](https://www.myeventon.com/)
* [Events Schedule WordPress Plugin](https://demo.curlythemes.com/timetable-wordpress-plugin/)
* [Modern Events Calendar](/plugins/modern-events-calendar-lite/)
* [Sugar Calendar](/plugins/sugar-calendar-lite/)
* [Theater for Wordpress](/plugins/theatre/)
* [The Events Calendar](/plugins/the-events-calendar/)
* [Very Simple Event List](/plugins/very-simple-event-list/)
* [WP Event Manager](/plugins/wp-event-manager/)
* Custom Post Types

= Are you missing a ticketing solution? =
My goal is to provide imports from any ticketing system to any calendar plugin. Please contact me so that I can include your solution as well.

= Get started for free =
Slim & Dapper's Jeero is a subscription-based service. Within the plugin, you can easily set up a free subscription. The free subscription is fully functional, displaying up to ten upcoming events at any given time. When you're ready to display more events, you can upgrade to a paid plan.

== Installation ==

1. Go to Plugins -> Add New and look for 'Jeero'.
2. Install and activate the Jeero plugin.
3. Go to Jeero in the WordPress admin and click on ‘Add Import’.
4. Select your ticketing solution and your calendar plugin and submit the form.
5. Fill in the missing fields in the form. Submit the form.

Wait a couple of minutes (max 5) and go to your Calendar plugin.

You should see the first 10 upcoming events that are coming from your ticketing solutions.

== Frequently Asked Questions ==

= How do I upgrade? =

Go to the Jeero menu in the WordPress admin and click on the upgrade link next the import that needs to import more than 10 upcoming events.

= How much does it cost? =

Subscription rates range from €30 - €70 per month, depending on your total number of upcoming events. 

== Changelog ==

= 1.30 =
* Added support for imports to custom post types. 
* Added a settings page.
* Added statistics to the debug page.

= 1.29 =
* The Events Calendar no longer imports a dummy end time if the ticketing solution does not provide one (1.29) and an end time was not already entered manually (1.29.2).
* Localised timestamps in log file.
* Added detection of problems with WP-Cron.
* Fixed number formatting of prices of imported events of the Modern Events Calendar plugin. No longer uses local number format, because the MEC input field for prices only accepts '.' as separator (1.29.1).

= 1.28 =
* Updated Twig to 3.10.3.
* Improvements to the debug log admin page.

= 1.27 =
* Added a WP filter to alter the number of items imported during each pickup.
* Performances improvements for imports with a LOT of events (1.27.1 + 1.27.2).

= 1.26 =

* Images with dynamic filenams no longer result in multiple images inside the media library.
* Imported images can now use alt texts from the ticketing system (if available).

= 1.25 =

* Added a WP filter to individual subscription settings.
* Fixed several PHP and WP deprecation warnings.
* Updated Twig to 3.8.0.
* Fixed an import problem where series were imported multiple times in The Events Calendar (1.25.1).

= 1.24 =

* Improved usability of debug log admin page.
* Fixed several PHP warnings in PHP8.2 (1.24.1).

= 1.23 =

* Added support for event statuses in The Events Calendar.
* Fixed number formatting of prices of imported events of the Modern Events Calendar plugin (1.23.1).

= 1.22 =

* Added support for series in The Events Calendar. Requires The Events Calendar Pro.
* Fixed [an issue](https://github.com/slimndap/jeero/issues/16) with the update settings for venue fields in The Events Calendar imports.

= 1.21 =

* Improved support for websites that are running multiple Jeero imports. 
* Added more information about imports to the Jeero logfile.

= 1.20 =

* Fixed conflict with events that were previously imported by one of the Theater for WordPress import extensions.
* Adds support for importing GIF files (1.20.1).
* Fixed an import problem where events were not properly imported in The Events Calendar 6. (1.20.2).

= 1.19 =

* Imported images now get SEO-friendly filenames and alt tags.

= 1.18 =

* Jeero now has a dedicated log file and no longer pollutes your PHP error log.

= 1.17 =

* Adds support for additional venue details to imported events of The Event Calendar plugin.
* Fixed number formatting of prices of imported events of The Event Calendar plugin (1.17.1).
* Fixed not retaining map setting after each import of events of The Event Calendar plugin (1.17.1).
* Fixed a problem with images of imported events of the Events Schedule WP Plugin (1.17.2).
* Fixed a problem with incorrect start times of imported events of the Events Schedule WP Plugin (1.17.3).
* Updated Twig to 3.3.8 (1.17.4).
* Fixed a problem where the Modern Events Calendar import stopped working on some systems (1.17.5).
* Fixed a PHP warning in PHP8 (1.17.6).

= 1.16 =

* Improved support for custom templates.
* Internal improvements to accommodate future support for more calendar/slider/narrow-casting plugins!

= 1.15 =

* Added support for the [EventON](https://www.myeventon.com/) plugin.
* Fixed a problem with event status import to the WordPress for Theater plugin (1.15.1).
* Fixed a problem with the Modern Events Calendar plugin (1.15.2).
* Fixed a problem with incorrect start and end times in The Event Calendar plugin (1.15.3).
* Fixed a problem with missing prices in the Theater for WordPress plugin (1.15.4).
* Improvements to Twig example code for templates (1.15.4).
* Fixed support for the latest version of the Sugar Calendar plugin (1.15.4).

= 1.14 =

You can now define your own custom fields for imported events.

= 1.13 =

Added support for the [WP Event Manager](/plugins/wp-event-manager/) plugin.

= 1.12 =

Added support for the [Sugar Calendar](/plugins/sugar-calendar-lite/) plugin.

= 1.11 =

* Added support for custom templates to the [Modern Events Calendar](/plugins/modern-events-calendar-lite/) import. 
* Other improvements to the [Modern Events Calendar](/plugins/modern-events-calendar-lite/) import.

= 1.10 =

* Added support for custom templates to the [The Events Calendar](/plugins/the-events-calendar/) and [Theater for Wordpress](/plugins/theatre/) imports. You can now use the power of [Twig](https://twig.symfony.com/doc/3.x/templates.html) templates to customise the contents of your imported events. 

= 1.9 =

* Made several improvements to the [Modern Events Calendar](/plugins/modern-events-calendar-lite/) import.

= 1.8 =

* Made it possible to set if title, description, categories and featured image should be overwritten after each import to the The Events Calendar plugin.

= 1.7 = 
* Simplified onboarding.

= 1.6 =

* [Theater for Wordpress](/plugins/theatre/) import now supports categories and cities.
* [Events Schedule WordPress Plugin](https://demo.curlythemes.com/timetable-wordpress-plugin/) import now supports venues and categories.

= 1.5 =

* Restructured setting pages.

= 1.4 =

* Added support for custom import settings per calendar plugin.
* Made it possible to set if title, description and featured image should be overwritten after each import to the Theater for WordPress plugin.

= 1.3 =

* Added support for [Goodlayers](https://www.goodlayers.com) themes that use their Event Post Type plugin.

= 1.2 =

* Added support for the [Very Simple Event List plugin](/plugins/very-simple-event-list/).
* Fixed several import issues with The Events Calendar.

= 1.1 =

* Renamed 'subscriptions' to 'imports'.
* Made it possible to deactivate an import.
* Added support for the [Events Schedule WordPress Plugin](https://demo.curlythemes.com/timetable-wordpress-plugin/).

= 1.0.4 =
Fixed a redirect issue while setting up a new import.

= 1.0.3 =
Added support for the Modern Events Calendar plugin.

= 1.0.2 =
Fixed several import issues with the All In One Event Calendar plugin. Adds featured images support to All In One Event Calendar and The Events Calendar imports.

= 1.0 =
* Welcome Jeero!

== Upgrade Notice ==

= 1.30 =
* Adds support for imports to custom post types.

= 1.29.4 =
* Improved detection of malfunctioning WP-Cron.

= 1.29 =
* The Events Calendar no longer imports a dummy end time if the ticketing solution does not provide one.

= 1.28 =
* Updates Twig to version 3.10.3.

= 1.27 =
Added a WP filter to alter the number of items imported during each pickup.

= 1.26 =
* Multiple improvements to image imports.

= 1.24.1 =
* Fixes several PHP warnings in PHP8.2.

= 1.23 =

* Added support for event statuses in The Events Calendar.

= 1.22 =

* Added support for series in The Events Calendar.

= 1.21 =
* Better support for websites that are running multiple Jeero imports.

= 1.20.2 =
* Fixes an import problem where events were not properly imported in The Events Calendar 6.

= 1.20.1 =

* Adds support for importing GIF files.

= 1.20 =

* Fixes conflict with events that were previously imported by one of the Theater for WordPress import extensions.

= 1.19 =

* Adds SEO-friendly filenames and alt tags to imported images.

= 1.18 =

* Adds a dedicated Jeero logfile.

= 1.17.6 =

* Improves PHP8 support

= 1.17.5 =

* Fixes a problem where the Modern Events Calendar import stopped working on some systems.

= 1.17.4 =

* Updates Twig to version 3.3.8, fixing a potential security hazard.

= 1.17.3 =

* Fixes a problem with incorrect start times of imported events of the Events Schedule WP Plugin.

= 1.17.2 =

* Fixes a problem with images of imported events of the Events Schedule WP Plugin.

= 1.17.1 =

Fixes number formatting of prices of imported events of The Event Calendar plugin.


= 1.17 =

Adds support for additional venue details in imported events of The Event Calendar plugin.

= 1.16 =

Improves support for custom templates.

= 1.15.4 =
Fixes a problem with missing prices in the Theater for WordPress plugin 

= 1.15.3 =

Fixes a problem with incorrect start and end times in The Event Calendar.

= 1.15.2 =

Fixes a problem with the Modern Events Calendar plugin.

= 1.15.1 =

Fixes a problem with event status import to the WordPress for Theater plugin.

= 1.15 = 

* Adds support for the EventON plugin.

= 1.14.2 = 

* You can now define your own custom fields for imported events.

= 1.13 = 

* Adds support for the WP Event Manager plugin.

= 1.12 = 

* Adds support for the Sugar Calendar plugin.

= 1.11 = 

* Adds support for custom templates to the Modern Events Calendar import.

= 1.10 = 

* Adds support for custom templates to the The Events Calendar and Theater for Wordpress imports.

= 1.9 =

* Improvements to the Modern Events Calendar import.

= 1.6 =
* Improvements to the Theater for WordPress and the Events Schedule WordPress Plugin imports.

= 1.4 =
* Makes it possible to set if title, description and featured image should be overwritten after each import to the Theater for WordPress plugin.

= 1.3.3 =
* Fixes a timezone issue with the [Goodlayers](https://www.goodlayers.com) Event Post Type plugin.

= 1.3 =
* Adds support for [Goodlayers](https://www.goodlayers.com) themes that user their Event Post Type plugin.


= 1.2 =
Adds support for the Very Simple Event List plugin.

= 1.1 = 
Adds the option to deactivate imports and support for the Events Schedule WordPress Plugin.

= 1.0 =
Jeero is born!
