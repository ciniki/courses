<?php
//
// Description
// -----------
// This function will check for paid content courses that should be available in the account menu.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_wng_accountMenuItems($ciniki, $tnid, $request, $args) {

    $items = array();

    $settings = isset($request['site']['settings']) ? $request['site']['settings'] : array();
    $base_url = isset($args['base_url']) ? $args['base_url'] : '';

    //
    // Check if the customer is or has been registered for any courses
    //
    $strsql = "SELECT COUNT(*) AS registrations "
        . "FROM ciniki_course_offering_registrations "
        . "WHERE ("
            . "customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR student_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
    $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.courses', 'num');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.263', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
    }
    if( isset($rc['num']) && $rc['num'] > 0 ) {
        $items[] = array(
            'title' => 'Program Registrations', 
            'priority' => 550, 
            'selected' => 'no',
            'ref' => 'ciniki.courses.registrations',
            'url' => $base_url . '/programs',
            );
    }
/*
*** This might be useful if people prefer dropdown of only registered courses ***
    //
    // Get the list of open/timeless course offerings the customer has paid for and there is paid content
    //
    $strsql = "SELECT courses.id AS course_id, "
        . "courses.name AS course_name, "
        . "courses.permalink AS course_permalink, "
        . "offerings.id AS offering_id, "
        . "offerings.name AS offering_name, "
        . "offerings.permalink AS offering_permalink "
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
        . "ORDER BY courses.name, offerings.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'offering_id', 
            'fields'=>array('course_id', 'course_name', 'course_permalink',
                'offering_id', 'offering_name', 'offering_permalink'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.194', 'msg'=>'Unable to load programs', 'err'=>$rc['err']));
    }
    $offerings = isset($rc['offerings']) ? $rc['offerings'] : array();
    foreach($offerings as $oid => $offering) {
        $offerings[$oid]['title'] = $offering['course_name'] . ' - ' . $offering['offering_name'];
        $offerings[$oid]['ref'] = 'ciniki.courses.offering';
        $offerings[$oid]['url'] = $base_url . '/courses/' . $offering['course_permalink'] . '/' . $offering['offering_permalink'];
    }

    if( count($offerings) > 0 ) {
        $items[] = array(
            'title' => 'Programs', 
            'priority' => 950, 
            'selected' => 'no',
            'items' => $offerings,
            );
    }

*/
    return array('stat'=>'ok', 'items'=>$items);
}
?>
