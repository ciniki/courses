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
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_courses_offeringFileAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Offering'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringFileAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get a new UUID
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUUID');
    $rc = ciniki_core_dbUUID($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return $rc;
    }
    $args['uuid'] = $rc['uuid'];

    //
    // Add the course to the database
    //
    $strsql = "INSERT INTO ciniki_course_offering_files (uuid, tnid, "
        . "course_id, offering_id, file_id, "
        . "date_added, last_updated) VALUES ("
        . "'" . ciniki_core_dbQuote($ciniki, $args['uuid']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "', "
        . "'" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "', "
        . "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
        . "";
    $rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) { 
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return $rc;
    }
    if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.34', 'msg'=>'Unable to add course'));
    }
    $offering_file_id = $rc['insert_id'];

    //
    // Add all the fields to the change log
    //
    $changelog_fields = array(
        'uuid',
        'course_id',
        'offering_id',
        'file_id',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) ) {
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.courses', 
                'ciniki_course_history', $args['tnid'], 
                1, 'ciniki_course_offering_files', $offering_file_id, $field, $args[$field]);
        }
    }

    //
    // Commit the database changes
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

    $ciniki['syncqueue'][] = array('push'=>'ciniki.courses.offering_file', 
        'args'=>array('id'=>$offering_file_id));

    return array('stat'=>'ok', 'id'=>$offering_file_id);
}
?>
