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
function ciniki_courses_web_courseList($ciniki, $settings, $tnid, $type, $when) {

    //
    // Get the course instructors
    // FIXME: This is not an efficient way to get this information.
    //
    if( isset($settings['page-courses-list-include-instructors']) && $settings['page-courses-list-include-instructors'] == 'yes' ) {
        $strsql = "SELECT offerings.id, " 
            . "instructors.id AS instructor_id, "
            . "instructors.first, "
            . "instructors.last "
            . "FROM ciniki_course_offerings AS offerings "
            . "LEFT JOIN ciniki_course_offering_instructors AS oi ON ("
                . "offerings.id = oi.offering_id "
                . "AND oi.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "LEFT JOIN ciniki_course_instructors AS instructors ON ("
                . "oi.instructor_id = instructors.id "
                . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND offerings.status = 10 "
            . "AND (offerings.webflags&0x01) = 0 "    // Visible online
            . "ORDER BY offerings.id, instructors.first, instructors.last "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'offerings', 'fname'=>'id', 'fields'=>array('id')),
            array('container'=>'instructors', 'fname'=>'instructor_id', 'fields'=>array('id'=>'instructor_id', 'first', 'last')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.113', 'msg'=>'Unable to load offerings', 'err'=>$rc['err']));
        }
        $instructors = isset($rc['offerings']) ? $rc['offerings'] : array();
    } else {
        $instructors = array();
    }

/*        $strsql = "SELECT id, name, available_to, unit_amount "
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
        } */
    //
    // Get the course prices
    // FIXME: This is not an efficient way to get this information.
    //
    if( isset($settings['page-courses-list-include-prices']) && $settings['page-courses-list-include-prices'] == 'yes' ) {
        $strsql = "SELECT offerings.id, " 
            . "prices.id AS price_id, "
            . "prices.name, "
            . "prices.available_to, "
            . "prices.unit_amount "
            . "FROM ciniki_course_offerings AS offerings "
            . "LEFT JOIN ciniki_course_offering_prices AS prices ON ("
                . "offerings.id = prices.offering_id "
                . "AND (prices.webflags&0x01) = 0 "
                . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND offerings.status = 10 "
            . "AND (offerings.webflags&0x01) = 0 "    // Visible online
            . "ORDER BY offerings.id, prices.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'offerings', 'fname'=>'id', 'fields'=>array('id')),
            array('container'=>'prices', 'fname'=>'price_id', 'fields'=>array('id'=>'price_id', 'name', 'available_to', 'unit_amount')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.114', 'msg'=>'Unable to load offerings', 'err'=>$rc['err']));
        }
        $prices = isset($rc['offerings']) ? $rc['offerings'] : array();
    } else {
        $prices = array();
    }

    //
    // Load the course listings
    //
    $strsql = "SELECT ciniki_course_offerings.id, "
        . "ciniki_course_offerings.course_id, "
        . "ciniki_course_offerings.permalink, "
        . "ciniki_course_offerings.condensed_date, "
        . "ciniki_course_offerings.code AS offering_code, "
        . "ciniki_courses.primary_image_id, "
        . "ciniki_courses.category, "
        . "ciniki_courses.permalink AS course_permalink, "
        . "ciniki_courses.name AS course_name, "
        . "ciniki_courses.level, "
        . "IF(ciniki_courses.long_description='', 'no', 'yes') AS is_details, "
        . "ciniki_courses.code, "
        . "IFNULL(DATE_FORMAT(MIN(ciniki_course_offering_classes.class_date), '%a %b %e, %Y'), 'No dates set') AS start_date, "
        . "UNIX_TIMESTAMP(MIN(ciniki_course_offering_classes.class_date)) AS start_date_ts, "
        . "DATE_FORMAT(MAX(ciniki_course_offering_classes.class_date), '%a %b %e, %Y') AS end_date, "
        . "UNIX_TIMESTAMP(MAX(ciniki_course_offering_classes.class_date)) AS end_date_ts, "
        . "ciniki_courses.short_description "
        . "FROM ciniki_course_offerings "
        . "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
            . "";
    if( $type != '' ) {
        if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x10) ) {
            $strsql .= "AND ciniki_courses.type = '" . ciniki_core_dbQuote($ciniki, $type) . "' ";
        } else {
            $strsql .= "AND ciniki_courses.category = '" . ciniki_core_dbQuote($ciniki, $type) . "' ";
        }
    }
    $strsql .= "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "') "
        . "LEFT JOIN ciniki_course_offering_classes ON (ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
            . "AND ciniki_course_offering_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "') "
        . "WHERE ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_course_offerings.status = 10 "
        . "AND (ciniki_course_offerings.webflags&0x01) = 0 "    // Visible online
        . "GROUP BY ciniki_course_offerings.id "
        . "";
    if( $when == 'upcoming' ) {
        $strsql .= "HAVING start_date = 'No dates set' OR start_date_ts > UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
            . "ORDER BY ciniki_courses.category, start_date_ts, LENGTH(ciniki_courses.code), ciniki_courses.code, ciniki_course_offerings.code, ciniki_courses.name "
            . "";
    } elseif( $when == 'upcomingcurrent' ) {
        $strsql .= "HAVING start_date = 'No dates set' OR end_date_ts >= UNIX_TIMESTAMP(DATE(UTC_TIMESTAMP())) "
            . "ORDER BY ciniki_courses.category, start_date_ts, LENGTH(ciniki_courses.code), ciniki_courses.code, ciniki_course_offerings.code, ciniki_courses.name "
            . "";
    } elseif( $when == 'current' ) {
        $strsql .= "HAVING start_date_ts <= UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
            . "AND end_date_ts >= UNIX_TIMESTAMP(DATE(UTC_TIMESTAMP())) "
            . "ORDER BY ciniki_courses.category, start_date_ts, LENGTH(ciniki_courses.code), ciniki_courses.code, ciniki_course_offerings.code, ciniki_courses.name "
            . "";
    } elseif( $when == 'currentpast' ) {
        $strsql .= "HAVING start_date_ts < UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
            . "ORDER BY ciniki_courses.category, start_date_ts, LENGTH(ciniki_courses.code), ciniki_courses.code, ciniki_course_offerings.code, ciniki_courses.name "
            . "";
    } elseif( $when == 'past' ) {
        $strsql .= "HAVING end_date_ts < UNIX_TIMESTAMP(DATE(UTC_TIMESTAMP())) "
            . "ORDER BY ciniki_courses.category, start_date_ts DESC, LENGTH(ciniki_courses.code), ciniki_courses.code, ciniki_course_offerings.code, ciniki_courses.name "
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'categories', 'fname'=>'category', 
            'fields'=>array('name'=>'category')),
        array('container'=>'list', 'fname'=>'id', 
            'fields'=>array('id', 'course_id', 'course_name', 'level', 
                'course_permalink', 'permalink', 'code', 'offering_code', 'image_id'=>'primary_image_id', 
                'start_date', 'end_date', 'short_description', 'subtitle'=>'condensed_date', 'is_details')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'ok', 'categories'=>array());
    }

    foreach($rc['categories'] as $cid => $cat) {
        foreach($cat['list'] as $lid => $item) {
            if( isset($instructors[$item['id']]['instructors']) ) {
                $names = '';
                foreach($instructors[$item['id']]['instructors'] as $instructor) {
                    $names .= ($names != '' ? ', ' : '') . $instructor['first'] . ' ' . $instructor['last'];
                }
                if( count($instructors[$item['id']]['instructors']) == 1 ) {
                    $rc['categories'][$cid]['list'][$lid]['subtitle2'] = 'Instructor: ' . $names;
                } elseif( count($instructors[$item['id']]['instructors']) > 1 ) {
                    $rc['categories'][$cid]['list'][$lid]['subtitle2'] = 'Instructors: ' . $names;
                }
            }
            $price_list = '';
            if( isset($prices[$item['id']]['prices']) ) {
                foreach($prices[$item['id']]['prices'] as $pid => $price) {
                    //
                    // Check if online registrations enabled
                    //
                    $price_list .= ($price_list != '' ? '<br/>' : '') . $price['name'] . ' $' . number_format($price['unit_amount'], 2);
                }
                if( $price_list != '' ) {
                    $rc['categories'][$cid]['list'][$lid]['prices'] = $price_list;
                }
            }
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x01) && $item['code'] != '' ) {
                $rc['categories'][$cid]['list'][$lid]['title'] = $item['code'] . ' - ' . $item['course_name'];
            } elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x20) && $item['offering_code'] != '' 
                && (!isset($settings['page-courses-hide-codes']) || $settings['page-courses-hide-codes'] != 'yes')
                ) {
                $rc['categories'][$cid]['list'][$lid]['title'] = $item['offering_code'] . ' - ' . $item['course_name'];
            } else {
                $rc['categories'][$cid]['list'][$lid]['title'] = $item['course_name'];
            }
            if( isset($settings['page-courses-level-display']) 
                && $settings['page-courses-level-display'] == 'yes' 
                && isset($item['level']) && $item['level'] != ''
                ) {
                $rc['categories'][$cid]['list'][$lid]['title'] .= ' - ' . $item['level'];
            }

            if( $item['is_details'] == 'yes' ) {
                $rc['categories'][$cid]['list'][$lid]['permalink'] = $item['course_permalink'] . '/' . $item['permalink'];
            } else {
                $rc['categories'][$cid]['list'][$lid]['permalink'] = '';
            }
        }
    }

    return array('stat'=>'ok', 'categories'=>$rc['categories']);
}
?>
