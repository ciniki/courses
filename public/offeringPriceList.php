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
// business_id:		The ID of the business the course offering is attached to.
// offering_id:		The ID of the course offering to get the details for.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringPriceList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Load the business intl settings
	//
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
	$date_format = ciniki_users_dateFormat($ciniki);

	//
	// Get the price list for the course offering
	//
	$strsql = "SELECT ciniki_course_offering_prices.id, "
		. "ciniki_course_offering_prices.name, "
		. "ciniki_course_offering_prices.unit_amount, "
		. "ciniki_course_offering_prices.unit_discount_amount, "
		. "ciniki_course_offering_prices.unit_discount_percentage, "
		. "CONCAT_WS(' - ', ciniki_courses.name, ciniki_course_offerings.name) AS course_name "
		. "FROM ciniki_course_offering_prices "
		. "LEFT JOIN ciniki_course_offerings ON ("
			. "ciniki_course_offering_prices.offering_id = ciniki_course_offerings.id "
			. "AND ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "LEFT JOIN ciniki_courses ON ("
			. "ciniki_course_offerings.course_id = ciniki_courses.id "
			. "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") "
		. "WHERE ciniki_course_offering_prices.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
		. "AND ciniki_course_offering_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY ciniki_course_offering_prices.name "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
		array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
			'fields'=>array('id', 'course_name', 'name', 
				'unit_amount', 'unit_discount_amount', 'unit_discount_percentage')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['prices']) ) {
		$prices = $rc['prices'];
		foreach($prices as $pid => $price) {
			$prices[$pid]['price']['unit_amount_display'] = numfmt_format_currency(
				$intl_currency_fmt, $price['price']['unit_amount'], $intl_currency);
			$prices[$pid]['price']['unit_discount_amount_display'] = numfmt_format_currency(
				$intl_currency_fmt, $price['price']['unit_discount_amount'], $intl_currency);
		}
	} else {
		$prices = array();
	}

	return array('stat'=>'ok', 'prices'=>$prices);
}
?>
