<?php
//
// Description
// -----------
// This function will send a notification for a offering to a customer.
//
// When sending a payment received notification, pass the argument ntrigger,
// or to send a nqueue notification from cron, pass notification_id.
//
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_offeringNotificationSend(&$ciniki, $tnid, $args) {

    //
    // Make sure required arguments are passed
    //
    if( !isset($args['customer_id']) || $args['customer_id'] == '' || $args['customer_id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.173', 'msg'=>'No customer specified'));
    }
    if( !isset($args['offering_id']) || $args['offering_id'] == '' || $args['offering_id'] < 1 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.174', 'msg'=>'No offering specified'));
    }

    //
    // Check for any notifications for the offering and trigger
    //
    $strsql = "SELECT notifications.id, "
        . "notifications.ntype, "
        . "notifications.status, "
        . "notifications.subject, "
        . "notifications.content "
        . "FROM ciniki_course_offering_notifications AS notifications "
        . "WHERE notifications.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "";
    //
    // Check if payment requested ntrigger is set
    //
    if( isset($args['ntrigger']) && $args['ntrigger'] != '' && $args['ntrigger'] > 0 ) {
        $strsql .= "AND notifications.ntrigger = '" . ciniki_core_dbQuote($ciniki, $args['ntrigger']) . "' ";
    } 
    //
    // Check if notification_id is specifed, from cron nqueue processing
    //
    elseif( isset($args['notification_id']) && $args['notification_id'] > 0 ) {
        $strsql .= "AND notifications.id = '" . ciniki_core_dbQuote($ciniki, $args['notification_id']) . "' ";
    }
    else {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.187', 'msg'=>'No trigger or notification specified'));
    }
    $strsql .= "AND notifications.ntype = 10 "
        . "AND notifications.status > 0 "
        . "AND notifications.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'notifications', 'fname'=>'id', 'fields'=>array('id', 'ntype', 'status', 'subject', 'content')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.181', 'msg'=>'Unable to load notifications', 'err'=>$rc['err']));
    }
    $notifications = isset($rc['notifications']) ? $rc['notifications'] : array();

    //
    // Load the customer details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerEmails');
    $rc = ciniki_customers_hooks_customerEmails($ciniki, $tnid, array('customer_id' => $args['customer_id']));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.188', 'msg'=>'Unable to load customer details', 'err'=>$rc['err']));
    }
    $customer = isset($rc['customer']) ? $rc['customer'] : array();
    $customer_name = $customer['display_name'];
    $firstname = isset($customer['first']) && $customer['first'] != '' ? $customer['first'] : '';
    $lastname = isset($customer['last']) && $customer['last'] != '' ? $customer['last'] : '';

    $emails = array();
    if( isset($customer['emails']) ) {
        foreach($customer['emails'] as $email) {
            $emails[] = $email['address'];
        }
    }

    //
    // Check if student_id is set
    //
    if( isset($args['student_id']) && $args['student_id'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerEmails');
        $rc = ciniki_customers_hooks_customerEmails($ciniki, $tnid, array('customer_id' => $args['customer_id']));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.193', 'msg'=>'Unable to load customer details', 'err'=>$rc['err']));
        }
        $student = isset($rc['customer']) ? $rc['customer'] : array();
        if( isset($student['display_name']) && $student['display_name'] != '' ) {
            $customer_name = $student['display_name'];
        }
        if( isset($student['first']) && $student['first'] != '' ) {
            $firstname = $student['first'];
        }
        if( isset($student['last']) && $student['last'] != '' ) {
            $lastname = $student['last'];
        }
        if( isset($student['emails']) ) {
            foreach($student['emails'] as $email) {
                $emails[] = $email['address'];
            }
        }
    }

    //
    // Only send to unique addresses: this will remove duplicates when children have parent email address
    //
    sort($emails);
    $emails = array_unique($emails);

    //
    // Send the notifications
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'addMessage');
    foreach($notifications as $notification) {
        
        $notification['subject'] = str_replace('{_firstname_}', $firstname, $notification['subject']);
        $notification['content'] = str_replace('{_firstname_}', $firstname, $notification['content']);
        $notification['subject'] = str_replace('{_lastname_}', $lastname, $notification['subject']);
        $notification['content'] = str_replace('{_lastname_}', $lastname, $notification['content']);
        foreach($emails as $email) {
            $rc = ciniki_mail_hooks_addMessage($ciniki, $tnid, array(
                'customer_id' => $args['customer_id'],
                'customer_name' => $customer_name,
                'customer_email' => $email,
                'object' => 'ciniki.courses.offering',
                'object_id' => $args['offering_id'],
                'status' => ($notification['status'] == 20 ? 10 : 7),
                'subject' => $notification['subject'],
                'html_content' => $notification['content'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.189', 'msg'=>'Unable to send email', 'err'=>$rc['err']));
            }
            $ciniki['emailqueue'][] = array(
                'mail_id' => $rc['id'],
                'tnid' => $tnid,
                );
        }
    }

    return array('stat'=>'ok');
}
?>
