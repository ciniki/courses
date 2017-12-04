<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant.
// class_id:            The ID of the class to get.
//
// Returns
// -------
//
function ciniki_courses_offeringClassGet($ciniki) {
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringClassGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_course_offering_classes.id, "
        . "IFNULL(DATE_FORMAT(ciniki_course_offering_classes.class_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS class_date, "
        . "IFNULL(DATE_FORMAT(ciniki_course_offering_classes.start_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS start_time, "
        . "IFNULL(DATE_FORMAT(ciniki_course_offering_classes.end_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS end_time, "
        . "ciniki_course_offering_classes.notes "
        . "FROM ciniki_course_offering_classes "
        . "WHERE ciniki_course_offering_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_course_offering_classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'classes', 'fname'=>'id', 'name'=>'class',
            'fields'=>array('id', 'class_date', 'start_time', 'end_time', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['classes']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.courses.31', 'msg'=>'Unable to find class'));
    }
    $class = $rc['classes'][0]['class'];
    
    return array('stat'=>'ok', 'class'=>$class);
}
?>
