<?php
//
// Description
// ===========
// This method will return the list of prices for an course offering.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the course offering is attached to.
// offering_id:     The ID of the course offering to get the details for.
// 
// Returns
// -------
//
function ciniki_courses_offeringPriceList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringPriceList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Get the price list for the course offering
    //
    $strsql = "SELECT ciniki_course_offering_prices.id, "
        . "ciniki_course_offering_prices.name, "
        . "ciniki_course_offering_prices.available_to, "
        . "ciniki_course_offering_prices.unit_amount, "
        . "ciniki_course_offering_prices.unit_discount_amount, "
        . "ciniki_course_offering_prices.unit_discount_percentage, "
        . "ciniki_course_offering_prices.taxtype_id, "
        . "ciniki_courses.code AS course_code, "
        . "ciniki_courses.name AS course_name, "
        . "ciniki_course_offerings.name AS offering_name "
//      . "CONCAT_WS(' - ', ciniki_courses.code, ciniki_courses.name, ciniki_course_offerings.name) AS course_name "
        . "FROM ciniki_course_offering_prices "
        . "LEFT JOIN ciniki_course_offerings ON ("
            . "ciniki_course_offering_prices.offering_id = ciniki_course_offerings.id "
            . "AND ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_courses ON ("
            . "ciniki_course_offerings.course_id = ciniki_courses.id "
            . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_course_offering_prices.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "AND ciniki_course_offering_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_course_offering_prices.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
            'fields'=>array('id', 'name', 'available_to', 'course_code', 'course_name', 'offering_name',
                'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['prices']) ) {
        $prices = $rc['prices'];
        foreach($prices as $pid => $price) {
            $price = $price['price'];
            if( $price['course_code'] != '' ) {
                $price['course_name'] = $price['course_code'] . ' - ' . $price['course_name'];
            }
            if( $price['offering_name'] != '' ) {
                $price['course_name'] .= ' - ' . $price['offering_name'];
            }
            $prices[$pid]['price']['unit_amount_display'] = numfmt_format_currency(
                $intl_currency_fmt, $price['unit_amount'], $intl_currency);
            $prices[$pid]['price']['unit_discount_amount_display'] = numfmt_format_currency(
                $intl_currency_fmt, $price['unit_discount_amount'], $intl_currency);
        }
    } else {
        $prices = array();
    }

    return array('stat'=>'ok', 'prices'=>$prices);
}
?>
