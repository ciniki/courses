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
function ciniki_courses_sapos_itemInvoiceMerge($ciniki, $tnid, $item, $primary_invoice_id, $secondary_invoice_id) {

    //
    // Update registrations with new invoice id
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.courses.offering_registration' && isset($item['object_id']) ) {
        //
        // Check the course registration
        //
        $strsql = "SELECT id, uuid, festival_id, invoice_id "
            . "FROM ciniki_courses_offering_registration "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'itemsale');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            // Don't worry if can't find existing reg, probably database error
            return array('stat'=>'ok');
        }
        $registration = $rc['registration'];

        //
        // Update the item
        //
        if( $registration['invoice_id'] != $primary_invoice_id ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.courses.offering_registration', $item['object_id'], [
                'invoice_id' => $primary_invoice_id,
                ], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.311', 'msg'=>'Unable to update the registration', 'err'=>$rc['err']));
            }
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
