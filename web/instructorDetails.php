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
function ciniki_courses_web_instructorDetails($ciniki, $settings, $tnid, $permalink) {

    $strsql = "SELECT instructors.id, "
        . "IFNULL(customers.display_name, '') AS display_name, "
        . "CONCAT_WS(' ', instructors.first, instructors.last) AS name, "
        . "instructors.customer_id, "
        . "instructors.permalink, "
        . "instructors.url, "
        . "instructors.short_bio, "
        . "instructors.full_bio, "
        . "instructors.primary_image_id, "
        . "instructor_images.image_id, "
        . "instructor_images.name AS image_name, "
        . "instructor_images.permalink AS image_permalink, "
        . "instructor_images.description AS image_description, "
        . "instructor_images.url AS image_url, "
        . "UNIX_TIMESTAMP(instructor_images.last_updated) AS image_last_updated "
        . "FROM ciniki_course_instructors AS instructors "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "instructors.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_instructor_images AS instructor_images ON ("
            . "instructors.id = instructor_images.instructor_id "
            . "AND (instructor_images.webflags&0x01) = 0 "
            . ") "
        . "WHERE instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND instructors.permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        // Check the instructor is visible on the website
        . "AND (instructors.webflags&0x01) = 0 "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'instructors', 'fname'=>'id', 
            'fields'=>array('id', 'permalink', 'name', 'customer_id', 'display_name', 'image_id'=>'primary_image_id', 
                'url', 'short_bio', 'full_bio')),
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
    if( $instructor['customer_id'] > 0 ) {
        $instructor['name'] = $instructor['display_name'];
    }

    return array('stat'=>'ok', 'instructor'=>$instructor);
}
?>
