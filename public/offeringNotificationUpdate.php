<?php
//
// Description
// ===========
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_offeringNotificationUpdate(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'notification_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering Notification'),
        'offering_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Program Session'),
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'),
        'ntrigger'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Trigger'),
        'ntype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notification Type'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'offset_days'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Offset Days'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'time_of_day'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'time', 'name'=>'Time of Day'),
        'subject'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subject'),
        'content'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Content'),
        'form_label'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Form Label'),
        'form_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Form'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringNotificationUpdate');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the current notification
    //
    $strsql = "SELECT notifications.id, "
        . "notifications.offering_id, "
        . "notifications.name, "
        . "notifications.ntrigger, "
        . "notifications.ntype, "
        . "notifications.flags, "
        . "notifications.offset_days, "
        . "notifications.status "
        . "FROM ciniki_course_offering_notifications AS notifications "
        . "WHERE notifications.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND notifications.id = '" . ciniki_core_dbQuote($ciniki, $args['notification_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'notification');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.239', 'msg'=>'Unable to load notification', 'err'=>$rc['err']));
    }
    if( !isset($rc['notification']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.240', 'msg'=>'Unable to find requested notification'));
    }
    $notification = $rc['notification'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the Offering Notification in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.courses.offering_notification', $args['notification_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return $rc;
    }

    //
    // Update the notification queue
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
    $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $args['tnid'], $notification['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.248', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
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

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.courses.offering_notification', 'object_id'=>$args['notification_id']));

    return array('stat'=>'ok');
}
?>
