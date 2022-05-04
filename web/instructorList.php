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
function ciniki_courses_web_instructorList($ciniki, $settings, $tnid, $offering_id, $format='') {

    $strsql = "SELECT instructors.id, "
        . "CONCAT_WS(' ', IFNULL(customers.first, instructors.first), IFNULL(customers.last, instructors.last)) AS name, "
        . "'' AS title, "
        . "instructors.primary_image_id, "
        . "instructors.permalink, "
        . "instructors.short_bio, "
//        . "IF(instructors.full_bio='', 'no', 'yes') AS is_details, "
//        . "IF(instructors.full_bio='', 'no', 'yes') 
        . "'yes' AS is_details, "
        . "instructors.url ";
    if( $offering_id > 0 ) {
        $strsql .= "FROM ciniki_course_offering_instructors AS oinstructors "
            . "INNER JOIN ciniki_course_instructors AS instructors ON ("
                . "oinstructors.instructor_id = instructors.id "
                . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "instructors.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE oinstructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND oinstructors.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
            . "";
    } else {
        $strsql .= "FROM ciniki_course_instructors AS instructors "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "instructors.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    }
    $strsql .= "AND (instructors.webflags&0x01) = 0 "
        . "AND instructors.status < 90 "
        . "ORDER BY customers.last, instructors.last "
        . "";
    if( $format == 'cilist' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'instructors', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'permalink', 'image_id'=>'primary_image_id', 'short_bio', 'url', 'is_details')),
            array('container'=>'list', 'fname'=>'id', 
                'fields'=>array('id', 'title'=>'name', 'permalink', 'image_id'=>'primary_image_id', 'synopsis'=>'short_bio', 'url', 'is_details')),
            ));
    } else {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'instructors', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'permalink', 'image_id'=>'primary_image_id', 'short_bio', 'url', 'is_details')),
            ));
    }
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['instructors']) ) {
        return array('stat'=>'ok', 'instructors'=>array());
    }

    return array('stat'=>'ok', 'instructors'=>$rc['instructors']);
}
?>
