<?php
//
// Description
// ===========
// This method will return all the information about an course offering registration.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business the course offering is attached to.
// registration_id:     The ID of the registration to get the details for.
// 
// Returns
// -------
//
function ciniki_courses_offeringRegistrationGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringRegistrationGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    $strsql = "SELECT ciniki_course_offering_registrations.id, "
        . "ciniki_course_offering_registrations.offering_id, "
        . "ciniki_course_offering_registrations.customer_id, "
        . "ciniki_course_offering_registrations.student_id, "
        . "ciniki_course_offering_registrations.invoice_id, "
        . "ciniki_course_offering_registrations.num_seats, "
        . "ciniki_course_offering_registrations.customer_notes, "
        . "ciniki_course_offering_registrations.notes "
        . "FROM ciniki_course_offering_registrations "
        . "WHERE ciniki_course_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_course_offering_registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'registrations', 'fname'=>'id', 'name'=>'registration',
            'fields'=>array('id', 'offering_id', 'customer_id', 'student_id', 'invoice_id', 'num_seats', 
                'customer_notes', 'notes')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registrations']) || !isset($rc['registrations'][0]) ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1517', 'msg'=>'Unable to find registration'));
    }
    $registration = $rc['registrations'][0]['registration'];

    //
    // Get the course info
    //
    $strsql = "SELECT ciniki_courses.id, "
        . "ciniki_courses.name, "
        . "ciniki_courses.code, "
        . "ciniki_courses.primary_image_id, "
        . "ciniki_courses.level, "
        . "ciniki_courses.type, "
        . "ciniki_courses.category, "
        . "ciniki_courses.short_description, "
        . "ciniki_courses.long_description, "
        . "ciniki_course_offerings.condensed_date "
        . "FROM ciniki_course_offerings, ciniki_courses "
        . "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
        . "AND ciniki_course_offerings.course_id = ciniki_courses.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['course']) ) {
        return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'2914', 'msg'=>'Unable to find course'));
    }
    $registration['course_name'] = ($rc['course']['code'] !=''?$rc['course']['code'] . ' - ':'') . $rc['course']['name'];
    $registration['course_dates'] = $rc['course']['condensed_date'];

    //
    // If include customer information
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    if( $registration['customer_id'] > 0 ) {
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], array('customer_id'=>$registration['customer_id'], 
            'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes', 'subscriptions'=>'no'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $registration['customer_details'] = $rc['details'];
    }

    //
    // Get the student details
    //
    if( $registration['customer_id'] != $registration['student_id'] && $registration['student_id'] > 0 ) {
        $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['business_id'], array('customer_id'=>$registration['student_id'], 
            'phones'=>'yes', 'emails'=>'yes', 'addresses'=>'yes', 'subscriptions'=>'no'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $registration['student_details'] = $rc['details'];
    }

/*  //
    // Add invoice information
    //
    if( isset($args['invoice']) && $args['invoice'] == 'yes' && $registration['invoice_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceLoad');
        $rc = ciniki_sapos_invoiceLoad($ciniki, $args['business_id'], $registration['invoice_id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $registration['invoice'] = $rc['invoice'];
    } */

    $rsp = array('stat'=>'ok', 'registration'=>$registration);

    //
    // Get the invoice item details
    //
    if( $rsp['registration']['invoice_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceObjectItem');
        $rc = ciniki_sapos_hooks_invoiceObjectItem($ciniki, $args['business_id'], $rsp['registration']['invoice_id'], 
            'ciniki.courses.offering_registration', $rsp['registration']['id']);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['invoice']) ) {
            $rsp['registration']['invoice_details'][] = array('detail'=>array('label'=>'Invoice', 'value'=>'#' . $rc['invoice']['invoice_number'] . ' - ' . $rc['invoice']['status_text']));
            $rsp['registration']['invoice_details'][] = array('detail'=>array('label'=>'Date', 'value'=>$rc['invoice']['invoice_date']));
            $rsp['registration']['invoice_status'] = $rc['invoice']['status'];
        }
        if( isset($rc['item']) ) {
            $rsp['registration']['item_id'] = $rc['item']['id'];
            $rsp['registration']['unit_amount'] = $rc['item']['unit_amount_display'];
            $rsp['registration']['unit_discount_amount'] = $rc['item']['unit_discount_amount_display'];
            $rsp['registration']['unit_discount_percentage'] = $rc['item']['unit_discount_percentage'];
            $rsp['registration']['taxtype_id'] = $rc['item']['taxtype_id'];
        } else {
            $rsp['registration']['item_id'] = 0;
            $rsp['registration']['unit_amount'] = '';
            $rsp['registration']['unit_discount_amount'] = '';
            $rsp['registration']['unit_discount_percentage'] = '';
            $rsp['registration']['taxtype_id'] = 0;
        }
    }

    return $rsp;
}
?>
