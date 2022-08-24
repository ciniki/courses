<?php
//
// Description
// -----------
// This method will add a new offering notification for the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to add the Offering Notification to.
//
// Returns
// -------
//
function ciniki_courses_offeringNotificationAdd(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Program Session'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Name'),
        'ntrigger'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Trigger'),
        'ntype'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notification Type'),
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'),
        'offset_days'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Offset Days'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'time_of_day'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'time', 'name'=>'Time of Day'),
        'subject'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Subject'),
        'content'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Content'),
        'form_label'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Form Label'),
        'form_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Form'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringNotificationAdd');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

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
    // Add the offering notification to the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.offering_notification', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return $rc;
    }
    $notification_id = $rc['id'];

    //
    // Update the notification queue
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
    $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $args['tnid'], $args['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.250', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.courses.offering_notification', 'object_id'=>$notification_id));

    return array('stat'=>'ok', 'id'=>$notification_id);
}
?>
