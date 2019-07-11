<?php
//
// Description
// -----------
// This method will return the list of customers who have registered for an course offering.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get course offering for.
//
// Returns
// -------
//
function ciniki_courses_offeringRegistrationList($ciniki) {
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
    // Check access to tnid as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringRegistrationList');
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
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Build the query string to get the list of registrations
    //
    if( isset($ciniki['tenant']['modules']['ciniki.sapos']) ) {
        //
        // Load the status maps for the text description of each status
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
        $rc = ciniki_sapos_maps($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $status_maps = $rc['maps']['invoice']['payment_status'];
        $status_maps[0] = 'No Invoice';

        $strsql = "SELECT ciniki_course_offering_registrations.id, "
            . "ciniki_course_offering_registrations.customer_id, "
            . "ciniki_course_offering_registrations.student_id, "
            . "IFNULL(c1.display_name, '') AS customer_name, "
            . "IFNULL(c2.display_name, '') AS student_name, "
            . "IFNULL(c2.display_name, IFNULL(c1.display_name, '')) AS sort_name, "
            . "ciniki_course_offering_registrations.num_seats, "
            . "ciniki_course_offering_registrations.invoice_id, "
            . "ciniki_sapos_invoices.payment_status AS invoice_status, "
            . "IFNULL(ciniki_sapos_invoices.payment_status, 0) AS invoice_status_text "
            . "FROM ciniki_course_offering_registrations "
            . "LEFT JOIN ciniki_customers AS c1 ON ("
                . "ciniki_course_offering_registrations.customer_id = c1.id "
                . "AND c1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS c2 ON ("
                . "ciniki_course_offering_registrations.student_id = c2.id "
                . "AND c2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices ON (ciniki_course_offering_registrations.invoice_id = ciniki_sapos_invoices.id "
                . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_course_offering_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "ORDER BY sort_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
                'fields'=>array('id', 'customer_id', 'customer_name'=>'sort_name', 'student_name', 'num_seats', 
                    'invoice_id', 'invoice_status', 'invoice_status_text'),
                'maps'=>array('invoice_status_text'=>$status_maps)),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.46', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
        }
        if( !isset($rc['registrations']) ) {
            $registrations = array();
        } else {
            $registrations = $rc['registrations'];
        }
    } else {
        $strsql = "SELECT ciniki_course_offering_registrations.id, "
            . "ciniki_course_offering_registrations.customer_id, "
            . "ciniki_course_offering_registrations.student_id, "
            . "IFNULL(c1.display_name, '') AS customer_name, "
            . "IFNULL(c2.display_name, '') AS student_name, "
            . "IFNULL(c2.display_name, IFNULL(c1.display_name, '')) AS sort_name, "
            . "ciniki_course_offering_registrations.num_seats, "
            . "ciniki_course_offering_registrations.invoice_id "
            . "FROM ciniki_course_offering_registrations "
            . "LEFT JOIN ciniki_customers AS c1 ON ("
                . "ciniki_course_offering_registrations.customer_id = c1.id "
                . "AND c1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS c2 ON ("
                . "ciniki_course_offering_registrations.student_id = c2.id "
                . "AND c2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_course_offering_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "ORDER BY sort_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
                'fields'=>array('id', 'customer_id', 'customer_name'=>'sort_name', 'num_seats', 'invoice_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.47', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
        }
        if( !isset($rc['registrations']) ) {
            $registrations = array();
        } else {
            $registrations = $rc['registrations'];
        }
    }

    //
    // Get the price list for the event
    //
    $strsql = "SELECT id, name, unit_amount "
        . "FROM ciniki_course_offering_prices "
        . "WHERE ciniki_course_offering_prices.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "AND ciniki_course_offering_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY ciniki_course_offering_prices.name "
        . "";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.offerings', array(
        array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
            'fields'=>array('id', 'name', 'unit_amount')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['prices']) ) {
        $prices = $rc['prices'];
        foreach($prices as $pid => $price) {
            $prices[$pid]['price']['unit_amount_display'] = numfmt_format_currency(
                $intl_currency_fmt, $price['price']['unit_amount'], $intl_currency);
        }
    } else {
        $prices = array();
    }

    return array('stat'=>'ok', 'registrations'=>$registrations, 'prices'=>$prices);
}
?>
