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
function ciniki_courses_instructorList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.instructorList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the list of instructors
    //
    $strsql = "SELECT ciniki_course_instructors.id, "
        . "CONCAT_WS(' ', first, last) AS name, "
        . "IF((webflags&0x01)=0, 'Visible', 'Hidden') AS status_text "
        . "FROM ciniki_course_instructors "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY (webflags&0x01), last, first "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'instructors', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'status_text'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.125', 'msg'=>'Unable to load instructors', 'err'=>$rc['err']));
    }
    if( isset($rc['instructors']) ) {
        $instructors = $rc['instructors'];
        $instructors_ids = array();
        foreach($instructors as $k => $v) {
            $instructors_ids[] = $v['id'];
        }
    } else {
        $instructors = array();
        $instructors_ids = array();
    }

    return array('stat'=>'ok', 'instructors'=>$instructors);
}
?>
