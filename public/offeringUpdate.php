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
function ciniki_courses_offeringUpdate(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
        'course_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Course'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'trimblanks'=>'yes', 'name'=>'Name'), 
        'code'=>array('required'=>'no', 'blank'=>'no', 'trimblanks'=>'yes', 'name'=>'Code'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('10', '60', '90'), 'name'=>'Status'), 
        'webflags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Web Flags'), 
        'start_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Start Date'), 
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'), 
        'dt_end_reg'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Registration Close Date and Time'),
        'num_seats'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Number of Seats'),
        'reg_flags'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Registration Flags'),
        'condensed_date'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Dates'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        'materials_list'=>array('required'=>'no', 'blank'=>'yes', 'trim'=>'yes', 'name'=>'Materials List'), 
        'paid_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Paid Content'),
        'dt_end_paid'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Paid Content End Date'),
        'form_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Required Form'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringUpdate'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    $strsql = "SELECT id, uuid, course_id, name, code "
        . "FROM ciniki_course_offerings "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
            $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower((isset($args['name']) ? $args['name'] : $offering['name']) . '-' . (isset($args['code']) ? $args['code'] : $offering['code']))));
        } else {
            $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower(isset($args['name']) ? $args['name'] : $offering['name'])));
        }
        $args['permalink'] = preg_replace('/\-\-\-/', '-', $args['permalink']);
        $args['permalink'] = preg_replace('/\-\-/', '-', $args['permalink']);
        $strsql = "SELECT id, name, permalink FROM ciniki_course_offerings "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
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
    $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.courses.offering', $args['offering_id'], $args, 0x07);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.109', 'msg'=>'Unable to update offering', 'err'=>$rc['err']));
    }

    //
    // Update the condensed date for the course
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'updateCondensedDate');
    $rc = ciniki_courses_updateCondensedDate($ciniki, $args['tnid'], $args['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the notification queue
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueUpdate');
    $rc = ciniki_courses_offeringNQueueUpdate($ciniki, $args['tnid'], $args['offering_id']);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.258', 'msg'=>'Unable to update notification queue', 'err'=>$rc['err']));
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
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.courses.offering', 'object_id'=>$args['offering_id']));
    
    return array('stat'=>'ok');
}
?>
