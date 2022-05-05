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
function ciniki_courses_instructorList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'stats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Stats'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.instructorList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'maps');
    $rc = ciniki_courses_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of instructors
    //
    $strsql = "SELECT instructors.id, "
        . "instructors.status, "
        . "instructors.status AS status_text, "
        . "IFNULL(customers.display_name, CONCAT_WS(' ', instructors.first, instructors.last)) AS name, "
        . "IF((instructors.webflags&0x01)=0, 'Visible', 'Hidden') AS website_text, "
        . "COUNT(offerings.id) AS num_offerings, "
        . "MAX(offerings.end_date) AS last_offering "
        . "FROM ciniki_course_instructors AS instructors "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "instructors.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_offering_instructors AS oi ON ("
            . "instructors.id = oi.instructor_id "
            . "AND oi.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_offerings AS offerings ON ("
            . "oi.offering_id = offerings.id "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] > 0 ) {
        $strsql .= "AND instructors.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    $strsql .= "GROUP BY instructors.id "
        . "ORDER BY instructors.last, instructors.first "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'instructors', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'status_text', 'website_text', 'num_offerings', 'last_offering'),
            'maps'=>array('status_text'=>$maps['instructor']['status']),
            'dtformat'=>array('last_offering'=>$date_format),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.125', 'msg'=>'Unable to load instructors', 'err'=>$rc['err']));
    }
    if( isset($rc['instructors']) ) {
        $instructors = $rc['instructors'];
        $instructors_ids = array();
        foreach($instructors as $k => $v) {
            $instructors_ids[] = $v['id'];
        }
    } else {
        $instructors = array();
        $instructors_ids = array();
    }

    $rsp = array('stat'=>'ok', 'instructors'=>$instructors);

    if( isset($args['stats']) && $args['stats'] == 'yes' ) {
        $strsql = "SELECT status AS label, status AS value, COUNT(*) AS num_instructors "
            . "FROM ciniki_course_instructors AS instructors "
            . "WHERE instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY status "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'stats', 'fname'=>'value', 
                'fields'=>array('label', 'value', 'num_instructors'),
                'maps'=>array('label'=>$maps['instructor']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.154', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $stats = isset($rc['stats']) ? $rc['stats'] : array();
        $count = 0;
        foreach($stats as $item) {  
            $count += $item['num_instructors'];
        }
        array_unshift($stats, array(
            'label' => 'All',
            'value' => '__',
            'num_instructors' => $count,
            ));
        $rsp['statuses'] = $stats;
    }

    return $rsp;
}
?>
