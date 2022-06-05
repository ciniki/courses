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
function ciniki_courses_sapos_itemLookup($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == ''
        || !isset($args['object_id']) || $args['object_id'] == '' 
        ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.66', 'msg'=>'No item specified.'));
    }

    //
    // An offering was added to an invoice item, get the details and see if we need to 
    // create a registration for this offering
    //
    if( $args['object'] == 'ciniki.courses.offering_price' ) {
        $strsql = "SELECT ciniki_course_offerings.id, "
            . "ciniki_course_offerings.code AS offering_code, "
            . "ciniki_course_offerings.condensed_date, "
            . "ciniki_course_offerings.form_id, "
            . "ciniki_course_offering_prices.name AS price_name, "
            . "ciniki_course_offering_prices.unit_amount, "
            . "ciniki_course_offering_prices.unit_discount_amount, "
            . "ciniki_course_offering_prices.unit_discount_percentage, "
            . "ciniki_course_offering_prices.taxtype_id, "
            . "ciniki_course_offering_prices.webflags, "
            . "ciniki_courses.code, "
            . "ciniki_courses.name "
            . "FROM ciniki_course_offering_prices "
            . "INNER JOIN ciniki_course_offerings ON ("
                . "ciniki_course_offering_prices.offering_id = ciniki_course_offerings.id "
                . "AND ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_courses ON ("
                . "ciniki_course_offerings.course_id = ciniki_courses.id "
                . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_course_offering_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_course_offering_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['offering']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.67', 'msg'=>'Unable to find course'));
        }
        $offering = $rc['offering'];
        $item = array(
            'status'=>0,
            'object'=>'ciniki.courses.offering',
            'object_id'=>$offering['id'],
            'code'=>$offering['offering_code'],
            'description'=>$offering['name'] . ($offering['price_name'] != '' ? ' - ' . $offering['price_name'] : ''),
            'notes'=>$offering['condensed_date'],
            'price_id'=>$args['object_id'],
            'quantity'=>1,
            'unit_amount'=>$offering['unit_amount'],
            'unit_discount_amount'=>$offering['unit_discount_amount'],
            'unit_discount_percentage'=>$offering['unit_discount_percentage'],
            'shipped_quantity'=>0,
            'taxtype_id'=>$offering['taxtype_id'], 
            'form_id'=>$offering['form_id'], 
            'registrations_available'=>0,
            );
        // Flags: No Quantity, Registration Item
        $item['flags'] = 0x28;
        if( ($offering['webflags']&0x40) == 0x40 ) {
            $item['flags'] |= 0x40; // Shipped item eg: course kit pickup
        }

        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'ok');
}
?>
