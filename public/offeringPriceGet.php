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
// tnid:         The ID of the tenant the course offering is attached to.
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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringPriceGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    if( $args['price_id'] == 0 ) {
        $price = array(
            'id' => 0,
            'offering_id' => 0,
            'name' => '',
            'available_to' => 0,
            'valid_from' => '',
            'valid_to' => '',
            'unit_amount' => '',
            'unit_discount_amount' => '',
            'unit_discount_percentage' => '',
            'taxtype_id' => 0,
            'webflags' => 0,
            );
    } 
    else {
        $strsql = "SELECT prices.id, "
            . "prices.offering_id, "
            . "prices.name, "
            . "prices.available_to, "
            . "prices.valid_from, "
            . "prices.valid_to, "
            . "prices.unit_amount, "
            . "prices.unit_discount_amount, "
            . "prices.unit_discount_percentage, "
            . "prices.taxtype_id, "
            . "prices.webflags "
            . "FROM ciniki_course_offering_prices AS prices "
            . "WHERE prices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
                'fields'=>array('id', 'offering_id', 'name', 'available_to', 'valid_from', 'valid_to', 
                    'unit_amount', 'unit_discount_amount', 'unit_discount_percentage',
                    'taxtype_id', 'webflags'),
                'naprices'=>array('unit_amount', 'unit_discount_amount'),
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
        $price = $rc['prices'][0];
    }

    return array('stat'=>'ok', 'price'=>$price);
}
?>
