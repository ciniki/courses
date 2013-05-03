<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the course to.
// name:				The name of the course.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_courses_offeringClassUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'), 
        'class_date'=>array('required'=>'no', 'blank'=>'no', 'type'=>'date', 'name'=>'Date'), 
        'start_time'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'time', 'name'=>'Start Time'), 
        'end_time'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'time', 'name'=>'End Time'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringClassUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	//
	// Get the existing class information
	//
	$strsql = "SELECT id, offering_id FROM ciniki_course_offering_classes "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'class');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['class']) ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1282', 'msg'=>'Class does not exist'));
	}
	$class = $rc['class'];

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Add all the fields to the change log
	//
	$strsql = "UPDATE ciniki_course_offering_classes SET last_updated = UTC_TIMESTAMP()";

	$changelog_fields = array(
		'class_date',
		'start_time',
		'end_time',
		'notes',
		);
	foreach($changelog_fields as $field) {
		if( isset($args[$field]) ) {
			$strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.courses', 
				'ciniki_course_history', $args['business_id'], 
				2, 'ciniki_course_offering_classes', $args['class_id'], $field, $args[$field]);
		}
	}
	$strsql .= "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.courses');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1283', 'msg'=>'Unable to update course'));	
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'updateCondensedDate');
    $rc = ciniki_courses_updateCondensedDate($ciniki, $args['business_id'], $class['offering_id']);
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
		'args'=>array('id'=>$args['class_id']));

	return array('stat'=>'ok');
}
?>
