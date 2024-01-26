<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_hooks_customerMerge($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');

    if( !isset($args['primary_customer_id']) || $args['primary_customer_id'] == '' 
        || !isset($args['secondary_customer_id']) || $args['secondary_customer_id'] == '' ) {
        return array('stat'=>'ok');
    }

    //
    // Keep track of how many items we've updated
    //
    $updated = 0;

    //
    // Get the list of exhibition items to update
    //
    $strsql = "SELECT id, customer_id, student_id "
        . "FROM ciniki_course_offering_registrations "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ("
            . "customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . "OR student_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
            . ") "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.121', 'msg'=>'Unable to find course registrations', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $update_args = array();
        if( $row['customer_id'] == $args['secondary_customer_id'] ) {
            $update_args['customer_id'] = $args['primary_customer_id'];
        }
        if( $row['student_id'] == $args['secondary_customer_id'] ) {
            $update_args['student_id'] = $args['primary_customer_id'];
        }
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.courses.offering_registration', $row['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.122', 'msg'=>'Unable to update course registration.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    //
    // Get the list of instructors
    //
    $strsql = "SELECT id, customer_id "
        . "FROM ciniki_course_instructors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['secondary_customer_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'items');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.290', 'msg'=>'Unable to find course instructors', 'err'=>$rc['err']));
    }
    $items = $rc['rows'];
    foreach($items as $i => $row) {
        $update_args = array();
        if( $row['customer_id'] == $args['secondary_customer_id'] ) {
            $update_args['customer_id'] = $args['primary_customer_id'];
        }
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.courses.instructor', $row['id'], $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.122', 'msg'=>'Unable to update instructor.', 'err'=>$rc['err']));
        }
        $updated++;
    }

    if( $updated > 0 ) {
        //
        // Update the last_change date in the tenant modules
        // Ignore the result, as we don't want to stop user updates if this fails.
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
        ciniki_tenants_updateModuleChangeDate($ciniki, $tnid, 'ciniki', 'courses');
    }

    return array('stat'=>'ok', 'updated'=>$updated);
}
?>
