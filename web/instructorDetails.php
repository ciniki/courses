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
function ciniki_courses_web_instructorDetails($ciniki, $settings, $business_id, $permalink) {

    $strsql = "SELECT ciniki_course_instructors.id, "
        . "CONCAT_WS(' ', ciniki_course_instructors.first, ciniki_course_instructors.last) AS name, "
        . "ciniki_course_instructors.permalink, "
        . "ciniki_course_instructors.url, "
        . "ciniki_course_instructors.synopsis, "
        . "ciniki_course_instructors.full_bio, "
        . "ciniki_course_instructors.primary_image_id, "
        . "ciniki_course_instructor_images.image_id, "
        . "ciniki_course_instructor_images.name AS image_name, "
        . "ciniki_course_instructor_images.permalink AS image_permalink, "
        . "ciniki_course_instructor_images.description AS image_description, "
        . "ciniki_course_instructor_images.url AS image_url, "
        . "UNIX_TIMESTAMP(ciniki_course_instructor_images.last_updated) AS image_last_updated "
        . "FROM ciniki_course_instructors "
        . "LEFT JOIN ciniki_course_instructor_images ON ("
            . "ciniki_course_instructors.id = ciniki_course_instructor_images.instructor_id "
            . "AND (ciniki_course_instructor_images.webflags&0x01) = 0 "
            . ") "
        . "WHERE ciniki_course_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_course_instructors.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        // Check the instructor is visible on the website
        . "AND (ciniki_course_instructors.webflags&0x01) = 0 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'instructors', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'name', 'image_id'=>'primary_image_id', 
                'url', 'synopsis', 'full_bio')),
        array('container'=>'images', 'fname'=>'image_id', 
            'fields'=>array('image_id', 'title'=>'image_name', 'permalink'=>'image_permalink',
                'description'=>'image_description', 'url'=>'image_url',
                'last_updated'=>'image_last_updated')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['instructors']) || count($rc['instructors']) < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.73', 'msg'=>"I'm sorry, but we are unable to find the instructor you requested."));
    }
    $instructor = array_pop($rc['instructors']);

    return array('stat'=>'ok', 'instructor'=>$instructor);
}
?>
