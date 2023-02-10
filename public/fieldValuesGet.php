<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_fieldValuesGet(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'field'=>array('required'=>'yes', 'blank'=>'no', 'validlist'=>array('level', 'type', 'category', 'medium', 'ages'), 'name'=>'Field'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.fieldValuesGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the distinct values for the field
    //
    $strsql = "SELECT DISTINCT " . $args['field'] . " AS item "
        . "FROM ciniki_courses "
        . "WHERE ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND {$args['field']} <> '' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'values', 'fname'=>'item', 
            'fields'=>array('value'=>'item'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.281', 'msg'=>'Unable to load fields', 'err'=>$rc['err']));
    }
    $values = isset($rc['values']) ? $rc['values'] : array();

    return array('stat'=>'ok', 'values'=>$values);
}
?>
