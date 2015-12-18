<?php
//
// Description
// ===========
// This function will be a callback when an item is added to ciniki.sapos.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_courses_sapos_itemAdd($ciniki, $business_id, $invoice_id, $item) {

	//
	// An course was added to an invoice item, get the details and see if we need to 
	// create a registration for this course offering
	//
	if( isset($item['object']) && $item['object'] == 'ciniki.courses.offering_price' && isset($item['object_id']) ) {
		//
		// Check the offering exists
		//
		$strsql = "SELECT ciniki_course_offerings.id, "
			. "CONCAT_WS(' - ', ciniki_courses.name, ciniki_course_offerings.name) AS name "
			. "FROM ciniki_course_offering_prices "
            . "INNER JOIN ciniki_course_offerings ON ("
                . "ciniki_course_offering_prices.offering_id = ciniki_course_offerings.id "
				. "AND ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . ") "
			. "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
				. "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_course_offering_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_course_offering_prices.id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['offering']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1974', 'msg'=>'Unable to find course'));
		}
		$offering = $rc['offering'];

		//
		// Load the customer for the invoice
		//
		$strsql = "SELECT id, customer_id "
			. "FROM ciniki_sapos_invoices "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['invoice']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1975', 'msg'=>'Unable to find invoice'));
		}
		$invoice = $rc['invoice'];
		
		//
		// Create the registration for the customer
		//
		$reg_args = array('offering_id'=>$offering['id'],
			'customer_id'=>$invoice['customer_id'],
			'num_seats'=>(isset($item['quantity'])?$item['quantity']:1),
			'invoice_id'=>$invoice['id'],
			'customer_notes'=>'',
			'notes'=>'',
			);
		$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.courses.offering_registration', 
			$reg_args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$reg_id = $rc['id'];

		return array('stat'=>'ok', 'object'=>'ciniki.courses.offering_registration', 'object_id'=>$reg_id);
	}

	//
	// An course was added to an invoice item, get the details and see if we need to 
	// create a registration for this course offering
	//
	if( isset($item['object']) && $item['object'] == 'ciniki.courses.offering' && isset($item['object_id']) ) {
		//
		// Check the offering exists
		//
		$strsql = "SELECT ciniki_course_offerings.id, "
			. "CONCAT_WS(' - ', ciniki_courses.name, ciniki_course_offerings.name) AS name "
			. "FROM ciniki_course_offerings "
			. "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
				. "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. ") "
			. "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['offering']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1974', 'msg'=>'Unable to find course'));
		}
		$offering = $rc['offering'];

		//
		// Load the customer for the invoice
		//
		$strsql = "SELECT id, customer_id "
			. "FROM ciniki_sapos_invoices "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['invoice']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1975', 'msg'=>'Unable to find invoice'));
		}
		$invoice = $rc['invoice'];
		
		//
		// Create the registration for the customer
		//
		$reg_args = array('offering_id'=>$offering['id'],
			'customer_id'=>$invoice['customer_id'],
			'num_seats'=>(isset($item['quantity'])?$item['quantity']:1),
			'invoice_id'=>$invoice['id'],
			'customer_notes'=>'',
			'notes'=>'',
			);
		$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.courses.offering_registration', 
			$reg_args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$reg_id = $rc['id'];

		return array('stat'=>'ok', 'object'=>'ciniki.courses.offering_registration', 'object_id'=>$reg_id);
	}

	//
	// If a registration was added to an invoice, update the invoice_id for the registration
	//
	if( isset($item['object']) && $item['object'] == 'ciniki.courses.offering_registration' && isset($item['object_id']) ) {
		//
		// Check the registration exists
		//
		$strsql = "SELECT id, invoice_id "
			. "FROM ciniki_course_offering_registrations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'registration');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['registration']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1977', 'msg'=>'Unable to find course registration'));
		}
		$registration = $rc['registration'];
	
		//
		// If the registration does not already have an invoice
		//
		if( $registration['invoice_id'] == '0' ) {
			$reg_args = array('invoice_id'=>$invoice_id);
			ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
			$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.courses.offering_registration', 
				$registration['id'], $reg_args, 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			return array('stat'=>'ok');
		}
	}

	return array('stat'=>'ok');
}
?>
