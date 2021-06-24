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
// offering_id:         The ID of the offering to get.
//
// Returns
// -------
//
function ciniki_courses_offeringGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'),
        'classes'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Classes'),
        'instructors'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Instructor'),
        'prices'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Prices'),
        'files'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Files'),
        'images'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Images'),
        'registrations'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Registrations'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the tenant intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki);

    //
    // Load event maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'maps');
    $rc = ciniki_courses_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];


    if( $args['offering_id'] == 0 ) {
        $offering = array(
            'course_id' => 0,
            'name' => '',
            'code' => '',
            'permalink' => '',
            'status' => 10,
            'webflags' => 0,
            'start_date' => '',
            'end_date' => '',
            'condensed_date' => '',
            'num_seats' => '',
            'reg_flags' => 0,
            );
    } 
    //
    // Get the main information
    //
    else {
        $strsql = "SELECT ciniki_course_offerings.id, "
            . "ciniki_course_offerings.course_id, "
            . "ciniki_course_offerings.name, "
            . "ciniki_course_offerings.code, "
            . "ciniki_course_offerings.permalink, "
            . "ciniki_course_offerings.status, "
            . "ciniki_course_offerings.status AS status_text, "
            . "ciniki_course_offerings.webflags, "
            . "ciniki_course_offerings.reg_flags, "
            . "ciniki_course_offerings.num_seats, "
            . "ciniki_course_offerings.start_date, "
            . "ciniki_course_offerings.end_date, "
            . "ciniki_course_offerings.condensed_date, "
            . "IF((ciniki_course_offerings.webflags&0x01)=1,'Hidden', 'Visible') AS web_visible, "
            . "ciniki_courses.name AS course_name, "
            . "ciniki_courses.code AS course_code, "
            . "ciniki_courses.primary_image_id, "
            . "ciniki_courses.status AS course_status, "
            . "ciniki_courses.level, "
            . "ciniki_courses.type, "
            . "ciniki_courses.category, "
            . "ciniki_courses.flags, "
            . "ciniki_courses.short_description, "
            . "ciniki_courses.long_description "
            . "FROM ciniki_course_offerings "
            . "LEFT JOIN ciniki_courses ON ("
                . "ciniki_course_offerings.course_id = ciniki_courses.id "
                . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'offerings', 'fname'=>'id',
                'fields'=>array('id', 'name', 'code', 'permalink', 'status', 'status_text', 
                    'reg_flags', 'num_seats', 'start_date', 'end_date', 'condensed_date', 'webflags', 'web_visible', 
                    'primary_image_id', 'course_id', 'course_status', 'course_name', 'course_code', 'level', 'type', 
                    'category', 'flags', 'short_description', 'long_description',
                    ),
                'dtformat'=>array('start_date'=>$date_format,
                    'end_date'=>$date_format,
                    ),
                'maps'=>array('status_text'=>array('10'=>'Active', '60'=>'Deleted')),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['offerings']) ) {
            return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.courses.38', 'msg'=>'Unable to find offering'));
        }
        $offering = $rc['offerings'][0];

        //
        // Get the list of classes for the offering
        //
        $strsql = "SELECT id, "
            . "class_date, "
            . "IFNULL(DATE_FORMAT(start_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS start_time, "
            . "IFNULL(DATE_FORMAT(end_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS end_time "
            . "FROM ciniki_course_offering_classes "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "ORDER BY class_date ASC "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'classes', 'fname'=>'id', 
                'fields'=>array('id', 'class_date', 'start_time', 'end_time'),
                'dtformat'=>array('class_date'=>'D M j, Y'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $offering['classes'] = isset($rc['classes']) ? $rc['classes'] : array();

        //
        // Get the price list for the event
        //
        $strsql = "SELECT id, "
            . "name, "
            . "available_to, "
            . "available_to AS available_to_text, "
            . "unit_amount "
            . "FROM ciniki_course_offering_prices "
            . "WHERE offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.offerings', array(
            array('container'=>'prices', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'available_to', 'available_to_text', 'unit_amount'),
                'flags'=>array('available_to_text'=>$maps['price']['available_to']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $offering['prices'] = isset($rc['prices']) ? $rc['prices'] : array();
        foreach($offering['prices'] as $pid => $price) {
            $offering['prices'][$pid]['unit_amount_display'] = numfmt_format_currency(
                $intl_currency_fmt, $price['unit_amount'], $intl_currency);
        }

        //
        // Get the list of instructors for a course, if requested
        //
        $strsql = "SELECT ciniki_course_offering_instructors.id, "
            . "ciniki_course_instructors.id AS instructor_id, "
            . "CONCAT_WS(' ', ciniki_course_instructors.first, ciniki_course_instructors.last) AS name "
            . "FROM ciniki_course_offering_instructors "
            . "LEFT JOIN ciniki_course_instructors ON ("
                . "ciniki_course_offering_instructors.instructor_id = ciniki_course_instructors.id "
                . "AND ciniki_course_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_course_offering_instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_offering_instructors.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "ORDER BY ciniki_course_instructors.last "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'instructors', 'fname'=>'id',
                'fields'=>array('id', 'instructor_id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $offering['instructors'] = isset($rc['instructors']) ? $rc['instructors'] : array();

/* TO BE REMOVED, Files now attached to courses only
        if( isset($args['files']) && $args['files'] == 'yes' ) {
            $strsql = "SELECT ciniki_course_offering_files.id, "
                . "ciniki_course_files.id AS file_id, "
                . "ciniki_course_files.name "
                . "FROM ciniki_course_offering_files "
                . "LEFT JOIN ciniki_course_files ON (ciniki_course_offering_files.file_id = ciniki_course_files.id "
                    . "AND ciniki_course_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "') "
                . "WHERE ciniki_course_offering_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_course_offering_files.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
                . "ORDER BY ciniki_course_files.name "
                . "";
            $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'files', 'fname'=>'id', 'name'=>'file',
                    'fields'=>array('id', 'file_id', 'name')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['files']) ) {
                $offering['files'] = $rc['files'];
            } else {
                $offering['files'] = array();
            }
        }

        $strsql = "SELECT id, "
            . "name, "
            . "flags, "
            . "image_id, "
            . "description "
            . "FROM ciniki_course_images "
            . "WHERE course_id = '" . ciniki_core_dbQuote($ciniki, $offering['course_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
            array('container'=>'images', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'flags', 'image_id', 'description')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $offering['images'] = isset($rc['images']) ? $rc['images'] : array();

        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
        foreach($offering['images'] as $img_id => $img) {
            if( isset($img['image_id']) && $img['image_id'] > 0 ) {
                $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image_id'], 75);
                if( $rc['stat'] != 'ok' ) {
                    return $rc;
                }
                $offering['images'][$img_id]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
            }
        }
*/

        //
        // Get the list of registrations
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
        $rc = ciniki_sapos_maps($ciniki);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $status_maps = $rc['maps']['invoice']['payment_status'];
        $status_maps[0] = 'No Invoice';

        $strsql = "SELECT ciniki_course_offering_registrations.id, "
            . "ciniki_course_offering_registrations.customer_id, "
            . "ciniki_course_offering_registrations.student_id, "
            . "IFNULL(c1.display_name, '') AS customer_name, "
            . "IFNULL(c2.display_name, '') AS student_name, "
            . "IFNULL(c2.display_name, IFNULL(c1.display_name, '')) AS sort_name, "
            . "IFNULL(TIMESTAMPDIFF(YEAR, c2.birthdate, CURDATE()), '') AS yearsold, "
            . "ciniki_course_offering_registrations.num_seats, "
            . "ciniki_course_offering_registrations.invoice_id, "
            . "IFNULL(ciniki_sapos_invoices.payment_status, 0) AS invoice_status, "
            . "IFNULL(ciniki_sapos_invoices.payment_status, 0) AS invoice_status_text, "
            . "IFNULL(ciniki_sapos_invoice_items.total_amount, 0) AS registration_amount "
            . "FROM ciniki_course_offering_registrations "
            . "LEFT JOIN ciniki_customers AS c1 ON ("
                . "ciniki_course_offering_registrations.customer_id = c1.id "
                . "AND c1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_customers AS c2 ON ("
                . "ciniki_course_offering_registrations.student_id = c2.id "
                . "AND c2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoices ON ("
                . "ciniki_course_offering_registrations.invoice_id = ciniki_sapos_invoices.id "
                . "AND ciniki_sapos_invoices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_sapos_invoice_items ON ("
                . "ciniki_sapos_invoices.id = ciniki_sapos_invoice_items.invoice_id "
                . "AND ciniki_sapos_invoice_items.object = 'ciniki.courses.offering_registration' "
                . "AND ciniki_course_offering_registrations.id = ciniki_sapos_invoice_items.object_id "
                . "AND ciniki_sapos_invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE ciniki_course_offering_registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "ORDER BY sort_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'registrations', 'fname'=>'id', 
                'fields'=>array('id', 'customer_id', 'customer_name', 'student_name', 'yearsold', 'num_seats', 
                    'invoice_id', 'invoice_status', 'invoice_status_text', 'registration_amount'),
                'naprices'=>array('registration_amount'),
                'maps'=>array('invoice_status_text'=>$status_maps)),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.46', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
        }
        $offering['registrations'] = isset($rc['registrations']) ? $rc['registrations'] : array();

        //
        // Get the number of registrations, if set for the offering
        //
        $offering['seats_sold'] = 0;
        if( isset($offering['reg_flags']) && ($offering['reg_flags']&0x03) > 0 ) {
            $strsql = "SELECT 'num_seats', SUM(num_seats) AS num_seats "    
                . "FROM ciniki_course_offering_registrations "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
            $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.courses', 'num');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['num']['num_seats']) ) {
                $offering['seats_sold'] = $rc['num']['num_seats'];
            }
        }

        //
        // Pull emails sent for this course
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'mail', 'hooks', 'objectMessages');
        $rc = ciniki_mail_hooks_objectMessages($ciniki, $args['tnid'], array(
            'object' => 'ciniki.courses.offering',
            'object_id' => $args['offering_id'],
            'xml' => 'no',
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $offering['messages'] = isset($rc['messages']) ? $rc['messages'] : array();
    }

    //
    // Return the list of courses
    //
    $strsql = "SELECT id, code, name "
        . "FROM ciniki_courses "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ";
    if( !isset($offering['course_status']) || $offering['course_status'] < 90 ) {
        // Get only list of active courses if the attached offering course is active
        $strsql .= "AND status < 90 ";
    }
    $strsql .= "ORDER BY code, name ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'courses', 'fname'=>'id', 
            'fields'=>array('id', 'code', 'name'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.146', 'msg'=>'Unable to load courses', 'err'=>$rc['err']));
    }
    $courses = isset($rc['courses']) ? $rc['courses'] : array();

    
    return array('stat'=>'ok', 'offering'=>$offering, 'courses'=>$courses);
}
?>
