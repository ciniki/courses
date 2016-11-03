<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:         The ID of the business to add the course to.
// name:                The name of the course.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_courses_offeringUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'code'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Code'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('10', '60'), 'name'=>'Status'), 
        'webflags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Web Flags'), 
        'reg_flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Registration Flags'),
        'num_seats'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number of Seats'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['business_id'], 'ciniki.courses.offeringUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    $strsql = "SELECT id, uuid, course_id, name, code "
        . "FROM ciniki_course_offerings "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.49', 'msg'=>'Unable to find course offering.'));
    }
    $offering = $rc['offering'];

    //
    // Check the permalink doesn't already exist
    //
    if( (isset($args['name']) || isset($args['code'])) && (!isset($args['permalink']) || $args['permalink'] == '') ) {  
        if( isset($args['code']) || $offering['code'] != '' ) {
            $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower((isset($args['name']) ? $args['name'] : $offering['name']) . '-' . (isset($args['code']) ? $args['code'] : $offering['code']))));
        } else {
            $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower(isset($args['name']) ? $args['name'] : $offering['name'])));
        }
        $strsql = "SELECT id, name, permalink FROM ciniki_course_offerings "
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND course_id = '" . ciniki_core_dbQuote($ciniki, $offering['course_id']) . "' "     // permalink must be unique within a course
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.50', 'msg'=>'You already have an course offering with this name, please choose another name.'));
        }
    }

    //
    // Update the offering
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    return ciniki_core_objectUpdate($ciniki, $args['business_id'], 'ciniki.courses.offering', $args['offering_id'], $args, 0x07);
}
?>
