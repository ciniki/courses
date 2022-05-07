<?php
//
// Description
// -----------
// Load the offering, classes and notifications then build what the notification queue should be.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_offeringNQueueBuild(&$ciniki, $tnid, $offering_id) {

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = new DateTimezone($rc['settings']['intl-default-timezone']);

    $utc_timezone = new DateTimezone('UTC');
    $dt_now = new DateTime('now', $utc_timezone);

    //
    // Load the offering
    //
    $strsql = "SELECT offerings.id, "
        . "offerings.start_date, "
        . "offerings.end_date, "
        . "courses.flags AS course_flags "
        . "FROM ciniki_course_offerings AS offerings "
        . "INNER JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE offerings.id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.227', 'msg'=>'Unable to load offering', 'err'=>$rc['err']));
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.228', 'msg'=>'Unable to find requested offering'));
    }
    $offering = $rc['offering'];

    //
    // Setup dates to skip notifications if timeless
    //
    if( ($offering['course_flags']&0x10) == 0x10 ) {
        $offering['start_date'] = '0000-00-00';
        $offering['end_date'] = '0000-00-00';
    }

    //
    // Load the classes, if NOT a timeless course
    //
    $first_class = null;
    $last_class = null;
    if( ($offering['course_flags']&0x10) == 0 ) {
        $strsql = "SELECT classes.id, "
            . "classes.class_date "
            . "FROM ciniki_course_offering_classes AS classes "
            . "WHERE classes.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'class');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.229', 'msg'=>'Unable to load class', 'err'=>$rc['err']));
        }
        $classes = isset($rc['rows']) ? $rc['rows'] : array();
        foreach($classes as $class) {   
            if( $first_class == null ) {
                $first_class = $class;
            }
            $last_class = $class;
        }
    } else {
        $classes = array();
    }

    //
    // Load the notifications
    //
    $strsql = "SELECT notifications.id, "
        . "notifications.name, "
        . "notifications.ntrigger, "
        . "notifications.ntype, "
        . "notifications.offset_days, "
        . "notifications.status, "
        . "notifications.status AS status_text, "
        . "TIME_FORMAT(notifications.time_of_day, '%l:%i %p') AS time_of_day "
        . "FROM ciniki_course_offering_notifications AS notifications "
        . "WHERE notifications.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "AND notifications.status > 0 "
        . "AND notifications.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'notification');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.233', 'msg'=>'Unable to load not', 'err'=>$rc['err']));
    }
    $notifications = isset($rc['rows']) ? $rc['rows'] : array();
    
    //
    // Load the registrations
    //
    $strsql = "SELECT registrations.id "
        . "FROM ciniki_course_offering_registrations AS registrations "
        . "WHERE registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'reg');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.249', 'msg'=>'Unable to load reg', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['rows']) ? $rc['rows'] : array();
    

    // 
    // Build the queue
    //
    $nqueue = array();
    foreach($notifications as $notification) {
        
        foreach($registrations as $registration) {
            //
            // Session Start Trigger
            //
            if( $notification['ntrigger'] == 60 && $offering['start_date'] != '0000-00-00' ) { 
                $dt = new DateTime($offering['start_date'] . ' ' . $notification['time_of_day'], $intl_timezone);
                if( $notification['offset_days'] < 0 ) {
                    $dt->sub(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                } elseif( $notification['offset_days'] > 0 ) {
                    $dt->add(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                }
                $dt->setTimezone($utc_timezone);
                if( $dt > $dt_now ) {
                    $nqueue["{$notification['id']}-{$registration['id']}-0"] = array(
                        'notification_id' => $notification['id'],
                        'registration_id' => $registration['id'],
                        'class_id' => 0,
                        'scheduled_dt' => $dt->format('Y-m-d H:i:s'),
                        );
                }
            }
            //
            // Session End trigger
            //
            elseif( $notification['ntrigger'] == 80 && $offering['end_date'] != '0000-00-00' ) {
                $dt = new DateTime($offering['end_date'] . ' ' . $notification['time_of_day'], $intl_timezone);
                if( $notification['offset_days'] < 0 ) {
                    $dt->sub(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                } elseif( $notification['offset_days'] > 0 ) {
                    $dt->add(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                }
                $dt->setTimezone($utc_timezone);
                if( $dt > $dt_now ) {
                    $nqueue["{$notification['id']}-{$registration['id']}-0"] = array(
                        'notification_id' => $notification['id'],
                        'registration_id' => $registration['id'],
                        'class_id' => 0,
                        'scheduled_dt' => $dt->format('Y-m-d H:i:s'),
                        );
                }
            }
            //
            // Each Class Start
            //
            elseif( $notification['ntrigger'] == 90 && count($classes) > 0 ) {
                foreach($classes as $class) {
                    $dt = new DateTime($class['class_date'] . ' ' . $notification['time_of_day'], $intl_timezone);
                    if( $notification['offset_days'] < 0 ) {
                        $dt->sub(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                    } elseif( $notification['offset_days'] > 0 ) {
                        $dt->add(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                    }
                    $dt->setTimezone($utc_timezone);
                    if( $dt > $dt_now ) {
                        $nqueue["{$notification['id']}-{$registration['id']}-{$class['id']}"] = array(
                            'notification_id' => $notification['id'],
                            'registration_id' => $registration['id'],
                            'class_id' => $class['id'],
                            'scheduled_dt' => $dt->format('Y-m-d H:i:s'),
                            );
                    }
                }
            }
            //
            // First Class Start
            //
            elseif( $notification['ntrigger'] == 94 && $first_class != null ) {
                $dt = new DateTime($first_class['class_date'] . ' ' . $notification['time_of_day'], $intl_timezone);
                if( $notification['offset_days'] < 0 ) {
                    $dt->sub(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                } elseif( $notification['offset_days'] > 0 ) {
                    $dt->add(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                }
                $dt->setTimezone($utc_timezone);
                if( $dt > $dt_now ) {
                    $nqueue["{$notification['id']}-{$registration['id']}-{$first_class['id']}"] = array(
                        'notification_id' => $notification['id'],
                        'registration_id' => $registration['id'],
                        'class_id' => $first_class['id'],
                        'scheduled_dt' => $dt->format('Y-m-d H:i:s'),
                        );
                }
            }
            //
            // Last Class Start
            //
            elseif( $notification['ntrigger'] == 95 && $last_class != null ) {
                $dt = new DateTime($last_class['class_date'] . ' ' . $notification['time_of_day'], $intl_timezone);
                if( $notification['offset_days'] < 0 ) {
                    $dt->sub(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                } elseif( $notification['offset_days'] > 0 ) {
                    $dt->add(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                }
                $dt->setTimezone($utc_timezone);
                if( $dt > $dt_now ) {
                    $nqueue["{$notification['id']}-{$registration['id']}-{$last_class['id']}"] = array(
                        'notification_id' => $notification['id'],
                        'registration_id' => $registration['id'],
                        'class_id' => $last_class['id'],
                        'scheduled_dt' => $dt->format('Y-m-d H:i:s'),
                        );
                }
            }
            //
            // Other Class Start - Not first or last
            //
            elseif( $notification['ntrigger'] == 96 && count($classes) > 0 ) {
                foreach($classes as $class) {
                    // Skip first and last class
                    if( $class['id'] == $first_class['id'] ) {
                        continue;
                    } elseif( $class['id'] == $last_class['id'] ) {
                        continue;
                    }
                    $dt = new DateTime($class['class_date'] . ' ' . $notification['time_of_day'], $intl_timezone);
                    if( $notification['offset_days'] < 0 ) {
                        $dt->sub(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                    } elseif( $notification['offset_days'] > 0 ) {
                        $dt->add(new DateInterval('P' . abs($notification['offset_days']) . 'D'));
                    }
                    $dt->setTimezone($utc_timezone);
                    if( $dt > $dt_now ) {
                        $nqueue["{$notification['id']}-{$registration['id']}-{$class['id']}"] = array(
                            'notification_id' => $notification['id'],
                            'registration_id' => $registration['id'],
                            'class_id' => $class['id'],
                            'scheduled_dt' => $dt->format('Y-m-d H:i:s'),
                            );
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'nqueue'=>$nqueue);
}
?>
