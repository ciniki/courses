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
function ciniki_courses_web_categories($ciniki, $settings, $business_id) {

    $strsql = "SELECT DISTINCT ciniki_courses.category "
        . "FROM ciniki_course_offerings "
        . "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
            . "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
        . "LEFT JOIN ciniki_course_offering_classes ON (ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
            . "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
        . "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_course_offerings.status = 10 "
        . "GROUP BY ciniki_course_offerings.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'categories', 'fname'=>'category', 
            'fields'=>array('name'=>'category')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'ok', 'categories'=>array());
    }

    return array('stat'=>'ok', 'categories'=>$rc['categories']);
}
?>
