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
function ciniki_courses_web_courseList($ciniki, $settings, $business_id, $type, $when) {

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
        . "IF(ciniki_courses.long_description='', 'no', 'yes') AS isdetails, "
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
        $strsql .= "AND ciniki_courses.type = '" . ciniki_core_dbQuote($ciniki, $type) . "' ";
    }
    $strsql .= "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
        . "LEFT JOIN ciniki_course_offering_classes ON (ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
            . "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
        . "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
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
        array('container'=>'offerings', 'fname'=>'id', 
            'fields'=>array('id', 'course_id', 'name'=>'course_name', 'level', 
                'course_permalink', 'permalink', 'code', 'offering_code', 'image_id'=>'primary_image_id', 
                'start_date', 'end_date', 'short_description', 'condensed_date', 'isdetails')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['categories']) ) {
        return array('stat'=>'ok', 'categories'=>array());
    }

    return array('stat'=>'ok', 'categories'=>$rc['categories']);
}
?>
