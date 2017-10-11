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
function ciniki_courses_web_calendarsWebItems($ciniki, $settings, $business_id, $args) {

    if( !isset($args['ltz_start']) || !is_a($args['ltz_start'], 'DateTime') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.97', 'msg'=>'Invalid start date'));
    }
    if( !isset($args['ltz_end']) || !is_a($args['ltz_end'], 'DateTime') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.98', 'msg'=>'Invalid end date'));
    }

    $sdt = $args['ltz_start'];
    $edt = $args['ltz_end'];

    if( isset($ciniki['business']['module_pages']['ciniki.courses']['base_url']) ) {
        $base_url = $ciniki['business']['module_pages']['ciniki.courses']['base_url'];
    } else {
        $base_url = '/courses';
    }

    //
    // FIXME: Add select for tags to get other colours on web
    //

    //
    // Get the list of classes for the calendar
    //
    $strsql = "SELECT ciniki_courses.id, "
        . "ciniki_courses.name, "
        . "ciniki_courses.permalink AS course_permalink, "
        . "ciniki_course_offerings.permalink AS offering_permalink, "
        . "DATE_FORMAT(ciniki_course_offering_classes.class_date, '%Y-%m-%d') AS class_date, "
        . "TIME_FORMAT(ciniki_course_offering_classes.start_time, '%l:%i %p') AS start_time, "
        . "TIME_FORMAT(ciniki_course_offering_classes.end_time, '%l:%i %p') AS end_time "
        . "FROM ciniki_course_offering_classes "
        . "INNER JOIN ciniki_course_offerings ON ("
            . "ciniki_course_offering_classes.offering_id = ciniki_course_offerings.id "
            . "AND (ciniki_course_offerings.webflags&0x01) = 0 "
            . "AND ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "INNER JOIN ciniki_courses ON ("
            . "ciniki_course_offerings.course_id = ciniki_courses.id "
            . "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
            . ") "
        . "WHERE ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_course_offering_classes.class_date >= '" . ciniki_core_dbQuote($ciniki, $sdt->format('Y-m-d')) . "' "
        . "AND ciniki_course_offering_classes.class_date <= '" . ciniki_core_dbQuote($ciniki, $edt->format('Y-m-d')) . "' "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'items', 'fname'=>'id', 
            'fields'=>array('id', 'title'=>'name', 'class_date', 'course_permalink', 'offering_permalink', 'start_time', 'end_time')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $prefix = '';
//    if( isset($settings['ciniki-artgallery-exhibition-prefix']) ) {
//        $prefix = $settings['ciniki-artgallery-exhibition-prefix'];
//    }
http://ciniki.local/gibsoncentre/school-of-the-arts/course/mixed-media-madness-grades-5-8/fall-8-week-sessions-2017fall-8-week-2017
    $items = array();
    if( isset($rc['items']) ) {
        foreach($rc['items'] as $class) {
            $item = array(
                'title'=>$prefix . $class['title'],
                'time_text'=>'',
                'url'=>$base_url . '/course/' . $class['course_permalink'] . '/' . $class['offering_permalink'],
                'classes'=>array('courses'),
                );
            if( isset($settings['ciniki-courses-display-times']) ) {
                if( $settings['ciniki-courses-display-times'] == 'startend' ) {
                    $item['time_text'] = $class['start_time'] . ' - ' . $class['end_time'];
                } elseif( $settings['ciniki-courses-display-times'] == 'start' ) {
                    $item['time_text'] = $class['start_time'];
                }
            }
            if( !isset($items[$class['class_date']]) ) {
                $items[$class['class_date']]['items'] = array();
            }
            $items[$class['class_date']]['items'][] = $item;

        }
    }

    return array('stat'=>'ok', 'items'=>$items);
}
?>
