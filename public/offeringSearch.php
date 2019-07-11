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
    // Get the list of 
    //
    $strsql = "SELECT offerings.id, "
        . "offerings.course_id, "
        . "courses.name AS course_name, "
        . "offerings.name, "
        . "offerings.code, "
        . "offerings.permalink, "
        . "offerings.status, "
        . "offerings.webflags, "
        . "offerings.condensed_date, "
        . "offerings.reg_flags, "
        . "offerings.num_seats, "
        . "MIN(classes.class_date) AS first_date "
        . "FROM ciniki_course_offerings AS offerings "
        . "LEFT JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_offering_classes AS classes ON ("
            . "offerings.id = classes.offering_id "
            . "AND classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
        . "ORDER BY first_date DESC "
        . "";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'id', 
            'fields'=>array('id', 'course_id', 'course_name', 'name', 'code', 'permalink', 'status', 'webflags', 
                'condensed_date', 'reg_flags', 'num_seats')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $offerings = isset($rc['offerings']) ? $rc['offerings'] : array();

    return array('stat'=>'ok', 'offerings'=>$offerings);
}
?>
