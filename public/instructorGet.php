<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business.
// instructor_id:		The ID of the instructor to get.
//
// Returns
// -------
//
function ciniki_courses_instructorGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'instructor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Instructor'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.instructorGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//	$date_format = ciniki_users_dateFormat($ciniki);
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
//	$time_format = ciniki_users_timeFormat($ciniki);

	//
	// Get the main information
	//
	$strsql = "SELECT ciniki_course_instructors.id, "
		. "ciniki_course_instructors.first, "
		. "ciniki_course_instructors.last, "
		. "ciniki_course_instructors.permalink, "
		. "ciniki_course_instructors.primary_image_id, "
		. "ciniki_course_instructors.webflags, "
		. "IF((ciniki_course_instructors.webflags&0x01)=1, 'Hidden', 'Visible') AS web_visible, "
		. "ciniki_course_instructors.short_bio, "
		. "ciniki_course_instructors.full_bio, "
		. "ciniki_course_instructors.url "
		. "FROM ciniki_course_instructors "
		. "WHERE ciniki_course_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_course_instructors.id = '" . ciniki_core_dbQuote($ciniki, $args['instructor_id']) . "' "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
		array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
			'fields'=>array('id', 'first', 'last', 'permalink', 'primary_image_id', 'webflags', 'web_visible', 'short_bio', 'full_bio', 'url')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['instructors']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'1270', 'msg'=>'Unable to find instructor'));
	}
	$instructor = $rc['instructors'][0]['instructor'];
	
	return array('stat'=>'ok', 'instructor'=>$instructor);
}
?>
