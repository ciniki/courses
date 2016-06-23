<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_web_files($ciniki, $settings, $business_id) {

    $strsql = "SELECT id, name, extension, permalink, description "
        . "FROM ciniki_course_files "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND type = 2 "
        . "AND (webflags&0x01) = 0 "
        . "ORDER BY name "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'files', 'fname'=>'name', 'name'=>'file',
            'fields'=>array('id', 'name', 'extension', 'permalink')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['files']) ) {
        return array('stat'=>'ok', 'files'=>$rc['files']);
    }

    return array('stat'=>'ok', 'files'=>array());
}
?>
