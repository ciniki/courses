<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the course to.
// name:                The name of the course.  
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_courses_instructorUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'instructor_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Instructor'), 
        'customer_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Customer'), 
        'first'=>array('required'=>'no', 'blank'=>'no', 'name'=>'First Name'), 
        'last'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Last Name'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        'webflags'=>array('required'=>'no', 'name'=>'Webflags'), 
        'primary_image_id'=>array('required'=>'no', 'name'=>'Level'), 
        'short_bio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Short Bio'), 
        'full_bio'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Full Bio'), 
        'url'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'URL'), 
        'rating'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Rating'), 
        'hourly_rate'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'number', 'name'=>'Hourly Rate'), 
        'notes'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Notes'), 
        'levels'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Levels'), 
        'mediums'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Mediums'), 
        'types'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'list', 'delimiter'=>'::', 'name'=>'Types'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.instructorUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        //
        // Check the customer to make sure they don't already exist as an instructor
        //
        $strsql = "SELECT id, customer_id FROM ciniki_course_instructors "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['instructor_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'instructor');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.courses.231', 'msg'=>'This customer is already setup as an instructor.'));
        }
        
    }
    if( (isset($args['first']) || isset($args['last'])) && (!isset($args['permalink']) || $args['permalink'] == '') ) {
        if( !isset($args['first']) || !isset($args['last']) ) { 
            //
            // Get original
            //
            $strsql = "SELECT first, last "
                . "FROM ciniki_course_instructors "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['instructor_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'instructor');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($args['first']) ) {
                $name = $rc['instructor']['first'] . '-' . $args['last'];
            } else {
                $name = $args['first'] . '-' . $rc['instructor']['last'];
            }
        } else {
            $name = $args['first'] . '-' . $args['last'];
        }
        $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower($name)));
    }

    //
    // Check the permalink doesn't already exist
    //
    if( isset($args['permalink']) ) {
        $strsql = "SELECT id, first, last, permalink FROM ciniki_course_instructors "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['instructor_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'instructor');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.courses.28', 'msg'=>'You already have an instructor with this name, please choose another name.'));
        }
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
    // Update the instructor
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.courses.instructor', $args['instructor_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.276', 'msg'=>'Unable to update instructor', 'err'=>$rc['err']));
    }

    //
    // Update the levels
    //
    if( isset($args['levels']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.courses', 'instructor_tag', $args['tnid'],
            'ciniki_course_instructor_tags', 'ciniki_course_history',
            'instructor_id', $args['instructor_id'], 10, $args['levels']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
            return $rc;
        }
    }

    //
    // Update the mediums
    //
    if( isset($args['mediums']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.courses', 'instructor_tag', $args['tnid'],
            'ciniki_course_instructor_tags', 'ciniki_course_history',
            'instructor_id', $args['instructor_id'], 20, $args['mediums']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
            return $rc;
        }
    }

    //
    // Update the types
    //
    if( isset($args['types']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'tagsUpdate');
        $rc = ciniki_core_tagsUpdate($ciniki, 'ciniki.courses', 'instructor_tag', $args['tnid'],
            'ciniki_course_instructor_tags', 'ciniki_course_history',
            'instructor_id', $args['instructor_id'], 30, $args['types']);
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.courses.instructor', 'object_id'=>$args['instructor_id']));
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'wng', 'indexObject', array('object'=>'ciniki.courses.instructor', 'object_id'=>$args['instructor_id']));

    return array('stat'=>'ok');
}
?>
