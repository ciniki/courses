<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to file belongs to.
// file_id:             The ID of the file to get.
//
// Returns
// -------
//
function ciniki_courses_fileGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.fileGet', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    if( $args['file_id'] == 0 ) {
        $file = array(
            'id' => 0,
            'type' => 20,
            'name' => '',
            'permalink' => '',
            'webflags' => 1,
            'publish_date' => '',
            );
    }
    else {
        //
        // Get the main information
        //
        $strsql = "SELECT files.id, "
            . "files.type, "
            . "files.name, "
            . "files.permalink, "
            . "files.webflags, "
            . "IF(files.webflags&0x01=1,'Hidden','Visible') AS webvisible, "
            . "IFNULL(DATE_FORMAT(publish_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS publish_date, "
            . "files.description "
            . "FROM ciniki_course_files AS files "
            . "WHERE files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'file');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['file']) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.17', 'msg'=>'Unable to find file'));
        }
        $file = isset($rc['file']) ? $rc['file'] : array();
    }
    
    return array('stat'=>'ok', 'file'=>$file);
}
?>
