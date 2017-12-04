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

    $strsql = "SELECT ciniki_course_instructors.id, "
        . "CONCAT_WS(' ', ciniki_course_instructors.first, ciniki_course_instructors.last) AS name, "
        . "'' AS title, "
        . "ciniki_course_instructors.primary_image_id, "
        . "ciniki_course_instructors.permalink, "
        . "ciniki_course_instructors.short_bio, "
//        . "IF(ciniki_course_instructors.full_bio='', 'no', 'yes') AS is_details, "
//        . "IF(ciniki_course_instructors.full_bio='', 'no', 'yes') 
        . "'yes' AS is_details, "
        . "ciniki_course_instructors.url ";
    if( $offering_id > 0 ) {
        $strsql .= "FROM ciniki_course_offering_instructors, ciniki_course_instructors "
            . "WHERE ciniki_course_offering_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_course_offering_instructors.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
            . "AND ciniki_course_offering_instructors.instructor_id = ciniki_course_instructors.id "
            . "AND ciniki_course_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    } else {
        $strsql .= "FROM ciniki_course_instructors "
            . "WHERE ciniki_course_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ";
    }
    $strsql .= "AND (ciniki_course_instructors.webflags&0x01) = 0 "
        . "ORDER BY ciniki_course_instructors.last "
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
