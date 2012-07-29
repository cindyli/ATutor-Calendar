<?php
    /****************************************************************/
    /* ATutor Calendar Module                                       */
    /* https://atutorcalendar.wordpress.com/                        */
    /*                                                              */
    /* This module provides standard calendar features in ATutor.   */
    /*                                                              */
    /* Author: Anurup Raveendran, Herat Gandhi                      */
    /* This program is free software. You can redistribute it and/or*/
    /* modify it under the terms of the GNU General Public License  */
    /* as published by the Free Software Foundation.                */
    /****************************************************************/
    
    /**
     * This file returns events from Google Calendar in JSON format.
     */
    require_once 'includes/classes/googlecalendar.class.php';
    define('AT_INCLUDE_PATH', '../../include/');
    require(AT_INCLUDE_PATH.'vitals.inc.php');

    $gcalobj = new GoogleCalendar();
    
    global $db;
    $qry = "SELECT * FROM " . TABLE_PREFIX . "calendar_google_sync WHERE userid='".
            $_SESSION['member_id']."'";
    $res = mysql_query($qry, $db);
    if (mysql_num_rows($res) > 0) {
        $row = mysql_fetch_assoc($res);
        $_SESSION['sessionToken'] = $row['token'];
        
        if ($gcalobj->isvalidtoken($_SESSION['sessionToken'])) {
         $client  = $gcalobj->getAuthSubHttpClient();
         $query   = "SELECT * FROM ".TABLE_PREFIX."calendar_google_sync WHERE userid='".
                    $_SESSION['member_id']."'";
         $res     = mysql_query($query,$db);
         $rowval  = mysql_fetch_assoc($res);
         $prevval = $rowval['calids'];

         outputCalendarByDateRange($client, $_GET['start'], $_GET['end'], $prevval, $gcalobj);
        }
    }

    /**
     * Iterate through all the Google Calendars and create a JSON encoded array of events.
     *
     * @return array of events in JSON format
     */
    function outputCalendarByDateRange($client, $startDate='2007-05-01', $endDate='2007-08-01', $idsofcal, $gcalobj) {
        $gdataCal = new Zend_Gdata_Calendar($client);
        $rows     = array();

        $idsofcal = explode(',',$idsofcal);
        $calFeed  = $gdataCal->getCalendarListFeed();

        foreach ($idsofcal as $idofcal) {
            if ($idofcal != '') {
                $query = $gdataCal->newEventQuery();
                
                $query->setUser(substr($idofcal,strrpos($idofcal,"/")+1));
                $query->setVisibility('private');
                $query->setProjection('full');
                $query->setOrderby('starttime');
                $query->setStartMin($startDate);
                $query->setStartMax($endDate);
                
                $eventFeed  = $gdataCal->getCalendarEventFeed($query);
                $color      = '#3399FF';
                $accesslevl = true;
                foreach ($calFeed as $calendar) {
                    if (strpos($idofcal,$calendar->id->text) !== false) {
                        $color = $calendar->color->value;
                        if ($calendar->accesslevel->value == 'read') {
                            $accesslevl = false;
                        }
                    }
                }

                foreach ($eventFeed as $event) {
                    foreach ($event->when as $when) {
                        $startD = substr($when->startTime, 0, 19);
                        $startD = str_replace('T', ' ', $startD);
                        $endD   = substr($when->endTime, 0, 19);
                        $endD   = str_replace('T', ' ', $endD);

                        /*
                         * If both start time and end time are different and their time parts differ then allDay is false
                         */
                        if (($startD != $endD) && substr($startD,0,10) == substr($endD,0,10)) {
                            $allDay = "false";
                        } else {
                            $allDay = "true";
                        }
                        $row = array();
                        $row["title"]     = $event->title->text;
                        $row["id"]        = $event->id->text;
                        $row["editable"]  = $accesslevl;
                        $row["start"]     = $startD;
                        $row["end"]       = $endD;
                        $row["allDay"]    = $allDay;
                        $row["color"]     = $color;
                        $row["textColor"] = "white";
                        $row["calendar"]  = "Google Calendar event";

                        array_push($rows, $row);
                    }
                }
            }
        }
        //Encode in JSON format.
        $str =  json_encode($rows);
        
        //Replace "true","false" with true,false for javascript.
        $str = str_replace('"true"', 'true', $str);
        $str = str_replace('"false"', 'false', $str);
        
        //Return the events in the JSON format.
        echo $str;    
    }
?>