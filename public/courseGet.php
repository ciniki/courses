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
        'form_submission_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Form Submission'),
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
        if( ciniki_core_checkModuleActive($ciniki, 'ciniki.forms')
            && isset($args['form_submission_id']) 
            && $args['form_submission_id'] > 0 
            ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'formSubmissionParse');
            $rc = ciniki_courses_formSubmissionParse($ciniki, $args['tnid'], $args['form_submission_id']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.222', 'msg'=>'Unable to load form submission', 'err'=>$rc['err']));
            }
            $form_course = isset($rc['course']) ? $rc['course'] : array();
        }
        $course = array(
            'id' => 0,
            'name' => isset($form_course['name']) ? $form_course['name'] : '',
            'code' => '',
            'status' => 10,
            'primary_image_id' => isset($form_course['primary_image_id']) ? $form_course['primary_image_id'] : 0,
            'level' => isset($form_course['level']) ? $form_course['level'] : '',
            'type' => isset($form_course['type']) ? $form_course['type'] : '',
            'category' => isset($form_course['category']) ? $form_course['category'] : '',
            'medium' => isset($form_course['medium']) ? $form_course['medium'] : '',
            'ages' => isset($form_course['ages']) ? $form_course['ages'] : '',
            'flags' => 0,
            'short_description' => isset($form_course['short_description']) ? $form_course['short_description'] : '',
            'long_description' => isset($form_course['long_description']) ? $form_course['long_description'] : '',
            'materials_list' => isset($form_course['materials_list']) ? $form_course['materials_list'] : '',
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
            . "ciniki_courses.long_description, "
            . "ciniki_courses.materials_list, "
            . "ciniki_courses.paid_content "
            . "FROM ciniki_courses "
            . "WHERE ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_courses.id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'courses', 'fname'=>'id', 'name'=>'course',
                'fields'=>array('id', 'name', 'code', 'status', 'primary_image_id', 
                    'level', 'type', 'category', 'medium', 'ages',
                    'flags', 'short_description', 'long_description', 'materials_list', 'paid_content',
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
            $strsql = "SELECT id, "
                . "name, "
                . "if((webflags&0x01)=0x01, 'Yes', 'No') AS visible, "
                . "if((webflags&0x10)=0x10, 'Yes', 'No') AS paid_content "
                . "FROM ciniki_course_files "
                . "WHERE course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'files', 'fname'=>'id', 'fields'=>array('id', 'name', 'visible', 'paid_content')),
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
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
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
            . "offerings.status, "
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
            array('container'=>'status', 'fname'=>'status', 'fields'=>array('status')),
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
        $course['offerings'] = array();
        if( isset($rc['status']) ) {
            foreach($rc['status'] as $s) {
                if( $s['status'] == 10 ) {
                    $course['offerings'] = $s['offerings'];
                } elseif( $s['status'] == 90 ) {
                    $course['archived'] = $s['offerings'];
                }
            }
        }
    }

    $rsp = array('stat'=>'ok', 'course'=>$course);

    //
    // Get the list of subcategories
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x100000) ) {
        $strsql = "SELECT subcategories.id, "
            . "CONCAT_WS(' - ', categories.name, subcategories.name) AS name "
            . "FROM ciniki_course_categories AS categories "
            . "INNER JOIN ciniki_course_subcategories AS subcategories ON ("
                . "categories.id = subcategories.category_id "
                . "AND subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY categories.sequence, categories.name, subcategories.sequence, subcategories.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'org_subcategories', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.288', 'msg'=>'Unable to load org_subcategories', 'err'=>$rc['err']));
        }
        $rsp['org_subcategories'] = isset($rc['org_subcategories']) ? $rc['org_subcategories'] : array();
    }

    return $rsp;
}
?>
