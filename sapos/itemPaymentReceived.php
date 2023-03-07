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
    // Check if item was in cart and marked paid via Ciniki Manager
    //
    if( $args['object'] == 'ciniki.courses.offering' ) {
        //
        // Check the offering exists
        //
        $strsql = "SELECT ciniki_course_offerings.id, "
            . "CONCAT_WS(' - ', ciniki_courses.name, ciniki_course_offerings.name) AS name "
            . "FROM ciniki_course_offerings "
            . "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
                . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['offering']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.58', 'msg'=>'Unable to find course'));
        }
        $offering = $rc['offering'];

        //
        // Create the registration for the customer
        //
        $reg_args = array('offering_id'=>$offering['id'],
            'customer_id'=>$args['customer_id'],
            'student_id'=>$args['student_id'],
            'num_seats'=>(isset($args['quantity'])?$args['quantity']:1),
            'invoice_id'=>$args['invoice_id'],
            'customer_notes'=>'',
            'notes'=>'',
            );
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.courses.offering_registration', $reg_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $reg_id = $rc['id'];

        //
        // Update the sold out flag
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringSoldOutUpdate');
        $rc = ciniki_courses_offeringSoldOutUpdate($ciniki, $tnid, $offering['id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.167', 'msg'=>'Unable to update', 'err'=>$rc['err']));
        }

        //
        // Check for any messages that should be sent after payment
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNotificationSend');
        $rc = ciniki_courses_offeringNotificationSend($ciniki, $tnid, array(
            'customer_id' => $args['customer_id'],
            'student_id' => $args['student_id'],
            'offering_id' => $offering['id'],
            'ntrigger' => 20,
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.172', 'msg'=>'Unable to send notification', 'err'=>$rc['err']));
        }

        //
        // Update notification queue
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
        $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $tnid, $offering['id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.246', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
        }

        return array('stat'=>'ok', 'object'=>'ciniki.courses.offering_registration', 'object_id'=>$reg_id);
    }
    //
    // Send any notifications upon payment received
    //
    elseif( $args['object'] == 'ciniki.courses.offering_registration' ) {
        
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

        //
        // Update notification queue
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
        $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $tnid, $registration['offering_id']);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.245', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
