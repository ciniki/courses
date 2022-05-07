<?php
//
// Description
// -----------
// Load the current offering notification queue
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_offeringNQueueLoad(&$ciniki, $tnid, $offering_id) {

    $nqueue = array();
    //
    // Load the current queue
    //
    $strsql = "SELECT nqueue.id, "
        . "nqueue.uuid, "
        . "nqueue.notification_id, "
        . "nqueue.registration_id, "
        . "nqueue.class_id, "
        . "nqueue.scheduled_dt "
        . "FROM ciniki_course_offering_notifications AS notifications "
        . "INNER JOIN ciniki_course_offering_nqueue AS nqueue ON ("
            . "notifications.id = nqueue.notification_id "
            . "AND nqueue.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE notifications.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "AND notifications.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.230', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    } 
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $row) {
            $nqueue["{$row['notification_id']}-{$row['registration_id']}-{$row['class_id']}"] = $row;
        }
    }

    return array('stat'=>'ok', 'nqueue'=>$nqueue);
}
?>
