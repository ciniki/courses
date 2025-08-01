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
function ciniki_courses_courseUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'trim'=>'yes', 'name'=>'Name'), 
        'code'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Code'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Status'), 
        'sequence'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Order'), 
        'primary_image_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'), 
        'subcategory_id'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Subcategory'), 
        'level'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Level'), 
        'type'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Type'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Category'), 
        'medium'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Medium'), 
        'ages'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Ages'), 
        'flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Options'), 
        'short_description'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Short Description'), 
        'long_description'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Long Description'), 
        'materials_list'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Materials List'), 
        'paid_content'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Paid Content'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.courseUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    //
    // Load the subcategory
    //
    $strsql = "SELECT courses.id, "
        . "courses.subcategory_id, "
        . "courses.code, "
        . "courses.name, "
        . "courses.sequence "
        . "FROM ciniki_courses AS courses "
        . "WHERE courses.id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
        . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.305', 'msg'=>'Unable to load course', 'err'=>$rc['err']));
    }
    if( !isset($rc['course']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.307', 'msg'=>'Unable to find requested course'));
    }
    $course = $rc['course'];

    //
    // Check permalink
    //
    if( (isset($args['code']) || isset($args['name'])) && (!isset($args['permalink']) || $args['permalink'] == '') ) {
        if( !isset($args['code']) || !isset($args['name']) ) {  
            if( !isset($args['code']) ) {
                $name = $args['name'] . ($course['code'] != '' ? '-' . $course['code'] : '');
            } else {
                $name = $course['name'] . ($args['code'] != '' ? '-' . $args['code'] : '');
            }
        } else {
            $name = $args['name'] . ($args['code'] != '' ? '-' . $args['code'] : '');
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'makePermalink');
        $args['permalink'] = ciniki_core_makePermalink($ciniki, $name);
    }

    //
    // Check the permalink doesn't already exist
    //
    if( isset($args['permalink']) ) {
        $strsql = "SELECT id, name, permalink FROM ciniki_courses "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'warn', 'err'=>array('code'=>'ciniki.courses.9', 'msg'=>'You already have an course with this name, please choose another name.'));
        }
    }
    
    //
    // Update the course
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.courses.course', $args['course_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.110', 'msg'=>'Unable to update course', 'err'=>$rc['err']));
    }

    //
    // Check if sequences should be updated
    //
    if( isset($args['sequence']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesUpdate');
        $rc = ciniki_core_sequencesUpdate($ciniki, $args['tnid'], 'ciniki.courses.course', 
            'subcategory_id', $course['subcategory_id'], $args['sequence'], $course['sequence']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.musicfestivals');
            return $rc;
        }
    }

    //
    // Find list of offerings and run web index update
    //
    $strsql = "SELECT id FROM ciniki_course_offerings "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['rows']) ) {
        foreach($rc['rows'] as $row) {
            //
            // Update notification queue for each offering
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
            $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $args['tnid'], $row['id']);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.260', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
            }

            //
            // Update the web index if enabled
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
            ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.courses.offering', 'object_id'=>$row['id']));
        }
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'courses');

    return array('stat'=>'ok');
}
?>
