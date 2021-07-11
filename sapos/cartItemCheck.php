<?php
//
// Description
// ===========
// This function will lookup an invoice item and make sure it is still available for purchase.
// This function is called for any items previous to paypal checkout.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_courses_sapos_cartItemCheck($ciniki, $tnid, $customer, $args) {

    //
    // Load the offering
    //
    if( isset($args['object']) && $args['object'] == 'ciniki.courses.offering' 
        && isset($args['object_id']) && $args['object_id'] > 0 
        ) {
        $strsql = "SELECT ciniki_course_offerings.id AS offering_id, "
            . "ciniki_course_offerings.code AS offering_code, "
            . "ciniki_courses.code, "
            . "CONCAT_WS(' - ', ciniki_courses.name, ciniki_course_offerings.name) AS description, "
            . "ciniki_course_offerings.reg_flags, "
            . "ciniki_course_offerings.num_seats, "
            . "ciniki_course_offering_prices.id AS price_id, "
            . "ciniki_course_offering_prices.name AS price_name, "
            . "ciniki_course_offering_prices.available_to, "
            . "ciniki_course_offering_prices.unit_amount, "
            . "ciniki_course_offering_prices.unit_discount_amount, "
            . "ciniki_course_offering_prices.unit_discount_percentage, "
            . "ciniki_course_offering_prices.taxtype_id "
            . "FROM ciniki_course_offering_prices "
            . "INNER JOIN ciniki_course_offerings ON ("
                . "ciniki_course_offering_prices.offering_id = ciniki_course_offerings.id "
                . "AND ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . ") "
            . "INNER JOIN ciniki_courses ON ("
                . "ciniki_course_offerings.course_id = ciniki_courses.id "
                . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_course_offering_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_course_offering_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'offerings', 'fname'=>'offering_id',
                'fields'=>array('offering_id', 'price_id', 'price_name', 'code', 'offering_code', 'offering_id', 'description', 'reg_flags', 'num_seats', 
                    'available_to', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['offerings']) || count($rc['offerings']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.152', 'msg'=>'No course found.'));     
        }
        $item = array_pop($rc['offerings']);

        //
        // Check the number of seats remaining
        //
        $strsql = "SELECT 'num_seats', SUM(num_seats) AS num_seats "
            . "FROM ciniki_course_offering_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $item['offering_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.courses', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num']['num_seats'] >= $item['num_seats'] ) {
            return array('stat'=>'unavailable', 'err'=>array('code'=>'ciniki.courses.126', 'msg'=>"We're sorry, but " . $item['description'] . " is now sold out."));
        }
    }

    return array('stat'=>'ok');
}
?>
