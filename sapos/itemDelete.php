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
function ciniki_courses_sapos_itemDelete($ciniki, $business_id, $invoice_id, $item) {

    //
    // An course offering was added to an invoice item, get the details and see if we need to 
    // create a registration for this course offering
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.courses.offering_registration' && isset($item['object_id']) ) {
        //
        // Check the course offering registration exists
        //
        $strsql = "SELECT id, uuid, offering_id, customer_id, num_seats "
            . "FROM ciniki_course_offering_registrations "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'registration');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            // Don't worry if can't find existing reg, probably database error
            return array('stat'=>'ok');
        }
        $registration = $rc['registration'];

        //
        // Remove the invoice from the registration, don't delete it
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $business_id, 'ciniki.courses.offering_registration', 
            $registration['id'], array('invoice_id'=>'0'), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
