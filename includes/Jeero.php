<?php
/**
 * \mainpage Jeero
 * \author Jeroen Schmit
 * \version	1.0
 * \copyright GNU Public License
 *
 * \section intro Introduction
 * Synchronizes events and tickets from your existing ticketing solution with popular calendar plugins.
 */
 
/**
 * Hi! I am Jeero.
 */
namespace Jeero;

include_once PLUGIN_PATH.'includes/Db/Subscriptions.php';

include_once PLUGIN_PATH.'includes/Admin/Admin.php';
include_once PLUGIN_PATH.'includes/Admin/Subscriptions/Subscriptions.php';
include_once PLUGIN_PATH.'includes/Admin/Subscriptions/List_Table.php';
include_once PLUGIN_PATH.'includes/Admin/Notices/Notices.php';

include_once PLUGIN_PATH.'includes/Calendars/Calendars.php';
include_once PLUGIN_PATH.'includes/Calendars/Calendar.php';
include_once PLUGIN_PATH.'includes/Calendars/All_In_One_Event_Calendar.php';
include_once PLUGIN_PATH.'includes/Calendars/The_Events_Calendar.php';
include_once PLUGIN_PATH.'includes/Calendars/Theater_For_WordPress.php';
include_once PLUGIN_PATH.'includes/Calendars/Modern_Events_Calendar.php';
include_once PLUGIN_PATH.'includes/Calendars/Events_Schedule_Wp_Plugin.php';

include_once PLUGIN_PATH.'includes/Theaters/Theaters.php';
include_once PLUGIN_PATH.'includes/Theaters/Theater.php';
include_once PLUGIN_PATH.'includes/Theaters/Veezi.php';

include_once PLUGIN_PATH.'includes/Subscriptions/Subscriptions.php';
include_once PLUGIN_PATH.'includes/Subscriptions/Subscription.php';
include_once PLUGIN_PATH.'includes/Subscriptions/Fields/Fields.php';
include_once PLUGIN_PATH.'includes/Subscriptions/Fields/Field.php';
include_once PLUGIN_PATH.'includes/Subscriptions/Fields/Select.php';
include_once PLUGIN_PATH.'includes/Subscriptions/Fields/Checkbox.php';
include_once PLUGIN_PATH.'includes/Subscriptions/Fields/Url.php';
include_once PLUGIN_PATH.'includes/Subscriptions/Fields/Message.php';
include_once PLUGIN_PATH.'includes/Subscriptions/Fields/Error.php';

include_once PLUGIN_PATH.'includes/Inbox/Inbox.php';

include_once PLUGIN_PATH.'includes/Mother/Mother.php';

include_once PLUGIN_PATH.'includes/Helpers/Images.php';