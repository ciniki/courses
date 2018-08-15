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
function ciniki_courses_web_courseOfferingDetails($ciniki, $settings, $tnid, $course_permalink, $offering_permalink) {
    
    //
    // Load INTL settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the offering details
    //
    $strsql = "SELECT ciniki_course_offerings.id, "
        . "ciniki_course_offerings.code AS offering_code, "
        . "ciniki_course_offerings.condensed_date, "
        . "ciniki_course_offerings.num_seats, "
        . "ciniki_course_offerings.reg_flags, "
        . "ciniki_courses.id AS course_id, "
        . "ciniki_courses.name, "
        . "ciniki_courses.code, "
        . "ciniki_courses.permalink, "
        . "ciniki_courses.primary_image_id, "
        . "ciniki_courses.level, "
        . "ciniki_courses.type, "
        . "ciniki_courses.category, "
        . "ciniki_courses.long_description, "
        . "ciniki_course_offering_classes.id AS class_id, "
        . "DATE_FORMAT(ciniki_course_offering_classes.class_date, '%W %b %e, %Y') AS class_date, "
        . "UNIX_TIMESTAMP(MIN(ciniki_course_offering_classes.class_date)) AS start_date_ts, "
        . "MIN(ciniki_course_offering_classes.class_date) AS first_date, "
        . "TIME_FORMAT(ciniki_course_offering_classes.start_time, '%l:%i %p') AS start_time, "
        . "TIME_FORMAT(ciniki_course_offering_classes.end_time, '%l:%i %p') AS end_time "
        . "FROM ciniki_course_offerings "
        . "LEFT JOIN ciniki_courses ON ("
            . "ciniki_course_offerings.course_id = ciniki_courses.id "
            . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "') "
        . "LEFT JOIN ciniki_course_offering_classes ON ("
            . "ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
            . "AND ciniki_course_offering_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "') "
        . "WHERE ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_course_offerings.permalink = '" . ciniki_core_dbQuote($ciniki, $offering_permalink) . "' "
        . "AND ciniki_courses.permalink = '" . ciniki_core_dbQuote($ciniki, $course_permalink) . "' "
        . "AND ciniki_course_offerings.status = 10 "    // Active offering
        . "AND (ciniki_course_offerings.webflags&0x01) = 0 "    // Visible
        . "ORDER BY ciniki_course_offering_classes.class_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'id', 
            'fields'=>array('id', 'course_id', 'name', 'code', 'offering_code', 'level', 'permalink', 
                'first_date', 'start_date_ts', 'image_id'=>'primary_image_id', 'num_seats', 'reg_flags',
                'level', 'type', 'category', 'long_description', 'condensed_date')),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'class_date', 'start_date_ts', 'start_time', 'end_time')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offerings']) || count($rc['offerings']) < 1 ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.71', 'msg'=>"I'm sorry, but we can't seem to find the course you requested."));
    }
    $offering = array_pop($rc['offerings']);

    //
    // Check if course is in the future
    //
    $reg = 'no';
    if( ($offering['reg_flags']&0x02) > 0 && isset($offering['classes']) ) {
        $reg = 'yes';
        $fdt = new DateTime($offering['first_date'], new DateTimeZone($intl_timezone));
        $fdt->setTime(3,0,0);
        $dt = new DateTime('now', new DateTimeZone($intl_timezone));
        if( $dt->format('U') > $fdt->format('U') ) {
            $reg = 'no';
        }
        // **** OLD METHOD of calculating if reg should still be open
        //$dt->setTimezone(new DateTimeZone('UTC'));
//        $end_of_cur_day = $dt->format('U');
//        foreach($offering['classes'] as $class) {
//            if( $class['start_date_ts'] < $end_of_cur_day ) {
//                $reg = 'no';
//            }
//        }
    }

    //
    // Check if there are files for this course to be displayed
    //
    if( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x08) == 0x08 ) {
        $strsql = "SELECT ciniki_course_files.id, "
            . "ciniki_course_files.name, "
            . "ciniki_course_files.permalink, ciniki_course_files.extension "
            . "FROM ciniki_course_offering_files "
            . "LEFT JOIN ciniki_course_files ON (ciniki_course_offering_files.file_id = ciniki_course_files.id "
                . "AND ciniki_course_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' ) "
            . "WHERE ciniki_course_offering_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_course_offering_files.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
            . "ORDER BY ciniki_course_files.name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'files', 'fname'=>'id', 
                'fields'=>array('id', 'name', 'permalink', 'extension')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $offering['files'] = $rc['files'];
        }
    }

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x0200) ) {
        $strsql = "SELECT id, "
            . "name, "
            . "permalink, "
            . "flags, "
            . "image_id, "
            . "description, "
            . "UNIX_TIMESTAMP(last_updated) AS last_updated "
            . "FROM ciniki_course_images "
            . "WHERE course_id = '" . ciniki_core_dbQuote($ciniki, $offering['course_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.lapt', array(
            array('container'=>'images', 'fname'=>'id', 
                'fields'=>array('id', 'title'=>'name', 'permalink', 'flags', 'image_id', 'description', 'last_updated')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $offering['images'] = isset($rc['images']) ? $rc['images'] : array();
    }

    //
    // Check for prices
    //
    if( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x04) > 0 ) {
        $offering['seats_sold'] = 0;
        $strsql = "SELECT 'num_seats', SUM(num_seats) AS num_seats "
            . "FROM ciniki_course_offering_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.courses', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['num_seats']) ) {
            $offering['seats_sold'] = $rc['num']['num_seats'];
        }
    
        //
        // Check if any prices are attached to the event
        //
        if( isset($ciniki['session']['customer']['price_flags']) ) {
            $price_flags = $ciniki['session']['customer']['price_flags'];
            //
            // Check to make sure at least one class is before the membership expiration date, if member flag is set
            //
            if( isset($ciniki['session']['customer']['membership_expiration']) && ($price_flags&0x20) == 0x20 ) {
                //
                // Remove price flags for members if expiration is after start of class
                //
                if( $offering['start_date_ts'] > $ciniki['session']['customer']['membership_expiration'] ) {
                    $price_flags = $price_flags &~ 0x20;
                }
            }
        } else {
            $price_flags = 0x01;
        }

        //
        // Get the price list for the course offering
        //
        $strsql = "SELECT id, name, available_to, unit_amount "
            . "FROM ciniki_course_offering_prices "
            . "WHERE ciniki_course_offering_prices.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
            . "AND ciniki_course_offering_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_course_offering_prices.webflags&0x01) = 0 "
            . "AND ((ciniki_course_offering_prices.available_to&$price_flags) > 0 OR (webflags&available_to&0xF0) > 0) "
            . "ORDER BY ciniki_course_offering_prices.name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'prices', 'fname'=>'id',
                'fields'=>array('price_id'=>'id', 'name', 'available_to', 'unit_amount')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['prices']) ) {
            $offering['prices'] = $rc['prices'];
            foreach($offering['prices'] as $pid => $price) {
                //
                // Check if online registrations enabled
                //
                if( $reg == 'yes' && ($price['available_to']&$price_flags) > 0 ) {
                    $offering['prices'][$pid]['cart'] = 'yes';
                } else {
                    $offering['prices'][$pid]['cart'] = 'no';
                }
                $offering['prices'][$pid]['object'] = 'ciniki.courses.offering';
                $offering['prices'][$pid]['object_id'] = $offering['id'];
                if( $offering['num_seats'] > 0 ) {
                    $offering['prices'][$pid]['limited_units'] = 'yes';
                    $offering['prices'][$pid]['units_available'] = $offering['num_seats'] - $offering['seats_sold'];
                }
                $offering['prices'][$pid]['unit_amount_display'] = numfmt_format_currency(
                    $intl_currency_fmt, $price['unit_amount'], $intl_currency);
            }
        } else {
            $offering['prices'] = array();
        }
    }

    return array('stat'=>'ok', 'offering'=>$offering);
}
?>
