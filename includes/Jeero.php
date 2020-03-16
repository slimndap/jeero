<?php
/**
 * \mainpage Jeero
 * \author Jeroen Schmit
 * \version	1.0
 * \copyright GNU Public License
 *
 * \section intro Introduction
 * Jeero is a little boy who dreams of going to the theater.
 * He asks his mother to contact the Theaters to negotiate a Subscription to their list of Shows.
 * The Theaters regularly leave a list of new events in his inbox. 
 * which Jeero checks eand empties continously.
 * He then Works diligently through all the data to put it in one of his beautiful Calendars.
 *
 * _Jeero also syncs all events and tickets from your ticketing solution with your favourite calendar plugin._
 */
 
/**
 * Hi! I am Jeero.
 */
namespace Jeero;

include_once PLUGIN_PATH.'includes/Db/Subscriptions.php';

include_once PLUGIN_PATH.'includes/Admin/Admin.php';
include_once PLUGIN_PATH.'includes/Admin/Subscriptions/Subscriptions.php';
include_once PLUGIN_PATH.'includes/Admin/Subscriptions/List_Table.php';
include_once PLUGIN_PATH.'includes/Admin/Fields/Fields.php';
include_once PLUGIN_PATH.'includes/Admin/Fields/Field.php';
include_once PLUGIN_PATH.'includes/Admin/Fields/Select.php';
include_once PLUGIN_PATH.'includes/Admin/Fields/Checkbox.php';
include_once PLUGIN_PATH.'includes/Admin/Fields/Url.php';
include_once PLUGIN_PATH.'includes/Admin/Fields/Message.php';
include_once PLUGIN_PATH.'includes/Admin/Fields/Error.php';
include_once PLUGIN_PATH.'includes/Admin/Notices/Notices.php';

include_once PLUGIN_PATH.'includes/Calendars/Calendars.php';
include_once PLUGIN_PATH.'includes/Calendars/Calendar.php';
include_once PLUGIN_PATH.'includes/Calendars/The_Events_Calendar.php';
include_once PLUGIN_PATH.'includes/Calendars/Theater_For_WordPress.php';

include_once PLUGIN_PATH.'includes/Theaters/Theaters.php';
include_once PLUGIN_PATH.'includes/Theaters/Theater.php';
include_once PLUGIN_PATH.'includes/Theaters/Veezi.php';

include_once PLUGIN_PATH.'includes/Subscriptions/Subscriptions.php';
include_once PLUGIN_PATH.'includes/Subscriptions/Subscription.php';

include_once PLUGIN_PATH.'includes/Inbox/Inbox.php';

include_once PLUGIN_PATH.'includes/Mother/Mother.php';

include_once PLUGIN_PATH.'includes/Work/Work.php';
include_once PLUGIN_PATH.'includes/Work/Task.php';
include_once PLUGIN_PATH.'includes/Work/Import.php';