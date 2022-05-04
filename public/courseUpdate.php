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
        'primary_image_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'), 
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

    if( (isset($args['code']) || isset($args['name'])) && (!isset($args['permalink']) || $args['permalink'] == '') ) {
        if( !isset($args['code']) || !isset($args['name']) ) {  
            //
            // Get original
            //
            $strsql = "SELECT code, name "
                . "FROM ciniki_courses "
                . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
                . "";
            $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( !isset($args['code']) ) {
                $name = $args['name'] . ($rc['course']['code'] != '' ? '-' . $rc['course']['code'] : '');
            } else {
                $name = $rc['course']['name'] . ($args['code'] != '' ? '-' . $args['code'] : '');
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.9', 'msg'=>'You already have an course with this name, please choose another name.'));
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
