<?php
//
// Description
// ===========
// This cron checks for session notifications that need to be sent from the notification queue.
//
// Arguments
// =========
// 
// Returns
// =======
// <rsp stat="ok" />
//
function ciniki_courses_cron_jobs(&$ciniki) {
    ciniki_cron_logMsg($ciniki, 0, array('code'=>'0', 'msg'=>'Checking for courses jobs', 'severity'=>'5'));

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNotificationSend');

    //
    // Set date range to check for notifications to send
    // Up to 1 hour behind current time notifications will be sent.
    //
    $dt_now = new DateTime('now', new DateTimezone('UTC'));
    $dt_start = clone($dt_now);
    $dt_start->sub(new DateInterval('PT1H'));

    //
    // Check for any notifications that should have been sent in the last hour
    //
    $strsql = "SELECT nqueue.id, "
        . "nqueue.uuid, "
        . "nqueue.tnid, "
        . "nqueue.notification_id, "
        . "registrations.offering_id, "
        . "registrations.customer_id, "
        . "registrations.student_id "
        . "FROM ciniki_course_offering_nqueue AS nqueue "
        . "INNER JOIN ciniki_course_offering_registrations AS registrations ON ("
            . "nqueue.registration_id = registrations.id "
            . ") "
        . "WHERE nqueue.scheduled_dt >= '" . ciniki_core_dbQuote($ciniki, $dt_start->format('Y-m-d H:i:s')) . "' "
        . "AND nqueue.scheduled_dt <= '" . ciniki_core_dbQuote($ciniki, $dt_now->format('Y-m-d H:i:s')) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.242', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $nqueue = isset($rc['rows']) ? $rc['rows'] : array();
    
    foreach($nqueue as $item) {
        $rc = ciniki_courses_offeringNotificationSend($ciniki, $item['tnid'], $item);
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $item['tnid'], array('code'=>'ciniki.courses.243', 
                'msg'=>'Unable to send notification queue item: ' . $item['id'],
                'cron_id'=>0, 'severity'=>50, 'err'=>$rc['err'],
                ));
        }

        //
        // Remove nqueue item
        //
        $rc = ciniki_core_objectDelete($ciniki, $item['tnid'], 'ciniki.courses.offering_nqueue', $item['id'], $item['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_cron_logMsg($ciniki, $item['tnid'], array('code'=>'ciniki.courses.244', 
                'msg'=>'Unable to remove notification queue item: ' . $item['id'],
                'cron_id'=>0, 'severity'=>50, 'err'=>$rc['err'],
                ));
        }
    }

    return array('stat'=>'ok');
}
?>
