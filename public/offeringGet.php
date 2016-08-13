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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'),
        'classes'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Classes'),
        'instructors'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Instructor'),
        'prices'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Prices'),
        'files'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Files'),
        'registrations'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Registrations'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the business intl settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
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

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_course_offerings.id, "
        . "ciniki_course_offerings.course_id, "
        . "ciniki_course_offerings.name AS offering_name, "
        . "ciniki_course_offerings.code AS offering_code, "
        . "ciniki_course_offerings.permalink, "
        . "ciniki_course_offerings.status, "
        . "ciniki_course_offerings.status AS status_text, "
        . "ciniki_course_offerings.webflags, "
        . "ciniki_course_offerings.reg_flags, "
        . "ciniki_course_offerings.num_seats, "
        . "IF((ciniki_course_offerings.webflags&0x01)=1,'Hidden', 'Visible') AS web_visible, "
        . "ciniki_courses.name AS course_name, "
        . "ciniki_courses.code, "
        . "ciniki_courses.primary_image_id, "
        . "ciniki_courses.level, "
        . "ciniki_courses.type, "
        . "ciniki_courses.category, "
        . "ciniki_courses.short_description, "
        . "ciniki_courses.long_description "
        . "FROM ciniki_course_offerings "
        . "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
            . "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
        . "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering',
            'fields'=>array('id', 'offering_name', 'offering_code', 'permalink', 'status', 'status_text', 
                'reg_flags', 'num_seats',
                'webflags', 'web_visible', 
                'primary_image_id', 'course_id', 'course_name', 'code', 'level', 'type', 
                'category', 'short_description', 'long_description'),
            'maps'=>array('status_text'=>array('10'=>'Active', '60'=>'Deleted'))),

        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offerings']) ) {
        return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'1345', 'msg'=>'Unable to find offering'));
    }
    $offering = $rc['offerings'][0]['offering'];

    if( isset($args['classes']) && $args['classes'] == 'yes' ) {
        $strsql = "SELECT id, "
            . "IFNULL(DATE_FORMAT(class_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS class_date, "
            . "IFNULL(DATE_FORMAT(start_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS start_time, "
            . "IFNULL(DATE_FORMAT(end_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS end_time "
            . "FROM ciniki_course_offering_classes "
            . "WHERE ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_course_offering_classes.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "ORDER BY ciniki_course_offering_classes.class_date ASC "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'classes', 'fname'=>'id', 'name'=>'class',
                'fields'=>array('id', 'class_date', 'start_time', 'end_time')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['classes']) ) {
            $offering['classes'] = $rc['classes'];
        } else {
            $offering['classes'] = array();
        }
    }

    //
    // Get the list of prices for the course, if requested
    //
    if( isset($args['prices']) && $args['prices'] == 'yes' ) {
        //
        // Get the price list for the event
        //
        $strsql = "SELECT id, name, available_to, available_to AS available_to_text, unit_amount "
            . "FROM ciniki_course_offering_prices "
            . "WHERE ciniki_course_offering_prices.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "AND ciniki_course_offering_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "ORDER BY ciniki_course_offering_prices.name "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.offerings', array(
            array('container'=>'prices', 'fname'=>'id', 'name'=>'price',
                'fields'=>array('id', 'name', 'available_to', 'available_to_text', 'unit_amount'),
                'flags'=>array('available_to_text'=>$maps['price']['available_to'])),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['prices']) ) {
            $offering['prices'] = $rc['prices'];
            foreach($offering['prices'] as $pid => $price) {
                $offering['prices'][$pid]['price']['unit_amount_display'] = numfmt_format_currency(
                    $intl_currency_fmt, $price['price']['unit_amount'], $intl_currency);
            }
        } else {
            $offering['prices'] = array();
        }
    }

    //
    // Get the list of instructors for a course, if requested
    //
    if( isset($args['instructors']) && $args['instructors'] == 'yes' ) {
        $strsql = "SELECT ciniki_course_offering_instructors.id, "
            . "ciniki_course_instructors.id AS instructor_id, "
            . "CONCAT_WS(' ', ciniki_course_instructors.first, ciniki_course_instructors.last) AS name "
            . "FROM ciniki_course_offering_instructors "
            . "LEFT JOIN ciniki_course_instructors ON (ciniki_course_offering_instructors.instructor_id = ciniki_course_instructors.id "
                . "AND ciniki_course_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
            . "WHERE ciniki_course_offering_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_course_offering_instructors.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "ORDER BY ciniki_course_instructors.last "
            . "";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'instructors', 'fname'=>'id', 'name'=>'instructor',
                'fields'=>array('id', 'instructor_id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['instructors']) ) {
            $offering['instructors'] = $rc['instructors'];
        } else {
            $offering['instructors'] = array();
        }
    }

    if( isset($args['files']) && $args['files'] == 'yes' ) {
        $strsql = "SELECT ciniki_course_offering_files.id, "
            . "ciniki_course_files.id AS file_id, "
            . "ciniki_course_files.name "
            . "FROM ciniki_course_offering_files "
            . "LEFT JOIN ciniki_course_files ON (ciniki_course_offering_files.file_id = ciniki_course_files.id "
                . "AND ciniki_course_files.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
            . "WHERE ciniki_course_offering_files.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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

    //
    // Get the number of registrations, if set for the offering
    //
    $offering['seats_sold'] = 0;
    if( isset($args['registrations']) && isset($offering['reg_flags']) && ($offering['reg_flags']&0x03) > 0 ) {
        $strsql = "SELECT 'num_seats', SUM(num_seats) AS num_seats "    
            . "FROM ciniki_course_offering_registrations "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
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
    
    return array('stat'=>'ok', 'offering'=>$offering);
}
?>
