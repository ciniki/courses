<?php
//
// Description
// ===========
// This method will return all the information about an course offering price.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the course offering is attached to.
// price_id:        The ID of the price to get the details for.
// 
// Returns
// -------
//
function ciniki_courses_offeringPriceGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'price_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Price'), 
        'customer'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer'),
        'invoice'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Invoice'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringPriceGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    $strsql = "SELECT ciniki_course_offering_prices.id, "
        . "ciniki_course_offering_prices.offering_id, "
        . "ciniki_course_offering_prices.name, "
        . "ciniki_course_offering_prices.available_to, "
        . "ciniki_course_offering_prices.valid_from, "
        . "ciniki_course_offering_prices.valid_to, "
        . "ciniki_course_offering_prices.unit_amount, "
        . "ciniki_course_offering_prices.unit_discount_amount, "
        . "ciniki_course_offering_prices.unit_discount_percentage, "
        . "ciniki_course_offering_prices.taxtype_id, "
        . "ciniki_course_offering_prices.webflags "
        . "FROM ciniki_course_offering_prices "
        . "WHERE ciniki_course_offering_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_course_offering_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
            'fields'=>array('id', 'offering_id', 'name', 'available_to', 'valid_from', 'valid_to', 
                'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
                'taxtype_id', 'webflags'),
            'utctotz'=>array('valid_from'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'valid_to'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['prices']) || !isset($rc['prices'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.42', 'msg'=>'Unable to find price'));
    }
    $price = $rc['prices'][0]['price'];

    $price['unit_amount'] = numfmt_format_currency($intl_currency_fmt,
        $price['unit_amount'], $intl_currency);
    $price['unit_discount_amount'] = numfmt_format_currency($intl_currency_fmt,
        $price['unit_discount_amount'], $intl_currency);

    return array('stat'=>'ok', 'price'=>$price);
}
?>
