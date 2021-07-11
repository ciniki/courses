<?php
//
// Description
// -----------
// This method will check the courses are stored in ciniki-storage and then it
// will clear the image blob from the datbase.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_dbMoveFileStorage($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'clear'=>array('required'=>'no', 'default'=>'no', 'name'=>'Clear DB Blob Content'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.dbMoveFileStorage');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');

    ini_set('memory_limit', '4192M');

    $strsql = "SELECT id, uuid, binary_content "
        . "FROM ciniki_course_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND binary_content <> '' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'image');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['rows']) ) { 
        return array('stat'=>'ok');
    }

    //
    // Got through the courses and check all of them are stored in the storage directory
    //
    $files = $rc['rows'];
    foreach($files as $file) {
        $storage_filename = $tenant_storage_dir . '/ciniki.courses/files/' . $file['uuid'][0] . '/' . $file['uuid'];
        if( !is_dir(dirname($storage_filename)) ) {
            if( !mkdir(dirname($storage_filename), 0700, true) ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.175', 'msg'=>'Unable to add file'));
            }
        }
        if( !file_exists($storage_filename) ) { 
            file_put_contents($storage_filename, $file['binary_content']);
        }
        if( isset($args['clear']) && $args['clear'] == 'yes' && file_exists($storage_filename) ) {
            $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.courses.file', $file['id'], array('binary_content'=>''));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
        }
    }

    return array('stat'=>'ok');
}
?>
