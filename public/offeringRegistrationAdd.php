<?php
//
// Description
// ===========
// This method will add a new registration for an course offering.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the file to.
// offering_id:			The ID of the course offering the file is attached to.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_courses_offeringRegistrationAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course Offering'),
		'customer_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Customer'),
		'num_seats'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Number of Seats'),
		'invoice_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Invoice'),
        'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Customer Notes'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Notes'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringRegistrationAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Add the registration to the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	return ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.courses.offering_registration', $args);
}
?>
