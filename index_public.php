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
     * This file provides calendar interface for shared calendar.
     */
    
    //It is possible that user is not logged in.
    $_user_location = 'public';
    
    define('AT_INCLUDE_PATH', '../../include/');
    require(AT_INCLUDE_PATH.'vitals.inc.php');
    
    //Get member id from request if it is not set then display default message
    if (!isset($_GET['mid'])) {
        require(AT_INCLUDE_PATH.'header.inc.php'); 
        echo _AT('calendar_pub_def_msg');
        require(AT_INCLUDE_PATH.'footer.inc.php'); 
        exit;
    }
    
    global $db;
    //User requested to bookmark calendar
    if (isset($_GET['bookm']) && $_GET['bookm'] == 1) {
        if (isset($_SESSION['member_id'])) {
            //Check whether user already bookmarked the calendar
            $sql    = "SELECT * FROM " . TABLE_PREFIX . "calendar_bookmark WHERE memberid=".
                      $_SESSION['member_id'] . " AND ownerid=" . $_GET['mid'].
                      " AND courseid=" . $_GET['cid'];
            $result = mysql_query($sql, $db);
            //If user has already bookmarked calendar then display error
            if (mysql_num_rows( $result ) > 0) {
                $msg->addError('ALREADY_BOOKMARKED');
            } else {
                //Not bookmarked so bookmark now
                $sql = "INSERT INTO " . TABLE_PREFIX . "calendar_bookmark VALUES (".
                       $_SESSION['member_id'] . "," . $_GET['mid'] . "," . $_GET['cid']. 
                       ",'" . $_GET['calname'] . "')";
                mysql_query($sql, $db);
            }
            header('Location: index.php');
            exit;
        } else {
            //add in sql
            $msg->addError('LOG_IN_FIRST');
            header('Location: '.AT_BASE_HREF.'login.php');
            exit;
        }
    } else if (isset($_GET['del']) && $_GET['del'] == 1) {
        //Delete the bookmark
        if (isset($_SESSION['member_id'])) {
            $sql = "DELETE FROM " . TABLE_PREFIX . "calendar_bookmark WHERE memberid=".
                   $_SESSION['member_id'] . " AND ownerid=" . $_GET['mid'];
            mysql_query($sql, $db);
            header('Location: index.php');
            exit;
        }
    } else if (isset($_GET['editname']) 
                && $_GET['editname'] == 1 
                && trim($_GET['calname']) != "") {
        //Change name of bookmark
        if (isset($_SESSION['member_id'])) {
            $sql = "UPDATE " . TABLE_PREFIX . "calendar_bookmark SET calname='".
                    $_GET['calname'] . "' WHERE memberid=".
                    $_SESSION['member_id'] . " AND ownerid=" . $_GET['mid'];
            mysql_query($sql, $db);
            header('Location: index.php');
            exit;
        }
    }

    require(AT_INCLUDE_PATH.'header.inc.php');
?>
<div style="left:50%; z-index:20000; position:absolute; top:50%" id="loader">
    <img src="mods/calendar/img/loader.gif" alt="Loading" />
</div>

<?php 
    if (isset($_GET['email']) && $_GET['email'] == 1 && isset($_SESSION['member_id'])) {
?>
<div style="float:right;width:20%" class="box">
    <fieldset>
        <legend><h4><?php echo _AT('calendar_options'); ?></h4></legend>
        <ul class="social_side_menu">
        <li>
            <a  href='mods/calendar/index_public.php?mid=<?php echo $_GET['mid'];?>&cid=<?php echo $_GET['cid'];?>&bookm=1&calname=<?php echo $_GET['calname']; ?>'>
                <?php echo _AT('calendar_bookmark_this'); ?>
            </a> 
        </li>
        </ul>
    </fieldset>
</div>
<?php
    } else if (isset($_SESSION['member_id'])) {
?>
<div style="float:right;width:20%" class="box">
    <fieldset>
        <legend><h4><?php echo _AT('calendar_options'); ?></h4></legend>
        <ul class="social_side_menu">
        <li>
            <form action="mods/calendar/index_public.php" method="get" >
                <label for="calname"><?php echo _AT('calendar_edit_title'); ?></label>
                <br/>
                <input type="hidden" value="<?php echo $_GET['mid'];?>" name="mid" />
                <input type="hidden" value="<?php echo $_GET['cid'];?>" name="cid" />
                <input type="hidden" value="1" name="editname" />
                <input type="text" size="12" value="<?php echo $_GET['calname']; ?>" name="calname" id="calname" />
                <br/>
                &nbsp;&nbsp;
                <input type="submit" value="<?php echo _AT('calendar_save'); ?>" />
            </form>
        </li>
        <li>
            <a  href='mods/calendar/index_public.php?mid=<?php echo $_GET['mid'];?>&cid=<?php echo $_GET['cid'];?>&del=1&calname=<?php echo $_GET['calname']; ?>'>
                <?php echo _AT('calendar_del_bookmark'); ?>
            </a>
        </li>            
        </ul>
    </fieldset>
</div>
<?php        
    } else {
?>
<div style="float:right;width:20%" class="box">
    <fieldset>
        <legend><h4><?php echo _AT('calendar_options'); ?></h4></legend>
        <ul class="social_side_menu">
        <li>
            <?php echo _AT("calendar_public_note1")." <a href= '".AT_BASE_HREF."login.php'>"._AT("calendar_public_note2")."</a> "._AT("calendar_public_note3");
            ?>    
        </li>
        </ul>
    </fieldset>
</div>
<?php
    }
?>
<?php $_custom_css = $_base_path . 'mods/calendar/lib/fullcalendar/fullcalendar-theme.css'; // use a custom stylesheet ?>

<script language="javascript" type="text/javascript" 
    src="<?php echo AT_BASE_HREF; ?>mods/calendar/lib/fullcalendar/fullcalendar-theme.js">
</script>

<link href= "<?php echo AT_BASE_HREF; ?>mods/calendar/lib/fullcalendar/fullcalendar-theme.css" rel="stylesheet" type="text/css"/>

<script>
    //For IE
    $.ajaxSetup({cache: false});

    $(document).ready(function () {
        //Get current date for calculations.
                
        var date = new Date();
        var d    = date.getDate();
        var m    = date.getMonth();
        var y    = date.getFullYear();
        
        var activeelem;
        var focusd     = false;
        var viewchangd = false;
        
        var calendar = $('#calendar').fullCalendar({
        
            defaultView: "month",            
            loading: function(isLoading, view) {
                if( isLoading )
                    $("#loader").show();
                else
                    $("#loader").hide();
            },            
            //Do not apply theme
            theme: false,            
            //Header details
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'month,agendaWeek,agendaDay'
            },
            //Catch the fired event but do not save view
            saveView: function() {
                var viewo = calendar.fullCalendar('getView');
                if (viewchangd) {
                    viewchangd = false;
                }
            },
            //Do not allow adding events by selecting cells
            selectable: false,
            selectHelper: false,            
            eventAfterRender: function(evento,elemento,viewo) {
                if (!evento.editable) {
                    var childo = elemento.children();
                    if (viewo.name == "month") {
                        childo[1].innerHTML += "<div class='fc-unedit-announce'>Uneditable event</div>";
                    } else {
                        childo[0].innerHTML += "<div class='fc-unedit-announce'>Uneditable event</div>";
                    }
                }
                if (focusd) {
                    if (evento.id + "" == $("#ori-name1").val()) {
                        elemento.focus();
                        focusd = false;
                    }
                }
            },            
            //Event is resized. So update db.
            eventResize: function(event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view) { 
            },
            viewDisplay: function(view) {
                //Add data for screen reader
                viewchangd = true;
                $(".fc-button-firsts").each(
                   function() {
                        if ($(this).text().indexOf( 'Previous' ) >= 0) {
                            if (view.name == "month") {
                                $(this).text("<?php echo _AT('calendar_prv_mnth'); ?>");
                            } else if (view.name == "agendaWeek") {
                                $(this).text("<?php echo _AT('calendar_prv_week'); ?>");
                            } else {
                                $(this).text("<?php echo _AT('calendar_prv_day'); ?>");
                            }
                        }
                        if ($(this).text().indexOf( 'Next' ) >= 0) {
                            if (view.name == "month") {
                                $(this).text("<?php echo _AT('calendar_nxt_mnth'); ?>");
                            } else if (view.name == "agendaWeek") {
                                $(this).text("<?php echo _AT('calendar_nxt_week'); ?>");
                            } else {
                                $(this).text("<?php echo _AT('calendar_nxt_day'); ?>");
                            }
                        }
                   });                
            },
            //Events are not editable.
            editable: false,
            //Retrieve events from php file.
            events: "mods/calendar/json-events.php?mid=<?php echo $_GET['mid']; ?>&pub=1&cid=<?php echo $_GET['cid']; ?>"            
        });            
    });
    function refreshevents() {
        //Refresh events as view is changed
        $("#calendar").fullCalendar("refetchEvents");
    }    
</script>
<style type="text/css">
    #calendar {
        width: 75%;
        margin: 0 auto;
    }
</style>

<div style="float:left" id="calendar">
</div>
    
<?php
    require(AT_INCLUDE_PATH.'footer.inc.php'); 
?>