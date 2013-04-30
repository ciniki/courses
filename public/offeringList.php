<?php
//
// Description
// -----------
// This method will return the list of course offerings for a business.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_courses_offeringList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'current'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Current'),
		'upcoming'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Upcoming'),
		'past'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Past'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];

	if( (!isset($args['current']) || $args['current'] != 'yes')
		&& (!isset($args['upcoming']) || $args['upcoming'] != 'yes')
		&& (!isset($args['past']) || $args['past'] != 'yes')
		) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1260', 'msg'=>'You must specify the type of list to return: past, current, upcoming.'));
	}
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $ac = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Query for the course offerings
	//
	$rsp = array('stat'=>'ok', 'past'=>array(), 'current'=>array(), 'upcoming'=>array());
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	if( isset($args['current']) && $args['current'] == 'yes' ) {
		$strsql = "SELECT ciniki_course_offerings.id, "
			. "ciniki_course_offerings.name AS offering_name, "
			. "ciniki_course_offerings.course_id, "
			. "ciniki_courses.name AS course_name, "
			. "ciniki_courses.code, "
			. "IFNULL(DATE_FORMAT(MIN(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'No dates set') AS start_date, "
			. "UNIX_TIMESTAMP(MIN(ciniki_course_offering_classes.class_date)) AS start_date_ts, "
			. "DATE_FORMAT(MAX(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
			. "UNIX_TIMESTAMP(MAX(ciniki_course_offering_classes.class_date)) AS end_date_ts "
			. "FROM ciniki_course_offerings "
			. "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
				. "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "LEFT JOIN ciniki_course_offering_classes ON (ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
				. "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_course_offerings.status = 10 "
			. "GROUP BY ciniki_course_offerings.id "
			. "HAVING start_date_ts <= UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
			. "AND end_date_ts >= UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
			. "ORDER BY ciniki_courses.code, ciniki_courses.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering',
				'fields'=>array('id', 'offering_name', 'course_id', 'course_name', 'code', 'start_date', 'end_date')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['offerings']) ) {
			$rsp['current'] = $rc['offerings'];
		}
	}
	if( isset($args['upcoming']) && $args['upcoming'] == 'yes' ) {
		$strsql = "SELECT ciniki_course_offerings.id, "
			. "ciniki_course_offerings.name AS offering_name, "
			. "ciniki_course_offerings.course_id, "
			. "ciniki_courses.name AS course_name, "
			. "ciniki_courses.code, "
			. "IFNULL(DATE_FORMAT(MIN(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'No dates set') AS start_date, "
			. "UNIX_TIMESTAMP(MIN(ciniki_course_offering_classes.class_date)) AS start_date_ts, "
			. "DATE_FORMAT(MAX(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date "
			. "FROM ciniki_course_offerings "
			. "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
				. "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "LEFT JOIN ciniki_course_offering_classes ON (ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
				. "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (ciniki_course_offerings.status = 10 || ciniki_course_offerings.status = 0 ) "
			. "GROUP BY ciniki_course_offerings.id "
			. "HAVING start_date = 'No dates set' OR start_date_ts > UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
			. "ORDER BY ciniki_courses.code, ciniki_courses.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering',
				'fields'=>array('id', 'offering_name', 'course_id', 'course_name', 'code', 'start_date', 'end_date')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['offerings']) ) {
			$rsp['upcoming'] = $rc['offerings'];
		}
	}
	if( isset($args['past']) && $args['past'] == 'yes' ) {
		$strsql = "SELECT ciniki_course_offerings.id, "
			. "ciniki_course_offerings.name AS offering_name, "
			. "ciniki_course_offerings.course_id, "
			. "ciniki_courses.name AS course_name, "
			. "ciniki_courses.code, "
			. "IFNULL(DATE_FORMAT(MIN(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'No dates set') AS start_date, "
			. "UNIX_TIMESTAMP(MIN(ciniki_course_offering_classes.class_date)) AS start_date_ts, "
			. "DATE_FORMAT(MAX(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
			. "UNIX_TIMESTAMP(MAX(ciniki_course_offering_classes.class_date)) AS end_date_ts "
			. "FROM ciniki_course_offerings "
			. "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
				. "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "LEFT JOIN ciniki_course_offering_classes ON (ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
				. "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
			. "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND (ciniki_course_offerings.status = 10 || ciniki_course_offerings.status = 0 ) "
			. "GROUP BY ciniki_course_offerings.id "
			. "HAVING end_date_ts < UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
			. "ORDER BY ciniki_courses.code, ciniki_courses.name "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering',
				'fields'=>array('id', 'offering_name', 'course_id', 'course_name', 'code', 'start_date', 'end_date')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['offerings']) ) {
			$rsp['past'] = $rc['offerings'];
		}
	}

	return $rsp;
}
?>
