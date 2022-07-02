<?php
//
// Description
// -----------
// This function will process the juror voting for forms.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_wng_accountRegistrationsProcess(&$ciniki, $tnid, &$request, $item) {

    $blocks = array();

    if( !isset($item['ref']) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Request error, please contact us for help.."
            )));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "You must be logged in to view your programs."
            )));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Check if registration form is being requested
    //
    if( isset($request['uri_split'][5]) && $request['uri_split'][4] == 'form' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'wng', 'accountFormProcess');
        return ciniki_courses_wng_accountFormProcess($ciniki, $tnid, $request, $item);
    }

    //
    // Check if offering information is being requested
    //
    if( isset($request['uri_split'][3]) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'wng', 'accountOfferingProcess');
        return ciniki_courses_wng_accountOfferingProcess($ciniki, $tnid, $request, $item);
    }

    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    $base_url = $request['base_url'] . '/' . join('/', $request['uri_split']);

    //
    // Display the list of current and past registrations
    //
    $strsql = "SELECT registrations.id AS id, "
        . "courses.id AS course_id, "
        . "courses.name AS course_name, "
        . "courses.permalink AS course_permalink, "
        . "courses.flags AS course_flags, "
        . "courses.status AS course_status, "
        . "offerings.id AS offering_id, "
        . "offerings.name AS offering_name, "
        . "offerings.permalink AS offering_permalink, "
        . "offerings.status AS offering_status, "
        . "offerings.condensed_date, "
        . "IF(offerings.end_date < '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "', 'yes', 'no') AS ended, "
        . "offerings.form_id, "
        . "registrations.student_id, "
        . "students.display_name AS student_name, "
        . "students.permalink AS student_permalink "
        . "FROM ciniki_course_offering_registrations AS registrations "
        . "INNER JOIN ciniki_course_offerings AS offerings ON ("
            . "registrations.offering_id = offerings.id "
//            . "AND offerings.status = 10 "  // Active
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
//            . "AND (courses.flags&0x40) = 0x40 "     // Paid content
//            . "AND (courses.status = 30 OR courses.status = 70 ) "  // Active or private
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS students ON ("
            . "registrations.student_id = students.id "
            . "AND students.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ("
            . "registrations.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.student_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//        . "AND ("
//            . "offerings.end_date > NOW() " // Open offering
//            . "OR ((courses.flags&0x10) = 0x10) " // Timeless course
//            . ") "  
        . "ORDER BY offerings.start_date, courses.name, offerings.name, students.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'course_id', 'course_name', 'course_permalink', 'course_flags', 'course_status',
                'offering_id', 'offering_name', 'offering_permalink', 'offering_status', 'condensed_date', 'ended',
                'form_id', 'student_id', 'student_name', 'student_permalink',
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.264', 'msg'=>'Unable to load registrations', 'err'=>$rc['err']));
    }
    $registrations = isset($rc['registrations']) ? $rc['registrations'] : array();

    //
    // Create 2 arrays for current and past registrations
    //
    $current_registrations = array();
    $past_registrations = array();
    foreach($registrations AS $registration) {
        
        $registration['name'] = $registration['course_name'] . ' - ' . $registration['offering_name'];
        if( $registration['student_id'] != $request['session']['customer']['id'] ) {
            $registration['name'] .= ' - ' . $registration['student_name'];
        }
        if( ($registration['course_flags']&0x10) == 0x10 ) {
            $registration['condensed_date'] = '';
        }
        $registration['buttons'] = '';
        if( ($registration['course_status'] == 30 || $registration['course_status'] == 70)
            && $registration['offering_status'] == 10
            && (($registration['course_flags']&0x10) == 0x10 || $registration['ended'] == 'no')
            ) {
            if( $registration['form_id'] > 0 ) {
                $registration['buttons'] .= "<a class='button' href='{$base_url}/{$registration['course_permalink']}/{$registration['offering_permalink']}/form/{$registration['student_permalink']}'>Student Information</a>";
            }
            if( ($registration['course_flags']&0x40) == 0x40 ) {
                $registration['buttons'] .= "<a class='button' href='{$base_url}/{$registration['course_permalink']}/{$registration['offering_permalink']}'>View Program</a>";
            }
            $current_registrations[] = $registration;
        } else {
            $past_registrations[] = $registration;
        }
    }


    if( count($registrations) <= 0 ) {
        $blocks[] = array(
            'type' => 'Text',
            'content' => 'You have not registered for any programs.',
            );
    } 
    if( count($current_registrations) > 0 ) {
        $blocks[] = array(
            'type' => 'table',
            'class' => 'limit-width fold-at-50',
            'title' => 'Current Registrations',
            'headers' => 'yes',
            'columns' => array(
                array('label' => 'Program', 'field' => 'name', 'class' => 'alignleft'),
                array('label' => 'Dates', 'field' => 'condensed_date', 'class' => 'alignleft'),
                array('label' => '', 'field' => 'buttons', 'class' => 'alignright buttons'),
                ),
            'rows' => $current_registrations,
            );
    }
    if( count($past_registrations) > 0 ) {
        $blocks[] = array(
            'type' => 'table',
            'class' => 'limit-width fold-at-50',
            'title' => 'Past Registrations',
            'headers' => 'yes',
            'columns' => array(
                array('label' => 'Program', 'field' => 'name', 'class' => 'alignleft'),
                array('label' => 'Dates', 'field' => 'condensed_date', 'class' => 'alignleft'),
                array('label' => '', 'field' => 'buttons', 'class' => 'alignright buttons'),
                ),
            'rows' => $past_registrations,
            );
    }


    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
