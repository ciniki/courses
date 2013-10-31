<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the course to.
// name:				The name of the course.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_courses_courseUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'code'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Code'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'), 
        'level'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Level'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Type'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'short_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Description'), 
        'long_description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Long Description'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.courseUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	if( (isset($args['code']) || isset($args['name'])) && (!isset($args['permalink']) || $args['permalink'] == '') ) {
		if( !isset($args['code']) || !isset($args['name']) ) {	
			//
			// Get original
			//
			$strsql = "SELECT code, name "
				. "FROM ciniki_courses "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
				. "";
			$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
			if( $rc['stat'] != 'ok' ) {
				return $rc;
			}
			if( !isset($args['code']) ) {
				$name = $rc['course']['code'] . '-' . $args['name'];
			} else {
				$name = $args['code'] . '-' . $rc['course']['name'];
			}
		} else {
			$name = $args['code'] . '-' . $args['name'];
		}
		$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower($name)));
	}

	//
	// Check the permalink doesn't already exist
	//
	if( isset($args['permalink']) ) {
		$strsql = "SELECT id, name, permalink FROM ciniki_courses "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
			. "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( $rc['num_rows'] > 0 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1278', 'msg'=>'You already have an course with this name, please choose another name.'));
		}
	}
	
	//
	// Update the course
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
	return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.courses.course', $args['course_id'], $args, 0x07);
}
?>
