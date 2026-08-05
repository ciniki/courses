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
function ciniki_courses_wng_offeringLoad($ciniki, $tnid, $request, $offering_id) {
    
    //
    // Load INTL settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    //
    // Load the offering details
    //
    $strsql = "SELECT ciniki_course_offerings.id, "
        . "ciniki_course_offerings.name AS offering_name, "
        . "ciniki_course_offerings.status, "
        . "ciniki_course_offerings.webflags, "
        . "ciniki_course_offerings.code AS offering_code, "
        . "ciniki_course_offerings.condensed_date, "
        . "ciniki_course_offerings.num_seats, "
        . "ciniki_course_offerings.reg_flags, "
        . "ciniki_course_offerings.dt_end_reg, "
        . "ciniki_course_offerings.primary_image_id AS image_id, "
        . "ciniki_course_offerings.synopsis, "
        . "ciniki_course_offerings.content, "
        . "ciniki_courses.id AS course_id, "
        . "ciniki_courses.name AS course_name, "
        . "ciniki_courses.code, "
        . "ciniki_courses.permalink, "
        . "ciniki_courses.primary_image_id, "
        . "ciniki_courses.level, "
        . "ciniki_courses.type, "
        . "ciniki_courses.category, "
        . "ciniki_courses.primary_image_id AS course_image_id, "
        . "ciniki_courses.short_description AS course_synopsis, "
        . "ciniki_courses.long_description AS course_content, "
        . "ciniki_course_offering_classes.id AS class_id, "
        . "DATE_FORMAT(ciniki_course_offering_classes.class_date, '%W %b %e, %Y') AS class_date, "
        . "TIME_FORMAT(ciniki_course_offering_classes.start_time, '%l:%i %p') AS start_time, "
        . "TIME_FORMAT(ciniki_course_offering_classes.end_time, '%l:%i %p') AS end_time "
        . "FROM ciniki_course_offerings "
        . "LEFT JOIN ciniki_courses ON ("
            . "ciniki_course_offerings.course_id = ciniki_courses.id "
            . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_offering_classes ON ("
            . "ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
            . "AND ciniki_course_offering_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
        . "AND ciniki_course_offerings.status <= 60 "    // Active offering
        . "AND (ciniki_course_offerings.webflags&0x01) = 0 "    // Visible
        . "ORDER BY ciniki_course_offering_classes.class_date "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'id', 
            'fields'=>array('id', 'webflags', 'status', 'course_id', 'course_name', 'offering_name', 'code', 'offering_code', 'level', 'permalink', 
                'image_id'=>'primary_image_id', 'num_seats', 'reg_flags', 'dt_end_reg',
                'level', 'type', 'category', 'image-id'=>'image_id', 'synopsis', 'content', 'course_synopsis', 'course_content', 'course_image_id', 'condensed_date')),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'class_date', 'start_time', 'end_time')),
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
        if( isset($offering['dt_end_reg']) && $offering['dt_end_reg'] != '' && $offering['dt_end_reg'] != '1970-01-01 00:00:00' ) {
            $dt = new DateTime($offering['dt_end_reg'], new DateTimezone('UTC'));
            $now = new DateTime('now', new DateTimeZone('UTC'));
            if( $dt > $now ) {
                $reg = 'yes';
            }
        } else {
            $reg = 'yes';
            $first_date = $offering['classes'][key($offering['classes'])];
            $fdt = new DateTime($first_date['class_date'], new DateTimeZone($intl_timezone));
            $fdt->setTime(3,0,0);
            $offering['start_date_ts'] = $fdt->format('U');
            $dt = new DateTime('now', new DateTimeZone($intl_timezone));
            if( $dt->format('U') > $fdt->format('U') ) {
                $reg = 'no';
            }
        }
    }

    //
    // Check if there are files for this course to be displayed
    //
    // FIXME: Needs to convert to new table
/*    if( ($ciniki['tenant']['modules']['ciniki.courses']['flags']&0x08) == 0x08 ) {
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
    } */

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
    if( $offering['status'] == 10 ) {
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
        if( isset($request['session']['customer']['price_flags']) ) {
            $price_flags = $request['session']['customer']['price_flags'];
            //
            // Check to make sure at least one class is before the membership expiration date, if member flag is set
            //
            if( isset($request['session']['customer']['membership_expiration']) && ($price_flags&0x20) == 0x20 ) {
                //
                // Remove price flags for members if expiration is after start of class
                //
                if( $offering['start_date_ts'] > $request['session']['customer']['membership_expiration'] ) {
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
                $offering['prices'][$pid]['unit_amount_display'] = '$' . number_format($price['unit_amount'], 2);
            }
        } else {
            $offering['prices'] = array();
        }
    }

    return array('stat'=>'ok', 'offering'=>$offering);
}
?>
