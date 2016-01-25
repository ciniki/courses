<?php
//
// Description
// ===========
// This method returns all the information for a offering (a group of offerings at the same time location)
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
// Returns
// -------
//
function ciniki_courses_offeringRegistrations($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'), 
        'output'=>array('required'=>'no', 'blank'=>'no', 'default'=>'pdf', 'name'=>'Output Format'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringRegistrations'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//
	// Load business details
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'businessDetails');
	$rc = ciniki_businesses_businessDetails($ciniki, $args['business_id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['details']) && is_array($rc['details']) ) {	
		$business_details = $rc['details'];
	} else {
		$business_details = array();
	}

	//
	// Load the invoice settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_course_settings', 'business_id', $args['business_id'],
		'ciniki.courses', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['settings']) ) {
		$courses_settings = $rc['settings'];
	} else {
		$courses_settings = array();
	}
	
	//
	// Load the template
	//
	$rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'templates', 'offeringregistrations');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$fn = $rc['function_call'];

	$rc = $fn($ciniki, $args['business_id'], $args['offering_id'], $business_details, $courses_settings);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	$title = $rc['offering']['code'] . '_' . $rc['offering']['course_name'] . '_' . $rc['offering']['course_name'];

	$filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $title));
	if( isset($rc['pdf']) ) {
		$rc['pdf']->Output($filename . '.pdf', 'D');
	}

	return array('stat'=>'exit');
}
?>
