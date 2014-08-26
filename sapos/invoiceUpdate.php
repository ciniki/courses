<?php
//
// Description
// ===========
// This method will be called whenever a item is updated in an invoice.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_courses_sapos_invoiceUpdate($ciniki, $business_id, $invoice_id, $item) {

	//
	// An course offering was added to an invoice item, get the details and see if we need to 
	// create a registration for this course offering
	//
	if( isset($item['object']) && $item['object'] == 'ciniki.courses.offering_registration' && isset($item['object_id']) ) {
		//
		// Check the course offering registration exists
		//
		$strsql = "SELECT id, offering_id, customer_id, num_seats "
			. "FROM ciniki_course_offering_registrations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'registration');
		if( $rc['stat'] != 'ok' ) {	
			return $rc;
		}
		if( !isset($rc['registration']) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1972', 'msg'=>'Unable to find course registration'));
		}
		$registration = $rc['registration'];

		//
		// Pull the customer id from the invoice, see if it's different
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
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1973', 'msg'=>'Unable to find invoice'));
		}
		$invoice = $rc['invoice'];
		
		//
		// If the customer is different, update the registration
		//
		if( $registration['customer_id'] != $invoice['customer_id'] ) {
			$reg_args = array('customer_id'=>$invoice['customer_id']);
			$rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.courses.offering_registration', 
				$registration['id'], $reg_args, 0x04);
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
		}

		return array('stat'=>'ok');
	}

	return array('stat'=>'ok');
}
?>
