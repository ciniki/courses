<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business.
// course_id:           The ID of the course to get.
//
// Returns
// -------
//
function ciniki_courses_courseGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.courseGet'); 
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
    $strsql = "SELECT ciniki_courses.id, "
        . "ciniki_courses.name, "
        . "ciniki_courses.code, "
        . "ciniki_courses.primary_image_id, "
        . "ciniki_courses.level, "
        . "ciniki_courses.type, "
        . "ciniki_courses.category, "
        . "ciniki_courses.short_description, "
        . "ciniki_courses.long_description "
        . "FROM ciniki_courses "
        . "WHERE ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_courses.id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'courses', 'fname'=>'id', 'name'=>'course',
            'fields'=>array('id', 'name', 
                'primary_image_id', 'code', 'level', 'type', 'category', 'short_description', 'long_description')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['courses']) ) {
        return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'1259', 'msg'=>'Unable to find course'));
    }
    $course = $rc['courses'][0]['course'];

    return array('stat'=>'ok', 'course'=>$course);
}
?>
