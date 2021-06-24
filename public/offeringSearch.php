<?php
//
// Description
// -----------
// This method searchs for a Course Offerings for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Course Offering for.
// start_needle:       The search string to search for.
// limit:              The maximum number of entries to return.
//
// Returns
// -------
//
function ciniki_courses_offeringSearch($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'),
        'limit'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Limit'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringSearch');
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
        . "AND ("
            . "courses.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR courses.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR offerings.name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR offerings.name LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR offerings.code LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR offerings.code LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR offerings.code LIKE '%_" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
        . ") "
        . "GROUP BY offerings.id "
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
    return $rc;
}
?>
