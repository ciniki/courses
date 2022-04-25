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
function ciniki_courses_wng_accountOfferingProcess(&$ciniki, $tnid, &$request, $item) {

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
            'content' => "You must be logged in to vote."
            )));
    }

    if( !isset($request['uri_split'][3]) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Invalid request, no offering requested."
            )));
    }
    $course_permalink = $request['uri_split'][2];
    $offering_permalink = $request['uri_split'][3];

    $base_url = '/' . join('/', $request['uri_split']);

    //
    // Load the offering
    //
    $strsql = "SELECT courses.id AS course_id, "
        . "courses.name AS course_name, "
        . "courses.permalink AS course_permalink, "
        . "courses.primary_image_id AS course_image_id, "
//        . "courses.paid_content AS course_paid_content, "
        . "offerings.id AS offering_id, "
        . "offerings.name AS offering_name, "
        . "offerings.permalink AS offering_permalink, "
        . "offerings.primary_image_id AS offering_image_id, "
        . "offerings.paid_content AS offering_paid_content "
        . "FROM ciniki_course_offering_registrations AS registrations "
        . "INNER JOIN ciniki_course_offerings AS offerings ON ("
            . "registrations.offering_id = offerings.id "
            . "AND offerings.status = 10 "  // Active
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND (courses.flags&0x40) = 0x40 "     // Paid content
            . "AND (courses.status = 30 OR courses.status = 70 ) "  // Active or private
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ("
            . "registrations.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.student_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ("
            . "offerings.end_date > NOW() " // Open offering
            . "OR ((courses.flags&0x10) = 0x10) " // Timeless course
            . ") "  
        . "AND courses.permalink = '" . ciniki_core_dbQuote($ciniki, $course_permalink) . "' "
        . "AND offerings.permalink = '" . ciniki_core_dbQuote($ciniki, $offering_permalink) . "' "
        . "ORDER BY courses.name, offerings.name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.198', 'msg'=>'Unable to load offering', 'err'=>$rc['err']));
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.199', 'msg'=>'Unable to find requested offering'));
    }
    $offering = $rc['offering'];
   
    $blocks[] = array(
        'type' => 'contentphoto',
        'class' => 'limit-width',
        'title' => $offering['course_name'] . ' - ' . $offering['offering_name'],
        'content' => $offering['offering_paid_content'] != '' ? $offering['offering_paid_content'] : $offering['course_paid_content'],
        );

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
