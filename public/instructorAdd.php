<?php
//
// Description
// ===========
// This method will add a new course to the courses table.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the course to.
//
// name:                The name of the course.
// permalink:           The permalink for identifing the instructor in web url.
// webflags:            (optional) How the course is shared with the public and customers.  
//                      The default is the course is public.
//
//                      0x01 - Hidden, unavailable on the website
//
// short_bio:           The short description of the instructor, for use in lists.
// full_bio:            The long description of the instructor, for use in the details page.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_courses_instructorAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
        'first'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'First Name'), 
        'last'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Last Name'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'name'=>'Level'), 
        'webflags'=>array('required'=>'no', 'default'=>'0', 'name'=>'Webflags'), 
        'short_bio'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Short Bio'), 
        'full_bio'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Full Bio'), 
        'url'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'URL'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.instructorAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        //
        // Check the customer to make sure they don't already exist as an instructor
        //
        $strsql = "SELECT id, customer_id FROM ciniki_course_instructors "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'instructor');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.223', 'msg'=>'This customer is already setup as an instructor.'));
        }
        //
        // Get the customer details
        //
        $strsql = "SELECT permalink "
            . "FROM ciniki_customers "
            . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND ciniki_customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'customer');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.225', 'msg'=>'Unable to load customer', 'err'=>$rc['err']));
        }
        if( isset($rc['customer']) ) {
            $args['permalink'] = $rc['customer']['permalink'];
        }
            
    } else {
        $name = $args['first'] . '-' . $args['last'];
        $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower($name)));

        //
        // Check the permalink doesn't already exist
        //
        $strsql = "SELECT id, first, last, permalink FROM ciniki_course_instructors "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'instructor');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.19', 'msg'=>'You already have an instructor with this name, please choose another name'));
        }
    }

    //
    // Add the instructor
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    return ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.instructor', $args, 0x07);
}
?>
