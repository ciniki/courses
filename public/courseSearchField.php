<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to search for the courses.
// start_needle:        The search string to use.
// field:               The field to search.
// limit:               (optional) The maximum number of results to return.  If not
//                      specified, the maximum results will be 25.
// 
// Returns
// -------
//
function ciniki_courses_courseSearchField($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'yes', 'name'=>'Search String'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('level', 'type', 'category', 'medium', 'ages'), 'name'=>'Field'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Build the search query
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.courseSearchField'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Buid the query to search courses
    //
    $strsql = "SELECT DISTINCT ciniki_courses." . $args['field'] . " AS name "
        . "FROM ciniki_courses "
        . "WHERE ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_courses.status < 90 " // Only non-archived fields
        . "";
    $strsql .= "AND ciniki_courses." . $args['field'] . " LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ";
    $strsql .= "AND ciniki_courses." . $args['field'] . " <> '' ";
    if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";   // is_numeric verified
    } else {
        $strsql .= "LIMIT 25 ";
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQuery');
    return ciniki_core_dbRspQuery($ciniki, $strsql, 'ciniki.courses', 'results', 'result', array('stat'=>'ok', 'results'=>array()));
}
?>
