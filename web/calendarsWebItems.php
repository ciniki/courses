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
function ciniki_courses_web_calendarsWebItems($ciniki, $settings, $tnid, $args) {

    if( !isset($args['ltz_start']) || !is_a($args['ltz_start'], 'DateTime') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.97', 'msg'=>'Invalid start date'));
    }
    if( !isset($args['ltz_end']) || !is_a($args['ltz_end'], 'DateTime') ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.98', 'msg'=>'Invalid end date'));
    }

    $sdt = $args['ltz_start'];
    $edt = $args['ltz_end'];

    if( isset($ciniki['tenant']['module_pages']['ciniki.courses.active']['base_url']) ) {
        $base_url = $ciniki['tenant']['module_pages']['ciniki.courses.active']['base_url'];
    } elseif( isset($ciniki['tenant']['module_pages']['ciniki.courses']['base_url']) ) {
        $base_url = $ciniki['tenant']['module_pages']['ciniki.courses']['base_url'] . '/course';
    } else {
        $base_url = '/courses/course';
    }

    //
    // Check if colours specified
    //
    $style = '';
    if( isset($settings['ciniki-courses-colour-background']) && $settings['ciniki-courses-colour-background'] != '' ) {
        $style .= ($style != '' ? ' ':'') . 'background: ' . $settings['ciniki-courses-colour-background'] . ';';
    }
    if( isset($settings['ciniki-courses-colour-border']) && $settings['ciniki-courses-colour-border'] != '' ) {
        $style .= ($style != '' ? ' ':'') . ' border: 1px solid ' . $settings['ciniki-courses-colour-border'] . ';';
    }
    if( isset($settings['ciniki-courses-colour-font']) && $settings['ciniki-courses-colour-font'] != '' ) {
        $style .= ($style != '' ? ' ':'') . ' color: ' . $settings['ciniki-courses-colour-font'] . ';';
    }

    //
    // Setup the legend
    //
    if( isset($settings['ciniki-courses-legend-title']) && $settings['ciniki-courses-legend-title'] != '' ) {
        $legend = array(
            array('title'=>$settings['ciniki-courses-legend-title'], 'style'=>$style)
            );
    } else {
        $legend = array();
    }

    //
    // FIXME: Add select for tags to get other colours on web
    //

    //
    // Get the list of classes for the calendar
    //
    $strsql = "SELECT ciniki_course_offering_classes.id, "
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
            . "AND ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_courses ON ("
            . "ciniki_course_offerings.course_id = ciniki_courses.id "
            . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ciniki_course_offering_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
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
    if( isset($settings['ciniki-courses-class-prefix']) ) {
        $prefix = $settings['ciniki-courses-class-prefix'];
    }

    $items = array();
    if( isset($rc['items']) ) {
        foreach($rc['items'] as $class) {
            $item = array(
                'title'=>$prefix . $class['title'],
                'time_text'=>'',
                'style'=>$style,
                'url'=>$base_url . '/' . $class['course_permalink'] . '/' . $class['offering_permalink'],
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

    return array('stat'=>'ok', 'items'=>$items, 'legend'=>$legend);
}
?>
