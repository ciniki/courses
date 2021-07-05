<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant.
// instructor_id:           The ID of the instructor to get.
//
// Returns
// -------
//
function ciniki_courses_instructorGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'instructor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Instructor'),
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.instructorGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');

    if( !isset($args['instructor_id']) || $args['instructor_id'] == 0 ) {
        $instructor = array(
            'id' => 0,
            'first' => '',
            'last' => '',
            'primary_image_id' => 0,
            'status' => 10,
            'webflags' => 0,
            'url' => '',
            'short_bio' => '',
            'full_bio' => '',
            'offerings' => array(),
            );
    }

    //
    // Get the main information
    //
    else {
        $strsql = "SELECT ciniki_course_instructors.id, "
            . "CONCAT_WS(' ', ciniki_course_instructors.first, ciniki_course_instructors.last) AS name, "
            . "ciniki_course_instructors.first, "
            . "ciniki_course_instructors.last, "
            . "ciniki_course_instructors.status, "
            . "ciniki_course_instructors.permalink, "
            . "ciniki_course_instructors.primary_image_id, "
            . "ciniki_course_instructors.webflags, "
            . "IF((ciniki_course_instructors.webflags&0x01)=1, 'Hidden', 'Visible') AS web_visible, "
            . "ciniki_course_instructors.short_bio, "
            . "ciniki_course_instructors.full_bio, "
            . "ciniki_course_instructors.url "
            . "FROM ciniki_course_instructors "
            . "WHERE ciniki_course_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_instructors.id = '" . ciniki_core_dbQuote($ciniki, $args['instructor_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
                'fields'=>array('id', 'first', 'last', 'status', 'name', 'permalink', 'primary_image_id', 'webflags', 'web_visible', 'short_bio', 'full_bio', 'url')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['instructors']) ) {
            return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.courses.20', 'msg'=>'Unable to find instructor'));
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
                . "WHERE ciniki_course_instructor_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_course_instructor_images.instructor_id = '" . ciniki_core_dbQuote($ciniki, $args['instructor_id']) . "' "
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
                            $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image']['image_id'], 75);
                            if( $rc['stat'] != 'ok' ) {
                                return $rc;
                            }
                            $instructor['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                        }
                    }
                }
            } 
        }

        //
        // Get the list of offerings for this instructor
        //
        $strsql = "SELECT courses.id AS course_id, "
            . "courses.code AS course_code, "
            . "courses.name AS course_name, "
            . "offerings.id AS offering_id, "
            . "offerings.code AS offering_code, "
            . "offerings.name AS offering_name, "
            . "offerings.start_date, "
            . "offerings.end_date, "
            . "offerings.num_seats, "
            . "COUNT(DISTINCT registrations.id) AS num_registrations "
            . "FROM ciniki_course_offering_instructors AS instructors "
            . "LEFT JOIN ciniki_course_offerings AS offerings ON ("
                . "instructors.offering_id = offerings.id "
                . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_courses AS courses ON ("
                . "offerings.course_id = courses.id "
                . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_course_offering_registrations AS registrations ON ("
                . "offerings.id = registrations.offering_id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE instructors.instructor_id = '" . ciniki_core_dbQuote($ciniki, $instructor['id']) . "' "
            . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY offerings.id "
            . "ORDER BY offerings.end_date DESC, courses.code, courses.name, offerings.code, offerings.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'offerings', 'fname'=>'offering_id', 
                'fields'=>array('course_id', 'course_code', 'course_name', 
                    'offering_id', 'offering_code', 'offering_name',
                    'start_date', 'end_date', 'num_seats', 'num_registrations',
                    ),
                'dtformat'=>array(
                    'start_date'=>$date_format, 
                    'end_date'=>$date_format,
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.156', 'msg'=>'Unable to load offerings', 'err'=>$rc['err']));
        }
        $instructor['offerings'] = isset($rc['offerings']) ? $rc['offerings'] : array();
    }
    
    return array('stat'=>'ok', 'instructor'=>$instructor);
}
?>
