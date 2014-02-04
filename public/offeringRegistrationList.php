<?php
//
// Description
// -----------
// This method will return the list of customers who have registered for an course offering.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get course offering for.
//
// Returns
// -------
//
function ciniki_courses_offeringRegistrationList($ciniki) {
	//
	// Find all the required and optional arguments
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $ac = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringRegistrationList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   
	$modules = $ac['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Build the query string to get the list of registrations
	//
	if( isset($modules['ciniki.sapos']) ) {
		//
		// Load the status maps for the text description of each status
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceStatusMaps');
		$rc = ciniki_sapos_invoiceStatusMaps($ciniki);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$status_maps = $rc['maps'];
		$status_maps[0] = 'No Invoice';

		$strsql = "SELECT ciniki_course_offering_registrations.id, "
			. "ciniki_course_offering_registrations.customer_id, "
			. "IFNULL(ciniki_customers.display_name, '') AS customer_name, "
			. "ciniki_course_offering_registrations.num_seats, "
			. "ciniki_course_offering_registrations.invoice_id, "
			. "ciniki_sapos_invoices.status AS invoice_status, "
			. "IFNULL(ciniki_sapos_invoices.status, 0) AS invoice_status_text "
			. "FROM ciniki_course_offering_registrations "
			. "LEFT JOIN ciniki_customers ON (ciniki_course_offering_registrations.customer_id = ciniki_customers.id "
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "LEFT JOIN ciniki_sapos_invoices ON (ciniki_course_offering_registrations.invoice_id = ciniki_sapos_invoices.id "
				. "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_course_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
			. "ORDER BY ciniki_customers.last, ciniki_customers.first "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
				'fields'=>array('id', 'customer_id', 'customer_name', 'num_seats', 
					'invoice_id', 'invoice_status', 'invoice_status_text'),
				'maps'=>array('invoice_status_text'=>$status_maps)),
			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1518', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
		}
		if( !isset($rc['registrations']) ) {
			return array('stat'=>'ok', 'registrations'=>array());
		}
		$registrations = $rc['registrations'];

	} else {
		$strsql = "SELECT ciniki_course_offering_registrations.id, "
			. "ciniki_course_offering_registrations.customer_id, "
			. "IFNULL(ciniki_customers.display_name, '') AS customer_name, "
			. "ciniki_course_offering_registrations.num_seats, "
			. "ciniki_course_offering_registrations.invoice_id "
			. "FROM ciniki_course_offering_registrations "
			. "LEFT JOIN ciniki_customers ON (ciniki_course_offering_registrations.customer_id = ciniki_customers.id "
				. "AND ciniki_customers.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. ") "
			. "WHERE ciniki_course_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
			. "ORDER BY ciniki_customers.last, ciniki_customers.first "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
				'fields'=>array('id', 'customer_id', 'customer_name', 'num_seats', 'invoice_id')),
			));
		if( $rc['stat'] != 'ok' ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1522', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
		}
		if( !isset($rc['registrations']) ) {
			return array('stat'=>'ok', 'registrations'=>array());
		}
		$registrations = $rc['registrations'];
	}

	return array('stat'=>'ok', 'registrations'=>$registrations);
}
?>
