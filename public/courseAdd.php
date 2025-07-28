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
// webflags:            (optional) How the course is shared with the public and customers.  
//                      The default is the course is public.
//
//                      0x01 - Hidden, unavailable on the website
//
// short_description:   The short description of the course, for use in lists.
// long_description:    The long description of the course, for use in the details page.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_courses_courseAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Name'), 
        'code'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Code'), 
        'status'=>array('required'=>'no', 'default'=>'10', 'blank'=>'yes', 'name'=>'Status'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'), 
        'primary_image_id'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Image'), 
        'subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Subcategory'), 
        'level'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Level'), 
        'type'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Type'), 
        'category'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Category'), 
        'medium'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Medium'), 
        'ages'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Ages'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
        'short_description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Short Description'), 
        'long_description'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Long Description'), 
        'materials_list'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Materials List'), 
        'paid_content'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Paid Content'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    $name = $args['name'];
    if( $args['code'] != '' ) {
        $name = $args['name'] . '-' . $args['code'];
    }
//    $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower($name)));
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
    $args['permalink'] = ciniki_core_makePermalink($ciniki, $name);

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.courseAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id, name, permalink FROM ciniki_courses "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.7', 'msg'=>'You already have a course with this name, please choose another name'));
    }

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add the course
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.course', $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $course_id = $rc['id'];

    //
    // Update sequences
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x100000) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.courses.course', 
            'subcategory_id', $args['subcategory_id'], $args['sequence'], -1);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
            return $rc;
        }
    }

    //
    // Commit the transaction
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'courses');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.courses.course', 'object_id'=>$course_id));

    return array('stat'=>'ok', 'id'=>$course_id);

}
?>
