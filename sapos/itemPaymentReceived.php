<?php
//
// Description
// ===========
// This function completes the course registration when the customer has submitted a payment and checkout cart.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_courses_sapos_itemPaymentReceived(&$ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.182', 'msg'=>'No item specified.'));
    }

    if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.183', 'msg'=>'No invoice specified.'));
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');

    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $dt = new DateTime('now', new DateTimeZone($intl_timezone));

    //
    // Send any notifications upon payment received
    //
    if( $args['object'] == 'ciniki.courses.offering_registration' ) {
        
        //
        // Load the registration
        //
        $strsql = "SELECT registrations.offering_id, "
            . "registrations.customer_id, "
            . "registrations.student_id "
            . "FROM ciniki_course_offering_registrations AS registrations "
            . "WHERE registrations.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'registration');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.184', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
        }
        if( !isset($rc['registration']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.185', 'msg'=>'Unable to find requested registration'));
        }
        $registration = $rc['registration'];
       
        //
        // Check for any messages that should be sent after payment
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNotificationSend');
        $rc = ciniki_courses_offeringNotificationSend($ciniki, $tnid, array(
            'customer_id' => $registration['customer_id'],
            'student_id' => $registration['student_id'],
            'offering_id' => $registration['offering_id'],
            'ntrigger' => 20,
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.186', 'msg'=>'Unable to send notification', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
