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
function ciniki_courses_wng_courseDetails($ciniki, $tnid, $request, $permalink) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

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
    numfmt_set_attribute($intl_currency_fmt, NumberFormatter::ROUNDING_MODE, NumberFormatter::ROUND_HALFUP);
    $intl_currency = $rc['settings']['intl-default-currency'];

    //
    // Load the course details
    //

    $strsql = "SELECT ciniki_courses.id, "
        . "ciniki_courses.name, "
        . "ciniki_courses.code, "
        . "ciniki_courses.permalink, "
        . "ciniki_courses.primary_image_id AS image_id, "
        . "ciniki_courses.flags, "
        . "if( (ciniki_courses.flags&0x10) = 0x10, 'yes', 'no') AS timeless, "
        . "ciniki_courses.level, "
        . "ciniki_courses.type, "
        . "ciniki_courses.category AS medium, "
        . "ciniki_courses.short_description AS synopsis, "
        . "ciniki_courses.long_description, "
        . "ciniki_courses.materials_list, "
        . "ciniki_courses.paid_content "
        . "FROM ciniki_courses "
        . "WHERE permalink = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        . "AND ciniki_courses.status = 30 "     // Active
        . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.285', 'msg'=>'Unable to load course', 'err'=>$rc['err']));
    }
    if( !isset($rc['course']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.286', 'msg'=>'Unable to find requested course'));
    }
    $course = $rc['course'];
    $course['registered'] = 'no';
    
    //
    // Load the offerings
    //
    $strsql = "SELECT offerings.id, "
        . "offerings.webflags, "
        . "offerings.code, "
        . "offerings.name, "
        . "offerings.permalink, "
        . "offerings.start_date, "
        . "offerings.end_date, "
        . "offerings.dt_end_reg, "
        . "offerings.condensed_date, "
        . "offerings.num_seats, "
        . "offerings.reg_flags, "
        . "offerings.primary_image_id, "
        . "offerings.synopsis, "
        . "offerings.content, "
        . "offerings.materials_list, "
        . "offerings.paid_content, "
        . "classes.id AS class_id, "
        . "DATE_FORMAT(classes.class_date, '%W %b %e, %Y') AS class_date, "
        . "TIME_FORMAT(classes.start_time, '%l:%i %p') AS start_time, "
        . "TIME_FORMAT(classes.end_time, '%l:%i %p') AS end_time "
        . "FROM ciniki_course_offerings AS offerings "
        . "LEFT JOIN ciniki_course_offering_classes AS classes ON ("
            . "offerings.id = classes.offering_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND offerings.course_id = '" . ciniki_core_dbQuote($ciniki, $course['id']) . "' "
        . "AND offerings.status = 10 "    // Active offering
        . "AND (offerings.webflags&0x01) = 0 "    // Visible
        . "ORDER BY offerings.start_date, offerings.code, offerings.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'id', 
            'fields'=>array('id', 'webflags', 'code', 'name', 'permalink', 'num_seats', 'reg_flags', 
                'primary_image_id', 'synopsis', 'content', 'materials_list', 'paid_content',
                'start_date', 'end_date', 'dt_end_reg', 'condensed_date')),
        array('container'=>'classes', 'fname'=>'class_id', 
            'fields'=>array('id'=>'class_id', 'class_date', 'start_time', 'end_time')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $course['offerings'] = isset($rc['offerings']) ? $rc['offerings'] : array();
    $offering_ids = array();
    $dt = new DateTime('now', new DateTimeZone($intl_timezone));
    foreach($course['offerings'] as $oid => $offering) {
        $offering_ids[] = $offering['id'];
        //
        // Determine if offering has already started
        //
        if( $course['timeless'] == 'no' ) {
            $first_dt = new DateTime($offering['start_date'], new DateTimezone($intl_timezone));
            $last_dt = new DateTime($offering['end_date'], new DateTimezone($intl_timezone));
            $first_dt->setTime(3,0,0);
            $last_dt->setTime(3,0,0);
            $course['offerings'][$oid]['start_date_ts'] = $first_dt->format('U');
            if( $dt->format('U') > $first_dt->format('U') && $dt->format('U') < $last_dt->format('U') ) {
                $course['offerings'][$oid]['inprogress'] = 'yes';
            } elseif( $dt->format('U') > $first_dt->format('U') ) {
                $course['offerings'][$oid]['past'] = 'yes';
            }
            if( $offering['dt_end_reg'] != '0000-00-00 00:00:00' && $offering['dt_end_reg'] != '' ) {
                $end_reg_dt = new DateTime($offering['dt_end_reg'], new DateTimezone('UTC'));
                $end_reg_dt->setTimezone(new DateTimezone($intl_timezone));
                if( $end_reg_dt > $dt && isset($course['offerings'][$oid]['inprogress']) ) {
                    unset($course['offerings'][$oid]['inprogress']);
                }
                elseif( $end_reg_dt <= $dt && !isset($course['offerings'][$oid]['inprogress'])
                    && !isset($course['offerings'][$oid]['past']) 
                    ) {
                    $course['offerings'][$oid]['regclosed'] = 'yes';
                }
            }
        }
    }

    //
    // Check if user is registered for the offering
    //
    if( isset($request['session']['customer']['id']) && count($offering_ids) > 0 ) {
        $strsql = "SELECT registrations.id "
            . "FROM ciniki_course_offering_registrations AS registrations "
            . "WHERE registrations.offering_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $offering_ids) . ") "
            . "AND ("
                . "registrations.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . "OR registrations.student_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
                . ") "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'reg');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.287', 'msg'=>'Unable to load reg', 'err'=>$rc['err']));
        }
        if( isset($rc['rows']) && count($rc['rows']) > 0 ) {
            $course['registered'] = 'yes';
        }
    }

    //
    // Check if there are files for this course to be displayed
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x08) ) {
        $strsql = "SELECT files.id, "
            . "files.uuid, "
            . "files.name, "
            . "files.permalink, "
            . "files.webflags, "
            . "files.extension "
            . "FROM ciniki_course_files AS files "
            . "WHERE files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND files.course_id = '" . ciniki_core_dbQuote($ciniki, $course['id']) . "' "
            . "AND (files.webflags&0x01) = 0x01 "    // Visible
            . "ORDER BY files.name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'files', 'fname'=>'id', 
                'fields'=>array('id', 'uuid', 'name', 'permalink', 'webflags', 'extension')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $course['files'] = array();
            $course['paid_content_files'] = array();
            foreach($rc['files'] as $file) {
                $file['permalink'] = $file['permalink'] . '.' . $file['extension'];
                if( ($file['webflags']&0x10) == 0x10 ) {
                    $course['paid_content_files'][$file['permalink']] = $file;
                } else {
                    $course['files'][$file['permalink']] = $file;
                }
            }
        }
    }

    //
    // Get the instructors for all the offerings
    //
    $strsql = "SELECT instructors.id, "
        . "IFNULL(customers.display_name, CONCAT_WS(' ', instructors.first, instructors.last)) AS name, "
        . "IFNULL(customers.last, instructors.last) AS last, "
        . "IFNULL(customers.first, instructors.first) AS first, "
        . "'' AS title, "
        . "instructors.primary_image_id, "
        . "instructors.permalink, "
        . "instructors.short_bio "
        . "FROM ciniki_course_offerings AS offerings "
        . "INNER JOIN ciniki_course_offering_instructors AS oi ON ("
            . "offerings.id = oi.offering_id "
            . "AND oi.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_course_instructors AS instructors ON ("
            . "oi.instructor_id = instructors.id "
            . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (instructors.webflags&0x01) = 0 "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "instructors.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE offerings.course_id = '" . ciniki_core_dbQuote($ciniki, $course['id']) . "' "
        . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY last, first "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'instructors', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'image_id'=>'primary_image_id', 'permalink', 'synopsis'=>'short_bio')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.289', 'msg'=>'Unable to load instructors', 'err'=>$rc['err']));
    }
    $course['instructors'] = isset($rc['instructors']) ? $rc['instructors'] : array();

    //
    // Load the course images
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x0200) ) {
        $strsql = "SELECT id, "
            . "name, "
            . "permalink, "
            . "flags, "
            . "image_id, "
            . "description, "
            . "UNIX_TIMESTAMP(last_updated) AS last_updated "
            . "FROM ciniki_course_images "
            . "WHERE course_id = '" . ciniki_core_dbQuote($ciniki, $course['id']) . "' "
            . "AND (flags&0x01) = 0x01 "
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
        if( isset($rc['images']) && count($rc['images']) > 0 ) {
            $course['images'] = array();
            $course['paid_content_images'] = array();
            foreach($rc['images'] as $image) {
                if( ($image['flags']&0x10) == 0x10 ) {
                    $course['paid_content_images'][$image['permalink']] = $image;
                } else {
                    $course['images'][$image['permalink']] = $image;
                }
            }
        }
    }

    //
    // Check for prices
    //
    if( isset($course['offerings']) && count($course['offerings']) > 0 
        && ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x04) 
        ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');
        $strsql = "SELECT offering_id, SUM(num_seats) AS num_seats "
            . "FROM ciniki_course_offering_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND offering_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $offering_ids) . ") "
            . "GROUP BY offering_id "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.courses', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $seats_sold = isset($rc['num']) ? $rc['num'] : array();
    
        //
        // Check if any prices are attached to the event
        //
        if( isset($request['session']['customer']['price_flags']) ) {
            $price_flags = $request['session']['customer']['price_flags'];
        } else {
            $price_flags = 0x01;
        }

        //
        // Get the price list for the course offering
        //
        $strsql = "SELECT prices.id, "
            . "prices.offering_id, "
            . "prices.name, "
            . "prices.available_to, "
            . "prices.unit_amount "
            . "FROM ciniki_course_offering_prices AS prices "
            . "WHERE prices.offering_id IN (" . ciniki_core_dbQuoteIDs($ciniki, $offering_ids) . ") "
            . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (prices.webflags&0x01) = 0 "
            . "AND ((prices.available_to&$price_flags) > 0 OR (webflags&available_to&0xF0) > 0) "
            . "ORDER BY prices.offering_id, prices.name "
            . "";
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'offerings', 'fname'=>'offering_id', 'fields'=>array()),
            array('container'=>'prices', 'fname'=>'id',
                'fields'=>array('price_id'=>'id', 'name', 'available_to', 'unit_amount')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $prices = isset($rc['offerings']) ? $rc['offerings'] : array();

        //
        // Add the prices and seats sold to each offering
        //
        foreach($course['offerings'] as $oid => $offering) {
            $course['offerings'][$oid]['seats_sold'] = 0;
            if( isset($seats_sold[$offering['id']]) ) {
                $course['offerings'][$oid]['seats_sold'] = $seats_sold[$offering['id']];
            }
            if( isset($prices[$offering['id']]['prices']) ) {
                $course['offerings'][$oid]['prices'] = $prices[$offering['id']]['prices'];
                foreach($course['offerings'][$oid]['prices'] as $pid => $price) {
                    //
                    // Check to make sure at least one class is before the membership expiration date, if member flag is set
                    //
                    if( isset($request['session']['customer']['membership_expiration']) 
                        && ($price['available_to']&0x20) == 0x20 
                        ) {
                        //
                        // Remove price flags for members if expiration is after start of class
                        //
                        if( $offering['start_date_ts'] > $request['session']['customer']['membership_expiration'] ) {
                            $price['available_to'] = $price['available_to'] &~ 0x20;
                        }
                    }
                    //
                    // Check if course is in the future
                    //
                    $reg = 'no';
                    if( ($offering['reg_flags']&0x02) > 0 && isset($offering['classes']) 
                        && isset($offering['inprogress']) 
                        ) {
                        $reg = 'no'; 
                        $course['offerings'][$oid]['prices'][$pid]['inprogress'] = 'yes';
                    } 
                    elseif( ($offering['reg_flags']&0x02) > 0 && isset($offering['classes']) 
                        && isset($offering['regclosed']) 
                        ) {
                        $reg = 'no'; 
                        $course['offerings'][$oid]['prices'][$pid]['regclosed'] = 'yes';
                    } 
                    elseif( ($offering['reg_flags']&0x02) > 0 && !isset($offering['past']) ) {    
                        $reg = 'yes';
                    }

                    //
                    // Check if online registrations enabled
                    //
                    if( $reg == 'yes' && ($price['available_to']&0x01) == 0x01 ) {
                        // public price
                        $course['offerings'][$oid]['prices'][$pid]['cart'] = 'yes';
                    } elseif( $reg == 'yes' 
                        && ($price['available_to']&0x20) == 0x20
                        && isset($request['session']['customer']['price_flags'])
                        && ($request['session']['customer']['price_flags']&0x20) == 0x20 
                        ) {
                        // member price
                        $course['offerings'][$oid]['prices'][$pid]['cart'] = 'yes';
                    } else {
                        $course['offerings'][$oid]['prices'][$pid]['cart'] = 'no';
                        $course['offerings'][$oid]['prices'][$pid]['no-cart-msg'] = ' ';
                    }
                    $course['offerings'][$oid]['prices'][$pid]['object'] = 'ciniki.courses.offering';
                    $course['offerings'][$oid]['prices'][$pid]['object_id'] = $offering['id'];
                    if( $offering['num_seats'] > 0 ) {
                        $course['offerings'][$oid]['prices'][$pid]['limited_units'] = 'yes';
                        $course['offerings'][$oid]['prices'][$pid]['units_available'] = $offering['num_seats'] - $course['offerings'][$oid]['seats_sold'];
                        if( ($offering['num_seats'] - $course['offerings'][$oid]['seats_sold']) <= 0 ) {
                            $course['offerings'][$oid]['soldout'] = 'yes';
                        }
                    }
                    $course['offerings'][$oid]['prices'][$pid]['unit_amount_display'] = numfmt_format_currency(
                        $intl_currency_fmt, $price['unit_amount'], $intl_currency);
                }
            } else {
                $course['offerings'][$oid]['prices'] = array();
            }
        }
    }

    return array('stat'=>'ok', 'course'=>$course);
}
?>
