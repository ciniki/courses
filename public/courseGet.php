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
// course_id:           The ID of the course to get.
//
// Returns
// -------
//
function ciniki_courses_courseGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.courseGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    if( $args['course_id'] == 0 ) {
        $course = array(
            'id' => 0,
            'name' => '',
            'code' => '',
            'status' => 10,
            'primary_image_id' => 0,
            'level' => '',
            'type' => '',
            'category' => '',
            'medium' => '',
            'ages' => '',
            'flags' => 0,
            'short_description' => '',
            'long_description' => '',
            'files' => array(),
            'images' => array(),
            );
    }
    //
    // Get the main information
    //
    else {
        $strsql = "SELECT ciniki_courses.id, "
            . "ciniki_courses.name, "
            . "ciniki_courses.code, "
            . "ciniki_courses.status, "
            . "ciniki_courses.primary_image_id, "
            . "ciniki_courses.level, "
            . "ciniki_courses.type, "
            . "ciniki_courses.category, "
            . "ciniki_courses.medium, "
            . "ciniki_courses.ages, "
            . "ciniki_courses.flags, "
            . "ciniki_courses.short_description, "
            . "ciniki_courses.long_description "
            . "FROM ciniki_courses "
            . "WHERE ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_courses.id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'courses', 'fname'=>'id', 'name'=>'course',
                'fields'=>array('id', 'name', 'code', 'status', 'primary_image_id', 
                    'level', 'type', 'category', 'medium', 'ages',
                    'flags', 'short_description', 'long_description',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['courses']) ) {
            return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.courses.8', 'msg'=>'Unable to find course'));
        }
        $course = $rc['courses'][0]['course'];

        //
        // Get the list of files
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x08) ) {
            $strsql = "SELECT id, name "
                . "FROM ciniki_course_files "
                . "WHERE course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'files', 'fname'=>'id', 'fields'=>array('id', 'name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.143', 'msg'=>'Unable to load files', 'err'=>$rc['err']));
            }
            $course['files'] = isset($rc['files']) ? $rc['files'] : array();
        }

        //
        // Get the list of images
        //
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x0200) ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            $strsql = "SELECT images.id, "
                . "images.image_id, "
                . "images.name, "
                . "images.description "
                . "FROM ciniki_course_images AS images "
                . "WHERE images.course_id = '" . ciniki_core_dbQuote($ciniki, $course['id']) . "' "
                . "AND images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY images.date_added, images.name "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.blog', array(
                array('container'=>'images', 'fname'=>'id', 'name'=>'image',
                    'fields'=>array('id', 'image_id', 'name', 'description')),
                ));
            if( $rc['stat'] != 'ok' ) { 
                return $rc;
            }
            $course['images'] = isset($rc['images']) ? $rc['images'] : array();
            foreach($course['images'] as $img_id => $img) {
                if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                    $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image_id'], 75);
                    if( $rc['stat'] != 'ok' ) {
                        return $rc;
                    }
                    $course['images'][$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                }
            }
        }

        //
        // Get the list of offerings for this course
        //
        $strsql = "SELECT offerings.id, "
            . "courses.id AS course_id, "
            . "courses.code AS course_code, "
            . "courses.name AS course_name, "
            . "offerings.code AS offering_code, "
            . "offerings.name AS offering_name, "
            . "offerings.start_date, "
            . "offerings.end_date, "
            . "offerings.num_seats, "
            . "COUNT(DISTINCT registrations.id) AS num_registrations "
            . "FROM ciniki_course_offerings AS offerings "
            . "LEFT JOIN ciniki_courses AS courses ON ("
                . "offerings.course_id = courses.id "
                . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_course_offering_registrations AS registrations ON ("
                . "offerings.id = registrations.offering_id "
                . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE offerings.course_id = '" . ciniki_core_dbQuote($ciniki, $course['id']) . "' "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY offerings.id "
            . "ORDER BY offerings.status, offerings.start_date, courses.code, courses.name, offerings.code, offerings.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'offerings', 'fname'=>'id', 
                'fields'=>array('id', 'course_id', 'course_code', 'course_name', 
                    'offering_id'=>'id', 'offering_code', 'offering_name',
                    'start_date', 'end_date', 'num_seats', 'num_registrations',
                    ),
                'dtformat'=>array(
                    'start_date'=>$date_format, 
                    'end_date'=>$date_format,
                    ),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.136', 'msg'=>'Unable to load offerings', 'err'=>$rc['err']));
        }
        $course['offerings'] = isset($rc['offerings']) ? $rc['offerings'] : array();
    }

    return array('stat'=>'ok', 'course'=>$course);
}
?>
