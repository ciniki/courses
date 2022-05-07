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
function ciniki_courses_sapos_itemAdd($ciniki, $tnid, $invoice_id, $item) {

    //
    // An course was added to an invoice item, get the details and see if we need to 
    // create a registration for this course offering
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.courses.offering_price' && isset($item['object_id']) ) {
        //
        // Check the offering exists
        //
        $strsql = "SELECT ciniki_course_offerings.id, "
            . "ciniki_course_offerings.code AS offering_code, "
            . "ciniki_courses.code, "
            . "CONCAT_WS(' - ', ciniki_courses.name, ciniki_course_offerings.name) AS name "
            . "FROM ciniki_course_offering_prices "
            . "INNER JOIN ciniki_course_offerings ON ("
                . "ciniki_course_offering_prices.offering_id = ciniki_course_offerings.id "
                . "AND ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
                . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_course_offering_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_course_offering_prices.id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['offering']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.61', 'msg'=>'Unable to find course'));
        }
        $offering = $rc['offering'];
        if( $offering['code'] != '' ) { 
            $offering['name'] = $offering['code'] . ' - ' . $offering['name'];
        } elseif( $offering['offering_code'] != '' ) {
            $offering['name'] = $offering['offering_code'] . ' - ' . $offering['name'];
        }

        //
        // Load the customer for the invoice
        //
        $strsql = "SELECT id, customer_id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.62', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
        
        //
        // Create the registration for the customer
        //
        $reg_args = array('offering_id'=>$offering['id'],
            'customer_id'=>$invoice['customer_id'],
            'student_id'=>(isset($item['student_id']) ? $item['student_id'] : $invoice['customer_id']),
            'num_seats'=>(isset($item['quantity'])?$item['quantity']:1),
            'invoice_id'=>$invoice['id'],
            'customer_notes'=>'',
            'notes'=>'',
            );
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.courses.offering_registration', 
            $reg_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $reg_id = $rc['id'];

        //
        // Update the sold out flag
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringSoldOutUpdate');
        $rc = ciniki_courses_offeringSoldOutUpdate($ciniki, $tnid, $offering['id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.163', 'msg'=>'Unable to update', 'err'=>$rc['err']));
        }

        //
        // Update notification queue
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
        $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $tnid, $offering['id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.251', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
        }

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
            . "ciniki_course_offerings.code AS offering_code, "
            . "ciniki_courses.code, "
            . "CONCAT_WS(' - ', ciniki_courses.name, ciniki_course_offerings.name) AS name "
            . "FROM ciniki_course_offerings "
            . "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
                . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['offering']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.63', 'msg'=>'Unable to find course'));
        }
        $offering = $rc['offering'];
        if( $offering['code'] != '' ) { 
            $offering['name'] = $offering['code'] . ' - ' . $offering['name'];
        } elseif( $offering['offering_code'] != '' ) {
            $offering['name'] = $offering['offering_code'] . ' - ' . $offering['name'];
        }

        //
        // Load the customer for the invoice
        //
        $strsql = "SELECT id, customer_id "
            . "FROM ciniki_sapos_invoices "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $invoice_id) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.sapos', 'invoice');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['invoice']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.64', 'msg'=>'Unable to find invoice'));
        }
        $invoice = $rc['invoice'];
        
        //
        // Create the registration for the customer
        //
        $reg_args = array('offering_id'=>$offering['id'],
            'customer_id'=>$invoice['customer_id'],
            'student_id'=>(isset($item['student_id']) ? $item['student_id'] : $invoice['customer_id']),
            'num_seats'=>(isset($item['quantity'])?$item['quantity']:1),
            'invoice_id'=>$invoice['id'],
            'customer_notes'=>'',
            'notes'=>'',
            );
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.courses.offering_registration', 
            $reg_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $reg_id = $rc['id'];

        //
        // Update the sold out flag
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringSoldOutUpdate');
        $rc = ciniki_courses_offeringSoldOutUpdate($ciniki, $tnid, $offering['id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.164', 'msg'=>'Unable to update', 'err'=>$rc['err']));
        }

        //
        // Update notification queue
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
        $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $tnid, $offering['id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.252', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
        }

        return array('stat'=>'ok', 'object'=>'ciniki.courses.offering_registration', 'object_id'=>$reg_id);
    }

    //
    // If a registration was added to an invoice, update the invoice_id for the registration
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.courses.offering_registration' && isset($item['object_id']) ) {
        //
        // Check the registration exists
        //
        $strsql = "SELECT id, offering_id, invoice_id "
            . "FROM ciniki_course_offering_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'registration');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.65', 'msg'=>'Unable to find course registration'));
        }
        $registration = $rc['registration'];
    
        //
        // If the registration does not already have an invoice
        //
        if( $registration['invoice_id'] == '0' ) {
            $reg_args = array('invoice_id'=>$invoice_id);
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.courses.offering_registration', 
                $registration['id'], $reg_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }

            //
            // Update the sold out flag
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringSoldOutUpdate');
            $rc = ciniki_courses_offeringSoldOutUpdate($ciniki, $tnid, $registration['id']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.165', 'msg'=>'Unable to update', 'err'=>$rc['err']));
            }

            //
            // Update notification queue
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
            $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $tnid, $registration['offering_id']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.253', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
            }

            return array('stat'=>'ok');
        }
    }

    return array('stat'=>'ok');
}
?>
