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
// class_id:            The ID of the class to get.
//
// Returns
// -------
//
function ciniki_courses_offeringClassGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'class_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'),
        'offering_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Offering'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringClassGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki);

    if( $args['class_id'] == 0 ) {
        if( isset($args['offering_id']) && $args['offering_id'] > 0 ) {
            $strsql = "SELECT id, class_date, "
                . "DATE_FORMAT(start_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "') AS start_time, "
                . "DATE_FORMAT(end_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "') AS end_time "
                . "FROM ciniki_course_offering_classes "
                . "WHERE offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
                . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY class_date DESC "
                . "LIMIT 2 "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'classes', 'fname'=>'id', 'fields'=>array('class_date', 'start_time', 'end_time')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.147', 'msg'=>'Unable to load classes', 'err'=>$rc['err']));
            }
            $classes = isset($rc['classes']) ? $rc['classes'] : array();
            if( isset($classes[1]['class_date']) ) {
                $dt1 = new DateTime($classes[0]['class_date'] . ' 12:00:00'); 
                $dt2 = new DateTime($classes[1]['class_date'] . ' 12:00:00'); 
                $dt1->add($dt2->diff($dt1));
            }
        }

        $class = array(
            'class_date' => (isset($dt1) ? $dt1->format('D M j, Y') : ''),
            'start_time' => (isset($classes[0]['start_time']) ? $classes[0]['start_time'] : ''),
            'end_time' => (isset($classes[0]['end_time']) ? $classes[0]['end_time'] : ''),
            );
    } 
    else {
        //
        // Get the main information
        //
        $strsql = "SELECT ciniki_course_offering_classes.id, "
            . "ciniki_course_offering_classes.class_date, "
            . "IFNULL(DATE_FORMAT(ciniki_course_offering_classes.start_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS start_time, "
            . "IFNULL(DATE_FORMAT(ciniki_course_offering_classes.end_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS end_time, "
            . "ciniki_course_offering_classes.notes "
            . "FROM ciniki_course_offering_classes "
            . "WHERE ciniki_course_offering_classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_offering_classes.id = '" . ciniki_core_dbQuote($ciniki, $args['class_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'classes', 'fname'=>'id', 'name'=>'class',
                'fields'=>array('id', 'class_date', 'start_time', 'end_time', 'notes'),
                'dtformat'=>array('class_date'=>$date_format),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['classes']) ) {
            return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.courses.31', 'msg'=>'Unable to find class'));
        }
        $class = $rc['classes'][0];
    }
    
    return array('stat'=>'ok', 'class'=>$class);
}
?>
