<?php
//
// Description
// -----------
// This method will return the list of Course Offerings for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Course Offering for.
//
// Returns
// -------
//
function ciniki_courses_offeringList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'year'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Year'),
        'stats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Request Stats'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load the date format strings for the user
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'maps');
    $rc = ciniki_courses_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    $now = new DateTime('now', new DateTimezone($intl_timezone));
    $recent_dt = clone $now;
    $recent_dt->sub(new DateInterval('P1M'));

    //
    // Get the list of offerings
    //
    $strsql = "SELECT offerings.id, "
        . "offerings.course_id, "
        . "IFNULL(courses.code, '??') AS course_code, "
        . "IFNULL(courses.name, '??') AS course_name, "
        . "IFNULL(offerings.permalink, '') AS course_permalink, "
        . "offerings.name AS offering_name, "
        . "offerings.code AS offering_code, "
        . "offerings.permalink AS offering_permalink, "
        . "offerings.status, "
        . "offerings.webflags, "
        . "offerings.start_date, "
        . "offerings.end_date, "
        . "offerings.condensed_date, "
        . "offerings.reg_flags, "
        . "offerings.num_seats, "
        . "COUNT(DISTINCT registrations.id) AS num_registrations "
        . "FROM ciniki_course_offerings AS offerings "
        . "LEFT JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_offering_registrations AS registrations ON ("
            . "offerings.id = registrations.offering_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    if( isset($args['status']) && $args['status'] != 'all' && $args['status'] != '__' ) {
        // Only want active offerings where course is also active
        if( $args['status'] == '10' ) {
            $strsql .= "AND courses.status < 90 ";
        }
        $strsql .= "AND offerings.status = '" . ciniki_core_dbQuote($ciniki, $args['status']) . "' ";
    }
    if( isset($args['year']) && $args['year'] != 'all' ) {
        if( $args['year'] == 'upcoming' ) {
            $strsql .= "AND offerings.start_date > '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
                . "AND offerings.status < 90 "
                . "AND courses.status < 90 "
                . "";
        } elseif( $args['year'] == 'current' ) {
            $strsql .= "AND offerings.start_date <= '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
                . "AND offerings.end_date >= '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
                . "AND offerings.status < 90 "
                . "AND courses.status < 90 "
                . "";
        } elseif( $args['year'] == 'recent' ) {
            $strsql .= "AND offerings.end_date > '" . ciniki_core_dbQuote($ciniki, $recent_dt->format('Y-m-d')) . "' "
                . "AND offerings.end_date < '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
                . "";
        } else {
            $strsql .= "AND YEAR(offerings.end_date) = '" . ciniki_core_dbQuote($ciniki, $args['year']) . "' ";
        }
    } 
    $strsql .= "GROUP BY offerings.id "
        . "ORDER BY course_code, offering_code, course_name, offering_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'id', 
            'fields'=>array('id', 'course_id', 'course_code', 'course_name', 'offering_name', 'offering_code', 
                'course_permalink', 'offering_permalink', 'status', 'status_text'=>'status', 'webflags', 'condensed_date', 
                'start_date', 'end_date', 
                'reg_flags', 'num_seats', 'num_registrations',
                ),
            'maps'=>array('status_text'=>$maps['offering']['status']),
            'dtformat'=>array('start_date'=>$date_format,
                'end_date'=>$date_format,
                ),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $offerings = isset($rc['offerings']) ? $rc['offerings'] : array();
    $offering_ids = array();
    foreach($offerings as $iid => $offering) {
        $offering_ids[] = $offering['id'];
    }

    $rsp = array('stat'=>'ok', 'offerings'=>$offerings, 'nplist'=>$offering_ids);

    //
    // Get the stats
    //
    if( isset($args['stats']) && $args['stats'] == 'yes' ) {
        //
        // To be considered active, the course and offering need to both have active status
        //
        $strsql = "SELECT IF((courses.status<90 AND offerings.status =10), 10, 90) AS value, "
            . "COUNT(*) AS num_offerings "
            . "FROM ciniki_course_offerings AS offerings "
            . "INNER JOIN ciniki_courses AS courses ON ("
                . "offerings.course_id = courses.id "
                . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY value "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'stats', 'fname'=>'value', 
                'fields'=>array('label'=>'value', 'value', 'num_offerings'),
                'maps'=>array('label'=>$maps['offering']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.127', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $stats = isset($rc['stats']) ? $rc['stats'] : array();
        $count = 0;
        foreach($stats as $item) {  
            $count += $item['num_offerings'];
        }
        array_unshift($stats, array(
            'label' => 'All',
            'value' => '__',
            'num_offerings' => $count,
            ));
        $rsp['statuses'] = $stats;

        //
        // The year of the end_date for offering
        //
        $strsql = "SELECT YEAR(offerings.end_date) AS label, YEAR(offerings.end_date) AS value, COUNT(*) AS num_offerings "
            . "FROM ciniki_course_offerings AS offerings "
            . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "GROUP BY label "
            . "HAVING label > 0 "
            . "ORDER BY label DESC "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'stats', 'fname'=>'value', 
                'fields'=>array('label', 'value', 'num_offerings'),
                'maps'=>array('label'=>$maps['course']['status']),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.155', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $rsp['years'] = isset($rc['stats']) ? $rc['stats'] : array();

        //
        // Get the recent
        //
        $strsql = "SELECT COUNT(*) AS num_offerings "
            . "FROM ciniki_course_offerings AS offerings "
            . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND end_date > '" . ciniki_core_dbQuote($ciniki, $recent_dt->format('Y-m-d')) . "' "
            . "AND end_date <= '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.courses', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.157', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        $num_items = isset($rc['num']) ? $rc['num'] : '';

        array_unshift($rsp['years'], array(
            'label' => 'Recent',
            'value' => 'recent',
            'num_offerings' => $num_items,
            ));

        //
        // Get the current
        //
        $strsql = "SELECT COUNT(*) AS num_offerings "
            . "FROM ciniki_course_offerings AS offerings "
            . "INNER JOIN ciniki_courses AS courses ON ("
                . "offerings.course_id = courses.id "
                . "AND courses.status < 90 "
                . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND offerings.status < 90 "
            . "AND start_date <= '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
            . "AND end_date >= '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.courses', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.158', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        $num_items = isset($rc['num']) ? $rc['num'] : '';

        array_unshift($rsp['years'], array(
            'label' => 'Current',
            'value' => 'current',
            'num_offerings' => $num_items,
            ));

        //
        // Get the upcoming
        //
        $strsql = "SELECT COUNT(*) AS num_offerings "
            . "FROM ciniki_course_offerings AS offerings "
            . "INNER JOIN ciniki_courses AS courses ON ("
                . "offerings.course_id = courses.id "
                . "AND courses.status < 90 "
                . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND offerings.status < 90 "
            . "AND start_date > '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbSingleCount');
        $rc = ciniki_core_dbSingleCount($ciniki, $strsql, 'ciniki.courses', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.145', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        $num_items = isset($rc['num']) ? $rc['num'] : '';

        array_unshift($rsp['years'], array(
            'label' => 'Upcoming',
            'value' => 'upcoming',
            'num_offerings' => $num_items,
            ));

    }

    return $rsp;
}
?>
