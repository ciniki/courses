<?php
//
// Description
// -----------
// This method will return the list of course offerings for a business.  
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to get files from.
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
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'current'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Current'),
        'upcoming'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Upcoming'),
        'past'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Past'),
        'files'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Files'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    if( (!isset($args['current']) || $args['current'] != 'yes')
        && (!isset($args['upcoming']) || $args['upcoming'] != 'yes')
        && (!isset($args['past']) || $args['past'] != 'yes')
        ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1260', 'msg'=>'You must specify the type of list to return: past, current, upcoming.'));
    }
    
    //  
    // Check access to business_id as owner, or sys admin. 
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $ac = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringList');
    if( $ac['stat'] != 'ok' ) { 
        return $ac;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Query for the course offerings
    //
    $rsp = array('stat'=>'ok', 'pastyears'=>array(), 'current'=>array(), 'upcoming'=>array());
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    if( isset($args['current']) && $args['current'] == 'yes' ) {
        $strsql = "SELECT ciniki_course_offerings.id, "
            . "ciniki_course_offerings.name AS offering_name, "
            . "ciniki_course_offerings.code AS offering_code, "
            . "ciniki_course_offerings.course_id, "
            . "ciniki_courses.name AS course_name, "
            . "ciniki_courses.code, "
            . "IFNULL(DATE_FORMAT(MIN(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'No dates set') AS start_date, "
            . "UNIX_TIMESTAMP(MIN(ciniki_course_offering_classes.class_date)) AS start_date_ts, "
            . "DATE_FORMAT(MAX(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
            . "UNIX_TIMESTAMP(MAX(ciniki_course_offering_classes.class_date)) AS end_date_ts, "
            . "ciniki_course_offerings.num_seats, "
            . "COUNT(DISTINCT ciniki_course_offering_registrations.id) AS num_registrations "
            . "FROM ciniki_course_offerings "
            . "LEFT JOIN ciniki_courses ON ("
                . "ciniki_course_offerings.course_id = ciniki_courses.id "
                . "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_course_offering_classes ON ("
                . "ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
                . "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_course_offering_registrations ON ("
                . "ciniki_course_offerings.id = ciniki_course_offering_registrations.offering_id "
                . "AND ciniki_course_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_course_offerings.status = 10 "
            . "GROUP BY ciniki_course_offerings.id "
            . "HAVING start_date_ts <= UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
            . "AND end_date_ts >= UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
            . "ORDER BY ciniki_courses.code, ciniki_course_offerings.code, ciniki_courses.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'offerings', 'fname'=>'id', 
                'fields'=>array('id', 'offering_name', 'course_id', 'course_name', 'code', 'offering_code', 'start_date', 'end_date', 'num_seats', 'num_registrations')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['offerings']) ) {
            usort($rc['offerings'], function($a, $b) {
                if( $a['code'] == $b['code'] ) {
                    if( $a['offering_code'] == $b['offering_code'] ) {
                        if( $a['course_name'] == $b['course_name'] ) {
                            return strnatcasecmp($a['offering_name'], $b['offering_name']);
                        }
                        return strnatcasecmp($a['course_name'], $b['course_name']);
                    }
                    return strnatcasecmp($a['offering_code'], $b['offering_code']);
                }
                return strnatcasecmp($a['code'], $b['code']);
            });
            $rsp['current'] = $rc['offerings'];
        }
    }
    if( isset($args['upcoming']) && $args['upcoming'] == 'yes' ) {
        $strsql = "SELECT ciniki_course_offerings.id, "
            . "ciniki_course_offerings.name AS offering_name, "
            . "ciniki_course_offerings.code AS offering_code, "
            . "ciniki_course_offerings.course_id, "
            . "ciniki_courses.name AS course_name, "
            . "ciniki_courses.code, "
            . "IFNULL(DATE_FORMAT(MIN(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'No dates set') AS start_date, "
            . "UNIX_TIMESTAMP(MIN(ciniki_course_offering_classes.class_date)) AS start_date_ts, "
            . "DATE_FORMAT(MAX(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
            . "ciniki_course_offerings.num_seats, "
            . "COUNT(DISTINCT ciniki_course_offering_registrations.id) AS num_registrations "
            . "FROM ciniki_course_offerings "
            . "LEFT JOIN ciniki_courses ON ("
                . "ciniki_course_offerings.course_id = ciniki_courses.id "
                . "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_course_offering_classes ON ("
                . "ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
                . "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_course_offering_registrations ON ("
                . "ciniki_course_offerings.id = ciniki_course_offering_registrations.offering_id "
                . "AND ciniki_course_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND (ciniki_course_offerings.status = 10 || ciniki_course_offerings.status = 0 ) "
            . "GROUP BY ciniki_course_offerings.id "
            . "HAVING start_date = 'No dates set' OR start_date_ts > UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
            . "ORDER BY ciniki_courses.code, ciniki_course_offerings.code, ciniki_courses.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'offerings', 'fname'=>'id',
                'fields'=>array('id', 'offering_name', 'course_id', 'course_name', 'code', 'offering_code', 'start_date', 'end_date', 'num_seats', 'num_registrations')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['offerings']) ) {
            usort($rc['offerings'], function($a, $b) {
                if( $a['code'] == $b['code'] ) {
                    if( $a['offering_code'] == $b['offering_code'] ) {
                        if( $a['course_name'] == $b['course_name'] ) {
                            return strnatcasecmp($a['offering_name'], $b['offering_name']);
                        }
                        return strnatcasecmp($a['course_name'], $b['course_name']);
                    }
                    return strnatcasecmp($a['offering_code'], $b['offering_code']);
                }
                return strnatcasecmp($a['code'], $b['code']);
            });
            $rsp['upcoming'] = $rc['offerings'];
        }
    }
    if( isset($args['past']) && $args['past'] == 'yes' ) {
        $strsql = "SELECT ciniki_course_offerings.id, "
            . "ciniki_course_offerings.name AS offering_name, "
            . "ciniki_course_offerings.code AS offering_code, "
            . "ciniki_course_offerings.course_id, "
            . "ciniki_courses.name AS course_name, "
            . "ciniki_courses.code, "
            . "IFNULL(DATE_FORMAT(MIN(ciniki_course_offering_classes.class_date), '%Y'), '??') AS year, "
            . "IFNULL(DATE_FORMAT(MIN(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), 'No dates set') AS start_date, "
            . "UNIX_TIMESTAMP(MIN(ciniki_course_offering_classes.class_date)) AS start_date_ts, "
            . "DATE_FORMAT(MAX(ciniki_course_offering_classes.class_date), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
            . "UNIX_TIMESTAMP(MAX(ciniki_course_offering_classes.class_date)) AS end_date_ts, "
            . "ciniki_course_offerings.num_seats, "
            . "COUNT(DISTINCT ciniki_course_offering_registrations.id) AS num_registrations "
            . "FROM ciniki_course_offerings "
            . "LEFT JOIN ciniki_courses ON ("
                . "ciniki_course_offerings.course_id = ciniki_courses.id "
                . "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_course_offering_classes ON ("
                . "ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
                . "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "LEFT JOIN ciniki_course_offering_registrations ON ("
                . "ciniki_course_offerings.id = ciniki_course_offering_registrations.offering_id "
                . "AND ciniki_course_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
                . ") "
            . "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND (ciniki_course_offerings.status = 10 || ciniki_course_offerings.status = 0 ) "
            . "GROUP BY ciniki_course_offerings.id "
            . "HAVING end_date_ts < UNIX_TIMESTAMP(UTC_TIMESTAMP()) "
            . "ORDER BY year, ciniki_courses.code, ciniki_course_offerings.code, ciniki_courses.name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'years', 'fname'=>'year', 'fields'=>array('year')),
            array('container'=>'offerings', 'fname'=>'id', 
                'fields'=>array('id', 'offering_name', 'course_id', 'course_name', 'code', 'offering_code', 'start_date', 'end_date', 'num_seats', 'num_registrations')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['years']) ) {
            $rsp['pastyears'] = array();
            foreach($rc['years'] as $year) {
                usort($year['offerings'], function($a, $b) {
                    if( $a['code'] == $b['code'] ) {
                        if( $a['offering_code'] == $b['offering_code'] ) {
                            if( $a['course_name'] == $b['course_name'] ) {
                                return strnatcasecmp($a['offering_name'], $b['offering_name']);
                            }
                            return strnatcasecmp($a['course_name'], $b['course_name']);
                        }
                        return strnatcasecmp($a['offering_code'], $b['offering_code']);
                    }
                    return strnatcasecmp($a['code'], $b['code']);
                });
                $rsp['pastyears'][$year['year']] = $year['offerings'];
            }
        }
    }

    if( isset($args['files']) && $args['files'] == 'yes' ) {
        //
        // Load the list of members for an courses
        //
        $strsql = "SELECT ciniki_course_files.id, "
            . "ciniki_course_files.type, "
            . "ciniki_course_files.type AS type_id, "
            . "ciniki_course_files.name, "
            . "ciniki_course_files.description, "
            . "ciniki_course_files.permalink "
            . "FROM ciniki_course_files "
            . "WHERE ciniki_course_files.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND type = '2' "
            . "ORDER BY type, publish_date DESC, name "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'files', 'fname'=>'id', 'name'=>'file', 'fields'=>array('id', 'name', 'permalink', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['files']) ) {
            $rsp['files'] = $rc['files'];
        }
    }

    return $rsp;
}
?>
