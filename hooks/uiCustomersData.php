<?php
//
// Description
// -----------
// This function will return the data for customer(s) to be displayed in the IFB display panel.
// The request might be for 1 individual, or multiple customer ids for a family.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get courses for.
//
// Returns
// -------
//
function ciniki_courses_hooks_uiCustomersData($ciniki, $tnid, $args) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    //
    // Default response
    //
    $rsp = array('stat'=>'ok', 'tabs'=>array());

    //
    // Get the list of registrations for the with latest first
    //
    $sections['ciniki.courses.registrations'] = array(
        'label' => 'Course Registrations',
        'type' => 'simplegrid', 
        'num_cols' => 2,
        'headerValues' => array('Name', 'Course'),
        'cellClasses' => array('', 'multiline', ''),
        'noData' => 'No registrations',
//            'editApp' => array('app'=>'ciniki.courses.sapos', 'args'=>array('registration_id'=>'d.id;', 'source'=>'\'\'')),
        'cellValues' => array(
            '0' => "d.student_name",
            '1' => "'<span class=\"maintext\">' + d.offering_code + ' - ' + d.course_name + ' - ' + d.offering_name + '</span><span class=\"subtext\">' + d.condensed_date + '</span>'",
            ),
        'data' => array(),
        );
        $strsql = "SELECT regs.id, "
            . "regs.customer_id, "
            . "regs.student_id, "
            . "IFNULL(customers.display_name, '') AS display_name, "
            . "IFNULL(students.display_name, '') AS student_name, "
            . "courses.name AS course_name, "
            . "courses.code AS course_code, "
            . "offerings.name AS offering_name, "
            . "offerings.code AS offering_code, "
            . "offerings.condensed_date "
            . "FROM ciniki_course_offering_registrations AS regs "
            . "INNER JOIN ciniki_course_offerings AS offerings ON ( "
                . "regs.offering_id = offerings.id "
                . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "INNER JOIN ciniki_courses AS courses ON ("
                . "offerings.course_id = courses.id "
                . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "regs.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS students ON ("
                . "regs.student_id = students.id "
                . "AND students.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE regs.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        if( isset($args['customer_id']) ) {
            $strsql .= "AND ("
                . "regs.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . "OR regs.student_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
                . ") ";
        } elseif( isset($args['customer_ids']) && count($args['customer_ids']) > 0 ) {
            $strsql .= "AND ("
                . "regs.customer_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") "
                . "OR regs.student_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $args['customer_ids']) . ") "
                . ") ";
        } else {
            return array('stat'=>'ok');
        }
    $strsql .= "ORDER BY customers.display_name, students.display_name, offerings.date_added DESC, courses.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.customers', array(
        array('container'=>'registrations', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'student_id', 'display_name', 'student_name', 
                'course_name', 'offering_code', 'offering_name', 'condensed_date'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $sections['ciniki.courses.registrations']['data'] = isset($rc['registrations']) ? $rc['registrations'] : array();
    $rsp['tabs'][] = array(
        'id' => 'ciniki.courses.registrations',
        'label' => 'Courses',
        'sections' => $sections,
        );
    $sections = array();

    return $rsp;
}
?>
