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
    //
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
            . "ORDER BY ciniki_courses.category, start_date_ts, LENGTH(ciniki_courses.code), ciniki_courses.code, ciniki_course_offerings.code, ciniki_courses.name "
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
            if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x01) && $item['code'] != '' ) {
                $rc['categories'][$cid]['list'][$lid]['title'] = $item['code'] . ' - ' . $item['course_name'];
            } elseif( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x20) && $item['offering_code'] != '' ) {
                $rc['categories'][$cid]['list'][$lid]['title'] = $item['offering_code'] . ' - ' . $item['course_name'];
            } else {
                $rc['categories'][$cid]['list'][$lid]['title'] = $item['course_name'];
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
