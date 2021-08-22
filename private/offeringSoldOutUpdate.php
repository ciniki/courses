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
function ciniki_courses_offeringSoldOutUpdate(&$ciniki, $tnid, $offering_id) {

    //
    // Load the offering
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringLoad');
    $rc = ciniki_courses_offeringLoad($ciniki, $tnid, $offering_id, array('registrations'=>'yes'));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.161', 'msg'=>'Unable to load offering', 'err'=>$rc['err']));
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.162', 'msg'=>'Unable to load offering', 'err'=>$rc['err']));
    }
    $offering = $rc['offering'];

    //
    // Check if the offering is marked as sold out
    //
    if( ($offering['reg_flags']&0x08) == 0 && $offering['seats_sold'] >= $offering['num_seats'] ) {

        $update_args['reg_flags'] = $offering['reg_flags'] | 0x08;

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
        $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.courses.offering', $offering_id, $update_args, 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.166', 'msg'=>'Unable to update the offering', 'err'=>$rc['err']));
        }
    }

    return array('stat'=>'ok');
}
?>
