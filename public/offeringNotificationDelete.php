<?php
//
// Description
// -----------
// This method will delete an offering notification.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:            The ID of the tenant the offering notification is attached to.
// notification_id:            The ID of the offering notification to be removed.
//
// Returns
// -------
//
function ciniki_courses_offeringNotificationDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'notification_id'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Offering Notification'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringNotificationDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current settings for the offering notification
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_course_offering_notifications "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['notification_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'notification');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['notification']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.176', 'msg'=>'Offering Notification does not exist.'));
    }
    $notification = $rc['notification'];

    //
    // Check for any dependencies before deleting
    //
    $strsql = "SELECT queue.id, "
        . "queue.uuid "
        . "FROM ciniki_course_offering_nqueue AS queue "
        . "WHERE queue.notification_id = '" . ciniki_core_dbQuote($ciniki, $args['notification_id']) . "' "
        . "AND queue.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'queue');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.190', 'msg'=>'Unable to load queue', 'err'=>$rc['err']));
    }
    $queue = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Check if any modules are currently using this object
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectCheckUsed');
    $rc = ciniki_core_objectCheckUsed($ciniki, $args['tnid'], 'ciniki.courses.offering_notification', $args['notification_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.177', 'msg'=>'Unable to check if the offering notification is still being used.', 'err'=>$rc['err']));
    }
    if( $rc['used'] != 'no' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.178', 'msg'=>'The offering notification is still in use. ' . $rc['msg']));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Remove the notifications from the queue
    //
    foreach($queue as $item) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.courses.offering_nqueue', $item['id'], $item['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.191', 'msg'=>'Unable to delete queue', 'err'=>$rc['err']));
        }
    }

    //
    // Remove the notification
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.courses.offering_notification',
        $args['notification_id'], $notification['uuid'], 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return $rc;
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'courses');

    return array('stat'=>'ok');
}
?>
