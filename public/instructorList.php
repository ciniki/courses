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
        'level'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
        'medium'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Medium'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'), 
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
        . "instructors.rating, "
        . "instructors.hourly_rate, "
        . "IFNULL(customers.display_name, CONCAT_WS(' ', instructors.first, instructors.last)) AS name, "
        . "IF((instructors.webflags&0x01)=0, 'Visible', 'Hidden') AS website_text, "
        . "COUNT(offerings.id) AS num_offerings, "
        . "MAX(offerings.end_date) AS last_offering "
        . "FROM ciniki_course_instructors AS instructors ";
    if( isset($args['level']) && $args['level'] != '' ) {
        $strsql .= "INNER JOIN ciniki_course_instructor_tags AS levels ON ("
            . "instructors.id = levels.instructor_id "
            . "AND levels.tag_type = 10 "
            . "AND levels.permalink = '" . ciniki_core_dbQuote($ciniki, $args['level']) . "' "
            . "AND levels.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    if( isset($args['medium']) && $args['medium'] != '' ) {
        $strsql .= "INNER JOIN ciniki_course_instructor_tags AS mediums ON ("
            . "instructors.id = mediums.instructor_id "
            . "AND mediums.tag_type = 20 "
            . "AND mediums.permalink = '" . ciniki_core_dbQuote($ciniki, $args['medium']) . "' "
            . "AND mediums.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    if( isset($args['type']) && $args['type'] != '' ) {
        $strsql .= "INNER JOIN ciniki_course_instructor_tags AS types ON ("
            . "instructors.id = types.instructor_id "
            . "AND types.tag_type = 30 "
            . "AND types.permalink = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' "
            . "AND types.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    $strsql .= "LEFT JOIN ciniki_customers AS customers ON ("
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
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'instructors', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status', 'status_text', 'rating', 'hourly_rate', 'website_text', 
                'num_offerings', 'last_offering',
                ),
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
            $instructors[$k]['hourly_rate_display'] = $v['hourly_rate'] != 0 ? '$' . number_format($v['hourly_rate'], 2) : '';
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

        //
        // Get the tags and counts
        //
        $strsql = "SELECT tags.tag_type, "
            . "tags.tag_name, "
            . "tags.permalink, "
            . "COUNT(tags.instructor_id) AS num_instructors "
            . "FROM ciniki_course_instructor_tags AS tags ";
/*        if( isset($args['status']) && $args['status'] > 0 ) {
            $strsql .= "INNER JOIN ciniki_course_instructors AS instructors ON ("
                . "tags.instructor_id = instructors.id "
                . "AND instructors.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' " 
                . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        } */
        $strsql .= "WHERE tags.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY tag_type, permalink "
            . "ORDER BY tag_type, tag_name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'types', 'fname'=>'tag_type', 
                'fields'=>array('tag_type'),
                ),
            array('container'=>'tags', 'fname'=>'permalink', 
                'fields'=>array('label'=>'tag_name', 'value'=>'permalink', 'num_instructors'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $rsp['levels'] = array();
        $rsp['mediums'] = array();
        $rsp['types'] = array();

        if( isset($rc['types']) ) {
            foreach($rc['types'] as $tag_types) {
                if( $tag_types['tag_type'] == 10 ) {
                    $rsp['levels'] = $tag_types['tags'];
                } elseif( $tag_types['tag_type'] == 20 ) {
                    $rsp['mediums'] = $tag_types['tags'];
                } elseif( $tag_types['tag_type'] == 30 ) {
                    $rsp['types'] = $tag_types['tags'];
                }
            }
        }

        array_unshift($rsp['levels'], array(
            'label' => 'All',
            'value' => '',
            ));
        array_unshift($rsp['mediums'], array(
            'label' => 'All',
            'value' => '',
            ));
        array_unshift($rsp['types'], array(
            'label' => 'All',
            'value' => '',
            ));
    }

    return $rsp;
}
?>
