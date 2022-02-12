<?php
/*
 * This file is part of con4gis, the gis-kit for Contao CMS.
 * @package con4gis
 * @version 8
 * @author con4gis contributors (see "authors.txt")
 * @license LGPL-3.0-or-later
 * @copyright (c) 2010-2022, by Küstenschmiede GmbH Software & Design
 * @link https://www.con4gis.org
 */

/** FIELDS */
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['caption'] = array("Name", "Name your reservation object. Examples: Room1, table7, specialist3");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['options'] = array("Frontend name", "Are displayed in the frontend depending on the language.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['quantity'] = array("Available number", "How many objects of this type are available? (default 1)");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['allTypesQuantity'] = array("Applies across all reservation types (same object)", "The available number of objects is considered across all reservation types.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['allTypesValidity'] = array("Applies across all reservation types (all objects)", "If more than one reservation type is active, then the booking will also block other objects.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['switchAllTypes'] = array("Applies only to the following reservation types", "Allows you to reduce the validity of the previous checkboxes to specific reservation types (optional).");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['priority'] = array("Should be offered with priority", "If this switch is active, then the object will be preselected if several objects fit into the time.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['viewableTypes'] = array("Reservation types", "Assign the reservation object to the reservation types.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['option'] = array("Name","");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['location'] = array("Event location", "Where will the event take place?");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['language'] = array("language","");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['minute_interval'] = array("minute interval", "Every how many minutes the reservation object can be booked during opening hours.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['hour_interval'] = array("hour interval", "Every how many hours the reservation object can be booked during opening hours");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['day_interval'] = array("day interval", "Every how many days the reservation object can be booked during opening hours");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['week_interval'] = array("week interval", "Every how many weeks the reservation object can be booked during opening hours");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['oh_monday'] = array("Monday", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['oh_tuesday'] = array("Tuesday", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['oh_wednesday'] = array("Wednesday", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['oh_thursday'] = array("Thursday", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['oh_friday'] = array("Friday", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['oh_saturday'] = array("Saturday", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['oh_sunday'] = array("Sunday", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['time_begin'] = array("begin", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['time_end'] = array("end", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['date_from'] = array("valid from (optional)", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['date_to'] = array("valid to (optional)", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['days_exclusion'] = array("Exclude Period", "This allows you to exclude periods from the reservation (e.g. holidays).");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['date_exclusion'] = array("", "");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['min_reservation_day'] = array("Earliest reservation date (in days)", "Today + how many days?");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['max_reservation_day'] = array("Latest reservation date (in days)", "Today + how many days?");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['periodType'] = array("Time type", "Select what kind of time interval should be used.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['event_id'] = array("Event selection","");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['event_dayBegin'] = array("Event start", "On which day the event starts.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['event_dayEnd'] = array("Event end", "On which day the event ends.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['event_timeBegin'] = array("Event start", "At what time does the event start.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['event_timeEnd'] = array("Event end", "At what time does the event end");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['description'] = array("Description", "Description of the reservation object.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['image'] = array("Image", "Matching image to reservation option.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['minute'] = array("minutes");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['hour'] = array("hours");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['day'] = array("days");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['week'] = array("weeks");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['openingHours'] = array("booking hours");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['time_interval'] = array("time interval", "Specify how many X minutes/ X hours the object can be booked (depends on which reservation type the object is assigned to");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['duration'] = array("Duration per booking (optional)", "Normally [0] the duration is according to the time interval. However, if the actual appointment should be longer than the offered interval. For example, if a conversion or cleaning break is to be scheduled, then a longer interval can be entered here.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['date_exclusion_end'] = array("Exclusion end", "Put the last Date of exlusion in");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['max_residence_time'] = array("Minimum duration of use (optional)", "Minimum usage period that the customer may choose in the form. Important for multi-day bookings (days, weeks). Attention. Applies only to the reservation type Object choice. At 0, the time interval applies.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['min_residence_time'] = array("Maximum duration of use (optional)", "Maximum duration of use that the customer may choose in the form. Important for multi-day bookings (days, weeks). Attention. Applies only to the reservation type Object choice. At 0, the time interval applies.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['md'] = array("Several days");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['event_selection'] = array("event type");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['event_object'] = array("event object");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['contao_event'] = array("Contao Event");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['published'] = array("Publish.", "Should this object be displayed in the frontend?");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['desiredCapacityMin'] = array("Minimum number of persons", "How many persons may appear at least? With standard 0 the number is not evaluated.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['desiredCapacityMax'] = array("Maximum number of persons", "How many persons may appear at least? With standard 0 the number is not evaluated.");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['location_legend'] = array("Settings for the location");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['notification_type'] = array('Automatic confirmation message (optional)', 'Select notification. This setting overrides the module settings and also the setting at the reservation type.');

$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['price'] = array("Price", "Specify the price for the booking (for example: 50.00).");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['priceoption'] = array("Price setting", "What should the price be calculated by.");

$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['references']['pMin'] = array("Price per minute");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['references']['pHour'] = array("Price per hour");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['references']['pDay'] = array("Price per day");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['references']['pWeek'] = array("Price per week");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['references']['pReservation'] = array("Price per reservation");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['references']['pPerson'] = array("Price per person");

/** LEGENDS **/
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['type_legend'] = "Reservation objects";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['md_legend'] = "Several days";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['event_legend'] = "event";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['event_object_legend'] = "Event";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['contao_event_legend'] = "Contao event";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['periodType_legend'] = "time";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['time_interval_legend'] = "Interval settings (depending on the type)";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['minute_legend'] = "minute settings";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['hour_legend'] = "hour settings";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['day_legend'] = "day settings";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['week_legend'] = "week settings";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['publish_legend'] = "publishing options";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['opening_hours_monday_legend'] = "Opening hours Mondays";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['opening_hours_tuesday_legend'] = "Opening hours Tuesdays";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['opening_hours_wednesday_legend'] = "Opening hours Wednesdays";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['opening_hours_thursday_legend'] = "Opening hours Thursdays";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['opening_hours_friday_legend'] = "Opening hours Fridays";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['opening_hours_saturday_legend'] = "Opening hours Saturdays";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['opening_hours_sunday_legend'] = "Opening hours Sundays";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['exclusion_legend'] = "Exclusion hours";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['booking_wd_legend'] = "Possible booking periods (opening hours)";
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['price_legend'] = 'Price settings';
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['expert_legend'] = 'Expert settings';

/** OPERATIONS **/
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['new'] = array("add object", "add object");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['edit'] = array("edit object", "edit object ID %s");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['copy'] = array("Copy object", "Copy object ID %s");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['delete'] = array("Delete object", "Delete object ID %s");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['show'] = array("Show object", "Show object ID %s");
$GLOBALS['TL_LANG']['tl_c4g_reservation_object']['TOGGLE'] = array("Activate object", "Display object ID %s");
