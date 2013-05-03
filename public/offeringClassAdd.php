<?php
//
// Description
// ===========
// This method will add a new course to the courses table.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the course to.
//
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_courses_offeringClassAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
        'class_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Date'), 
        'start_time'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'time', 'name'=>'Start Time'), 
        'end_time'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'type'=>'time', 'name'=>'End Time'), 
        'notes'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringClassAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get a new UUID
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
	$rc = ciniki_core_dbUUID($ciniki, 'ciniki.courses');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
		return $rc;
	}
	$args['uuid'] = $rc['uuid'];

	//
	// Add the course to the database
	//
	$strsql = "INSERT INTO ciniki_course_offering_classes (uuid, business_id, "
		. "course_id, offering_id, class_date, start_time, end_time, notes, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['class_date']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['start_time']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['end_time']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['notes']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.courses');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1264', 'msg'=>'Unable to add course'));
	}
	$class_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'uuid',
		'course_id',
		'offering_id',
		'class_date',
		'start_time',
		'end_time',
		'notes',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.courses', 
				'ciniki_course_history', $args['business_id'], 
				1, 'ciniki_course_offering_classes', $class_id, $field, $args[$field]);
		}
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'updateCondensedDate');
    $rc = ciniki_courses_updateCondensedDate($ciniki, $args['business_id'], $args['offering_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.courses');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'courses');

	$ciniki['syncqueue'][] = array('push'=>'ciniki.courses.class', 
		'args'=>array('id'=>$class_id));

	return array('stat'=>'ok', 'id'=>$class_id);
}
?>
