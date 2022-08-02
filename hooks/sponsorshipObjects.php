<?php
//
// Description
// -----------
// Return the list of objects and ids available for sponsorship.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_hooks_sponsorshipObjects(&$ciniki, $tnid, $args) {

    $objects = array();
    
    //
    // Get the list of offerings that are upcoming for adding a sponsorship package to
    //
    $strsql = "SELECT offerings.id, "
        . "CONCAT_WS(' - ', courses.name, offerings.name, offerings.condensed_date) AS name, "
        . "offerings.start_date, "
        . "offerings.condensed_date "
        . "FROM ciniki_course_offerings AS offerings "
        . "INNER JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND (offerings.start_date > NOW() "
        . "";
    if( isset($args['object']) && $args['object'] == 'ciniki.courses.offering' && isset($args['object_id']) ) {
        $strsql .= "OR offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' ";
    }
    $strsql .= ") ";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'name', 'fields'=>array('id', 'name', 'start_date', 'condensed_date')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.270', 'msg'=>'Unable to load courses', 'err'=>$rc['err']));
    }
    $offerings = isset($rc['offerings']) ? $rc['offerings'] : array();

    //
    // Create the object array
    //
    foreach($offerings as $oid => $offering) {
        $objects["ciniki.courses.offering.{$offering['id']}"] = array(
            'id' => 'ciniki.courses.offering.' . $offering['id'],
            'object' => 'ciniki.courses.offering',
            'object_id' => $offering['id'],
            'full_name' => 'Program - ' . $offering['name'],
            'name' => $offering['name'],
            );
    }

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
