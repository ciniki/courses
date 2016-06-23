<?php
//
// Description
// ===========
// This function completes the course registration when the customer has submitted a payment and checkout cart.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_courses_sapos_cartItemPaymentReceived($ciniki, $business_id, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3221', 'msg'=>'No course specified.'));
    }

    if( !isset($args['price_id']) || $args['price_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3222', 'msg'=>'No course specified.'));
    }
    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3223', 'msg'=>'No course specified.'));
    }
    if( !isset($args['student_id']) || $args['student_id'] == 0 ) {
        $args['student_id'] = $args['customer_id'];
    }

    if( $args['object'] == 'ciniki.courses.offering' ) {
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
            . "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['offering']) ) {
            return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3224', 'msg'=>'Unable to find course'));
        }
        $offering = $rc['offering'];

        //
        // Create the registration for the customer
        //
        $reg_args = array('offering_id'=>$offering['id'],
            'customer_id'=>$args['customer_id'],
            'student_id'=>$args['student_id'],
            'num_seats'=>(isset($args['quantity'])?$args['quantity']:1),
            'invoice_id'=>$args['invoice_id'],
            'customer_notes'=>'',
            'notes'=>'',
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.courses.offering_registration', $reg_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $reg_id = $rc['id'];

        return array('stat'=>'ok', 'object'=>'ciniki.courses.offering_registration', 'object_id'=>$reg_id);
    }

    return array('stat'=>'ok');
}
?>
