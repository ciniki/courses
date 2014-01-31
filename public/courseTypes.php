<?php
//
// Description
// -----------
// This function will return the list of categories in use.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to search for the courses.
// start_needle:		The search string to use.
// field:				The field to search.
// limit:				(optional) The maximum number of results to return.  If not
//						specified, the maximum results will be 25.
// 
// Returns
// -------
//
function ciniki_courses_courseTypes($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.courseTypes'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Build the query to get the list of course types
	//
	$strsql = "SELECT DISTINCT ciniki_courses.type AS name "
		. "FROM ciniki_courses "
		. "WHERE ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "ORDER BY name ";
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
		array('container'=>'types', 'fname'=>'name', 'name'=>'type',
			'fields'=>array('name')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['types']) ) {
		return array('stat'=>'ok', 'types'=>array());
	}

	foreach($rc['types'] as $cat => $type) {
		$rc['types'][$cat]['type']['settings_name'] = preg_replace('/[^a-z0-9]/', '', strtolower($type['type']['name']));
	}
	
	return array('stat'=>'ok', 'types'=>$rc['types']);
}
?>
