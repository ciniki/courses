<?php
//
// Description
// ===========
// This function will lookup an item that is being added to a shopping cart online.  This function
// has extra checks to make sure the requested item is available to the customer.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_courses_sapos_cartItemLookup($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.51', 'msg'=>'No course specified.'));
    }

    //
    // Lookup the requested course offering if specified along with a price_id
    //
    if( $args['object'] == 'ciniki.courses.offering' && isset($args['price_id']) && $args['price_id'] > 0 ) {
        $strsql = "SELECT ciniki_course_offerings.id AS offering_id, "
            . "ciniki_course_offerings.code AS code, "
            . "ciniki_courses.code AS course_code, "
            . "CONCAT_WS(' - ', ciniki_courses.name, ciniki_course_offerings.name) AS description, "
            . "ciniki_course_offerings.reg_flags, "
            . "ciniki_course_offerings.num_seats, "
            . "ciniki_course_offerings.form_id, "
            . "ciniki_course_offering_prices.id AS price_id, "
            . "ciniki_course_offering_prices.name AS price_name, "
            . "ciniki_course_offering_prices.available_to, "
            . "ciniki_course_offering_prices.unit_amount, "
            . "ciniki_course_offering_prices.unit_discount_amount, "
            . "ciniki_course_offering_prices.unit_discount_percentage, "
            . "ciniki_course_offering_prices.taxtype_id, "
            . "ciniki_course_offering_prices.webflags "
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
                'fields'=>array('offering_id', 'price_id', 'price_name', 'code', 'course_code', 
                    'offering_id', 'description', 'reg_flags', 'num_seats', 'form_id',
                    'available_to', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id', 'webflags',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['offerings']) || count($rc['offerings']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.52', 'msg'=>'No course found.'));     
        }
        $item = array_pop($rc['offerings']);
//        if( $item['offering_code'] != '' ) {
//            $item['description'] = $item['offering_code'] . ' - ' . $item['description'];
//        } elseif( $item['code'] != '' ) {
//            $item['description'] = $item['code'] . ' - ' . $item['description'];
//        }
        if( isset($item['price_name']) && $item['price_name'] != '' ) {
            $item['description'] .= ' - ' . $item['price_name'];
        }

        //
        // Check the available_to is correct for the specified customer
        //
        if( ($item['available_to']|0xF0) > 0 ) {
            if( ($item['available_to']&$customer['price_flags']) == 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.53', 'msg'=>"I'm sorry, but this course is not available to you."));
            }
        }

        $item['flags'] = 0x28;
        if( ($item['webflags']&0x40) == 0x40 ) {
            $item['flags'] |= 0x40; // Shipped item eg: course kit pickup
        }
    
        //
        // Check the number of seats remaining
        //
        $item['tickets_sold'] = 0;
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
        if( isset($rc['num']['num_seats']) ) {
            $item['tickets_sold'] = $rc['num']['num_seats'];
        }
        $item['units_available'] = $item['num_seats'] - $item['tickets_sold'];
        $item['limited_units'] = 'yes';

        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.54', 'msg'=>'No course specified.'));
}
?>
