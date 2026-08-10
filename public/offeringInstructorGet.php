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
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offeringinstructor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Instructor'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringInstructorGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki);

    //
    // Return default for new Course Offering Instructor
    //
    if( $args['offeringinstructor_id'] == 0 ) {
        $instructor = array('id'=>0,
            'course_id'=>'',
            'offering_id'=>'',
            'instructor_id'=>'',
            'prefix' => '',
            'suffix' => '',
        );
    }
    else {
        //
        // Get the main information
        //
        $strsql = "SELECT instructors.id, "
            . "instructors.course_id, "
            . "instructors.offering_id, "
            . "instructors.instructor_id, "
            . "instructors.prefix, "
            . "instructors.suffix "
            . "FROM ciniki_course_offering_instructors AS instructors "
            . "WHERE instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND instructors.id = '" . ciniki_core_dbQuote($ciniki, $args['offeringinstructor_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'instructors', 'fname'=>'id', 
                'fields'=>array('id', 'course_id', 'offering_id', 'instructor_id', 'prefix', 'suffix'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.330', 'msg'=>'Course Offering Instructor not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['instructors'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.331', 'msg'=>'Unable to find Course Offering Instructor'));
        }
        $instructor = $rc['instructors'][0];

/*        $strsql = "SELECT ciniki_course_offering_instructors.id, "
            . "ciniki_course_instructors.id AS instructor_id, "
            . "CONCAT_WS(' ', ciniki_course_instructors.first, ciniki_course_instructors.last) AS name, "
            . "ciniki_course_offering_instructors.prefix, "
            . "ciniki_course_offering_instructors.suffix, "
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
                . "AND ciniki_course_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "') "
            . "WHERE ciniki_course_offering_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_offering_instructors.id = '" . ciniki_core_dbQuote($ciniki, $args['offering_instructor_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
                'fields'=>array('id', 'instructor_id', 'prefix', 'first', 'last', 'name', 'suffix', 'permalink', 
                    'primary_image_id', 'webflags', 'web_visible', 'short_bio', 'full_bio', 'url',
                    )),
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
                . "WHERE ciniki_course_instructor_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
                            $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image']['image_id'], 75);
                            if( $rc['stat'] != 'ok' ) {
                                return $rc;
                            }
                            $instructor['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                        }
                    }
                }
            } 
        } */
    }
    
    $rsp = array('stat'=>'ok', 'instructor'=>$instructor);

    //
    // Load the list of instructors
    //
    $strsql = "SELECT instructors.id, "
        . "IFNULL(customers.display_name, CONCAT_WS(' ', instructors.first, instructors.last)) AS name "
        . "FROM ciniki_course_instructors AS instructors "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "instructors.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( $instructor['id'] > 0 ) {
        $strsql .= "AND (instructors.status = 10 OR instructors.id = '" . ciniki_core_dbQuote($ciniki, $instructor['id']) . "') ";
    } else {
        $strsql .= "AND instructors.status = 10 ";
    }
    $strsql .= "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'instructors', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.332', 'msg'=>'Unable to load instructors', 'err'=>$rc['err']));
    }
    $rsp['instructors'] = isset($rc['instructors']) ? $rc['instructors'] : array();

    return $rsp;
}
?>
