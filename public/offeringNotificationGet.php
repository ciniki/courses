<?php
//
// Description
// ===========
// This method will return all the information about an offering notification.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the offering notification is attached to.
// notification_id:          The ID of the offering notification to get the details for.
//
// Returns
// -------
//
function ciniki_courses_offeringNotificationGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'notification_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering Notification'),
        'offering_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Offering'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringNotificationGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Offering Notification
    //
    if( $args['notification_id'] == 0 ) {
        $notification = array('id'=>0,
            'offering_id'=>'',
            'name'=>'',
            'ntrigger'=>'',
            'ntype'=>'10',
            'flags'=>'0',
            'offset_days'=>'0',
            'status'=>'0',
            'time_of_day'=>'',
            'subject'=>'',
            'content'=>'',
            'form_label'=>'',
            'form_id'=>0,
        );
        if( isset($args['offering_id']) ) {
            $strsql = "SELECT offerings.id, "
                . "offerings.course_id, "
                . "offerings.name, "
                . "offerings.code, "
                . "offerings.condensed_date, "
                . "courses.name AS course_name, "
                . "courses.code AS course_code "
                . "FROM ciniki_course_offerings AS offerings "
                . "INNER JOIN ciniki_courses AS courses ON ("
                    . "offerings.course_id = courses.id "
                    . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'item');
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.269', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
            }
            if( isset($rc['item']) ) {
                $notification['subject'] = 'Re: ' . $rc['item']['course_name'] . ' - ' . $rc['item']['name'] . ' (' . $rc['item']['condensed_date'] . ')';
            }
        }
    }

    //
    // Get the details for an existing Offering Notification
    //
    else {
        $strsql = "SELECT ciniki_course_offering_notifications.id, "
            . "ciniki_course_offering_notifications.offering_id, "
            . "ciniki_course_offering_notifications.name, "
            . "ciniki_course_offering_notifications.ntrigger, "
            . "ciniki_course_offering_notifications.ntype, "
            . "ciniki_course_offering_notifications.flags, "
            . "ciniki_course_offering_notifications.offset_days, "
            . "ciniki_course_offering_notifications.status, "
            . "TIME_FORMAT(ciniki_course_offering_notifications.time_of_day, '%l:%i %p') AS time_of_day, "
            . "ciniki_course_offering_notifications.subject, "
            . "ciniki_course_offering_notifications.content, "
            . "ciniki_course_offering_notifications.form_label, "
            . "ciniki_course_offering_notifications.form_id "
            . "FROM ciniki_course_offering_notifications "
            . "WHERE ciniki_course_offering_notifications.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_offering_notifications.id = '" . ciniki_core_dbQuote($ciniki, $args['notification_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'notifications', 'fname'=>'id', 
                'fields'=>array('offering_id', 'name', 'ntrigger', 'ntype', 'flags', 'offset_days', 'status', 'time_of_day', 
                    'subject', 'content', 'form_label', 'form_id'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.179', 'msg'=>'Offering Notification not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['notifications'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.180', 'msg'=>'Unable to find Offering Notification'));
        }
        $notification = $rc['notifications'][0];
    }

    return array('stat'=>'ok', 'notification'=>$notification);
}
?>
