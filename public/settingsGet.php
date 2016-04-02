<?php
//
// Description
// -----------
// This method will turn the courses settings for a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the ATDO settings for.
// 
// Returns
// -------
//
function ciniki_courses_settingsGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'processhtml'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Process HTML'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.settingsGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	
	//
	// Grab the settings for the business from the database
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
	$rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_course_settings', 'business_id', $args['business_id'], 'ciniki.courses', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	if( !isset($rc['settings']) ) {
		return array('stat'=>'ok', 'settings'=>array());
	}
	$settings = $rc['settings'];

	if( isset($args['processhtml']) && $args['processhtml'] == 'yes' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'processContent');
		if( isset($settings['course-registration-details']) ) {
			$rc = ciniki_web_processContent($ciniki, array(), $settings['course-registration-details']);
			$settings['course-registration-details-html'] = $rc['content'];
		}
		if( isset($settings['course-registration-more-details']) ) {
			$rc = ciniki_web_processContent($ciniki, array(), $settings['course-registration-more-details']);
			$settings['course-registration-more-details-html'] = $rc['content'];
		}
	}

	return array('stat'=>'ok', 'settings'=>$settings);
}
?>
