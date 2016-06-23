<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_web_courseTypes($ciniki, $settings, $business_id) {

    $strsql = "SELECT DISTINCT ciniki_courses.type "
        . "FROM ciniki_course_offerings "
        . "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
            . "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
        . "LEFT JOIN ciniki_course_offering_classes ON (ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
            . "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
        . "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_course_offerings.status = 10 "
        . "AND (ciniki_course_offerings.webflags&0x01) = 0 "
        . "GROUP BY ciniki_course_offerings.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'types', 'fname'=>'type', 
            'fields'=>array('name'=>'type')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['types']) ) {
        return array('stat'=>'ok', 'types'=>array());
    }

    return array('stat'=>'ok', 'types'=>$rc['types']);
}
?>
