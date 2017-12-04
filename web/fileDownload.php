<?php
//
// Description
// ===========
// This function will return the file details and content so it can be sent to the client.
//
// Returns
// -------
//
function ciniki_courses_web_fileDownload($ciniki, $tnid, $permalink) {

    //
    // Get the file details
    //
    $strsql = "SELECT ciniki_course_files.id, "
        . "ciniki_course_files.name, "
        . "ciniki_course_files.extension, "
        . "ciniki_course_files.binary_content "
        . "FROM ciniki_course_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND CONCAT_WS('.', permalink, extension) = '" . ciniki_core_dbQuote($ciniki, $permalink) . "' "
        . "AND (webflags&0x01) = 0 "        // Make sure file is to be visible
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.courses.72', 'msg'=>'Unable to find requested file'));
    }
    $rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
