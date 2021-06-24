<?php
//
// Description
// -----------
// This method will return the list of Courses for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Course for.
//
// Returns
// -------
//
function ciniki_courses_courseList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'stats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Request Stats'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'level'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'),
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'),
        'medium'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Medium'),
        'ages'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ages'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.courseList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $fields = array(
        'status' => 'statuses',
        'level' => 'levels',
        'category' => 'categories',
        'type' => 'types',
        'medium' => 'mediums',
        'ages' => 'ages',
        );

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
    // Setup the filter sql
    //
    $filter_sql = '';
    if( isset($args['status']) && $args['status'] != '__' ) {
        $filter_sql .= "AND courses.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    if( isset($args['level']) && $args['level'] != '__' ) {
        $filter_sql .= "AND courses.level = '" . ciniki_core_dbQuote($ciniki, $args['level']) . "' ";
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x4000) && isset($args['category']) && $args['category'] != '__' ) {
        $filter_sql .= "AND courses.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x10) && isset($args['type']) && $args['type'] != '__' ) {
        $filter_sql .= "AND courses.type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x1000) && isset($args['medium']) && $args['medium'] != '__' ) {
        $filter_sql .= "AND courses.medium = '" . ciniki_core_dbQuote($ciniki, $args['medium']) . "' ";
    }
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x2000) && isset($args['ages']) && $args['ages'] != '__' ) {
        $filter_sql .= "AND courses.ages = '" . ciniki_core_dbQuote($ciniki, $args['ages']) . "' ";
    }

    //
    // Get the list of courses
    //
    $strsql = "SELECT courses.id, "
        . "courses.name AS course_name, "
        . "courses.code AS course_code, "
        . "courses.permalink, "
        . "courses.status, "
        . "courses.status AS status_text, "
        . "courses.level, "
        . "courses.type, "
        . "courses.category, "
        . "courses.flags, "
        . "courses.medium, "
        . "courses.ages, "
        . "IFNULL(MIN(offerings.start_date), '') AS start_date, "
        . "IFNULL(MAX(offerings.start_date), '') AS end_date "
        . "FROM ciniki_courses AS courses "
        . "LEFT JOIN ciniki_course_offerings AS offerings ON ("
            . "courses.id = offerings.course_id "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . $filter_sql
        . "GROUP BY courses.id "
        . "ORDER BY courses.code, courses.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'courses', 'fname'=>'id', 
            'fields'=>array('id', 'course_name', 'course_code', 'permalink', 'status', 'status_text', 
                'level', 'type', 'category', 'medium', 'ages', 'flags',
                'start_date', 'end_date',
                ),
            'dtformat'=>array('start_date'=>$date_format,
                'end_date'=>$date_format,
                ),
            'maps'=>array('status_text'=>$maps['course']['status'],),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['courses']) ) {
        $courses = $rc['courses'];
        $course_ids = array();
        foreach($courses as $iid => $course) {
            $course_ids[] = $course['id'];
        }
    } else {
        $courses = array();
        $course_ids = array();
    }

    $rsp = array('stat'=>'ok', 'courses'=>$courses, 'nplist'=>$course_ids);

    if( !isset($args['stats']) || $args['stats'] != 'yes' ) {
        return $rsp;
    }

    //
    // Get the stats
    //
    foreach($fields as $field => $plural) {
        
        $strsql = "SELECT courses.{$field} AS label, courses.{$field} AS value, COUNT(*) AS num_courses "
            . "FROM ciniki_courses AS courses "
            . "WHERE courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        if( $field != 'status' && isset($args['status']) && $args['status'] != '__' ) {
            $strsql .= "AND courses.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
        }
        if( $field != 'level' && isset($args['level']) && $args['level'] != '__' ) {
            $strsql .= "AND courses.level = '" . ciniki_core_dbQuote($ciniki, $args['level']) . "' ";
        }
        if( $field != 'category' && ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x4000) 
            && isset($args['category']) && $args['category'] != '__' 
            ) {
            $strsql .= "AND courses.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
        }
        if( $field != 'type' && ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x10) 
            && isset($args['type']) && $args['type'] != '__' 
            ) {
            $strsql .= "AND courses.type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
        }
        if( $field != 'medium' && ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x1000) 
            && isset($args['medium']) && $args['medium'] != '__'
            ) {
            $strsql .= "AND courses.medium = '" . ciniki_core_dbQuote($ciniki, $args['medium']) . "' ";
        }
        if( $field != 'ages' && ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x2000) 
            && isset($args['ages']) && $args['ages'] != '__' 
            ) {
            $strsql .= "AND courses.ages = '" . ciniki_core_dbQuote($ciniki, $args['ages']) . "' ";
        }
        $strsql .= "GROUP BY {$field} "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'stats', 'fname'=>'value', 
                'fields'=>array('label', 'value', 'num_courses'),
                'maps'=>array('label'=>$maps['course']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.127', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $stats = isset($rc['stats']) ? $rc['stats'] : array();
        $count = 0;
        foreach($stats as $item) {  
            $count += $item['num_courses'];
        }
        array_unshift($stats, array(
            'label' => 'All',
            'value' => '__',
            'num_courses' => $count,
            ));
        $rsp[$plural] = $stats;
    }
    
    return $rsp;
}
?>
