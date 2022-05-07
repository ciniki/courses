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
// <rsp stat='ok' />
//
function ciniki_courses_offeringClassDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringClassDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing class information
    //
    $strsql = "SELECT uuid, offering_id FROM ciniki_course_offering_classes "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'class');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['class']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.30', 'msg'=>'Class does not exist'));
    }
    $class = $rc['class'];

    //
    // Delete the offering
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.courses.offering_class', $args['class_id'], $class['uuid'], 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the condensed date for the course
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'updateCondensedDate');
    $rc = ciniki_courses_updateCondensedDate($ciniki, $args['tnid'], $class['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the notification queue
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
    $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $args['tnid'], $class['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.257', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
    }

    return array('stat'=>'ok');
}
?>
