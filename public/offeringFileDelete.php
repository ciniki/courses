<?php
//
// Description
// ===========
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_courses_offeringFileDelete(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offering_file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringFileDelete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the existing offering file information
    //
    $strsql = "SELECT id, file_id, uuid FROM ciniki_course_offering_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['offering_file_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.35', 'msg'=>'File does not exist'));
    }
    $ofile = $rc['file'];

    //
    // Remove the file from the database
    //
    $strsql = "DELETE FROM ciniki_course_offering_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['offering_file_id']) . "' ";
    $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) { 
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return $rc;
    }

    ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.courses', 'ciniki_course_history', 
        $args['tnid'], 3, 'ciniki_course_offering_files', $args['offering_file_id'], '*', '');
    //
    // Add to the sync queue so it will get pushed
    //
    $ciniki['syncqueue'][] = array('push'=>'ciniki.courses.offering_file', 
        'args'=>array('delete_uuid'=>$ofile['uuid'], 'delete_id'=>$ofile['id']));

    //
    // Get the referenced file information
    //
    $strsql = "SELECT ciniki_course_files.id, ciniki_course_files.uuid, COUNT(file_id) AS num_offerings "
        . "FROM ciniki_course_files "
        . "LEFT JOIN ciniki_course_offering_files ON (ciniki_course_files.id = ciniki_course_offering_files.file_id "
            . "AND ciniki_course_offering_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' ) "
        . "WHERE ciniki_course_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_course_files.id = '" . ciniki_core_dbQuote($ciniki, $ofile['file_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.36', 'msg'=>'File does not exist'));
    }
    $file = $rc['file'];
    //
    // Only delete the file if it's not reference by any other offerings
    //
    if( $file['num_offerings'] == 0 ) {
        //
        // Remove the file from the database
        //
        $strsql = "DELETE FROM ciniki_course_files "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $ofile['file_id']) . "' ";
        $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.courses');
        if( $rc['stat'] != 'ok' ) { 
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
            return $rc;
        }

        ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.courses', 'ciniki_course_history', 
            $args['tnid'], 3, 'ciniki_course_files', $ofile['file_id'], '*', '');
        //
        // Add to the sync queue so it will get pushed
        //
        $ciniki['syncqueue'][] = array('push'=>'ciniki.courses.file', 
            'args'=>array('delete_uuid'=>$file['uuid'], 'delete_id'=>$file['id']));
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

    return array('stat'=>'ok');
}
?>
