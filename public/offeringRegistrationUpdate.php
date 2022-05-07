<?php
//
// Description
// ===========
// This method will update an course offering registration in the database.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the course offering is attached to.
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_courses_offeringRegistrationUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
//        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
//      'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'),
////        'num_seats'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number of Seats'),
  //      'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Notes'), 
   //     'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'registration_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Registration'), 
        'offering_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Offering'),
        'item_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Invoice Item'),
        'student_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Student'),
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        'customer_notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Customer Notes'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'test_results'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Test Results'), 
        'unit_amount'=>array('required'=>'no', 'blank'=>'no', 'type'=>'currency', 'name'=>'Unit Amount'),
        'unit_discount_amount'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'currency', 'name'=>'Discount Amount'),
        'unit_discount_percentage'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Discount Percentage'),
        'taxtype_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Tax Type'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringRegistrationUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Get the existing details for the registration
    //
    $strsql = "SELECT id, offering_id, student_id, invoice_id "
        . "FROM ciniki_course_offering_registrations "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['registration_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.48', 'msg'=>'Registration does not exist'));
    }
    $registration = $rc['registration'];

    //
    // Load the offering details to get condensed_date
    //
    $strsql = "SELECT id, condensed_date "
        . "FROM ciniki_course_offerings "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $registration['offering_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.118', 'msg'=>'Unable to load offering', 'err'=>$rc['err']));
    }
    if( !isset($rc['item']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.119', 'msg'=>'Unable to find requested offering'));
    }
    $offering = $rc['item'];

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the registration in the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.courses.offering_registration', 
        $args['registration_id'], $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return $rc;
    }

    //
    // Check if there is an invoice for this course offering, and update the invoice.
    //
/*  if( isset($args['num_seats']) && $args['num_seats'] != $registration['num_seats'] && $registration['invoice_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'invoiceUpdateItem');
        $rc = ciniki_sapos_invoiceUpdateItem($ciniki, $args['tnid'], $registration['invoice_id'],
            array('object'=>'ciniki.courses.offering_registration',
                'object_id'=>$registration['id'],
                'quantity'=>$args['num_seats'],
                ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
            return $rc;
        }
    } */

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'courses');

    //
    // Update the notification queue
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
    $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $args['tnid'], $offering['id']);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.261', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
    }

    //
    // Update the invoice item
    //
    if( isset($args['item_id']) && $args['item_id'] > 0 ) {
        $item_args = array('item_id'=>$args['item_id']);
        if( isset($args['student_id']) && $args['student_id'] != $registration['student_id'] ) {
            if( $args['student_id'] == 0 ) {
                $item_args['notes'] = '';
            } else {
                ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
                $rc = ciniki_customers_hooks_customerDetails($ciniki, $args['tnid'], array('customer_id'=>$args['student_id']));
                if( $rc['stat'] == 'ok' && isset($rc['customer']['display_name']) ) {
                    $item_args['notes'] = $rc['customer']['display_name'];
                    if( isset($offering['condensed_date']) && $offering['condensed_date'] != '' ) {
                        $item_args['notes'] .= ($item_args['notes'] != '' ? ' - ' : '') . $offering['condensed_date'];
                    }
                }
            }
        }

        foreach(array('unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id') as $aname) {
            if( isset($args[$aname]) ) {
                $item_args[$aname] = $args[$aname];
            }
        }
        if( isset($args['offering_id']) && $args['offering_id'] > 0 && $args['offering_id'] != $registration['offering_id'] && isset($course) ) {
            $item_args['description'] = $course['name'];
            $item_args['unit_amount'] = $course['price'];
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'hooks', 'invoiceItemUpdate');
        $rc = ciniki_sapos_hooks_invoiceItemUpdate($ciniki, $args['tnid'], $item_args);
        return $rc;
    }

    return array('stat'=>'ok');
}
?>
