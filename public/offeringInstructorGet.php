<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business.
// offering_instructor_id:          The ID of the course offering instructor to get.
//
// Returns
// -------
//
function ciniki_courses_offeringInstructorGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'offering_instructor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Instructor'),
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringInstructorGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_course_offering_instructors.id, "
        . "ciniki_course_instructors.id AS instructor_id, "
        . "CONCAT_WS(' ', ciniki_course_instructors.first, ciniki_course_instructors.last) AS name, "
        . "ciniki_course_instructors.first, "
        . "ciniki_course_instructors.last, "
        . "ciniki_course_instructors.permalink, "
        . "ciniki_course_instructors.primary_image_id, "
        . "ciniki_course_instructors.webflags, "
        . "IF((ciniki_course_instructors.webflags&0x01)=1, 'Hidden', 'Visible') AS web_visible, "
        . "ciniki_course_instructors.short_bio, "
        . "ciniki_course_instructors.full_bio, "
        . "ciniki_course_instructors.url "
        . "FROM ciniki_course_offering_instructors "
        . "LEFT JOIN ciniki_course_instructors ON (ciniki_course_offering_instructors.instructor_id = ciniki_course_instructors.id "
            . "AND ciniki_course_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
        . "WHERE ciniki_course_offering_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_course_offering_instructors.id = '" . ciniki_core_dbQuote($ciniki, $args['offering_instructor_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
            'fields'=>array('id', 'instructor_id', 'first', 'last', 'name', 'permalink', 'primary_image_id', 'webflags', 'web_visible', 'short_bio', 'full_bio', 'url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['instructors']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.courses.40', 'msg'=>'Unable to find instructor'));
    }
    $instructor = $rc['instructors'][0]['instructor'];

    if( isset($args['images']) && $args['images'] == 'yes' ) {
        $strsql = "SELECT "
            . "id, "
            . "name, "
            . "webflags, "
            . "image_id, "
            . "description, "
            . "url "
            . "FROM ciniki_course_instructor_images "
            . "WHERE ciniki_course_instructor_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_course_instructor_images.instructor_id = '" . ciniki_core_dbQuote($ciniki, $instructor['instructor_id']) . "' "
            . "ORDER BY ciniki_course_instructor_images.id ASC ";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'images', 'fname'=>'id', 'name'=>'image',
                'fields'=>array('id', 'name', 'webflags', 'image_id', 'description', 'url')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            $instructor['images'] = $rc['images'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            if( isset($instructor['images']) ) {
                foreach($instructor['images'] as $img_id => $img) {
                    if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
                        $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['business_id'], $img['image']['image_id'], 75);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $instructor['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                    }
                }
            }
        } 
    }
    
    return array('stat'=>'ok', 'instructor'=>$instructor);
}
?>
