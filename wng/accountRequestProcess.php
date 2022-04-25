<?php
//
// Description
// -----------
// This function will process the account request from accountMenuItems
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_wng_accountRequestProcess(&$ciniki, $tnid, &$request, $item) {

    if( !isset($item['ref']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.195', 'msg'=>'No reference specified'));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.196', 'msg'=>'Must be logged in'));
    }

    if( $item['ref'] == 'ciniki.courses.offering' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'wng', 'accountOfferingProcess');
        return ciniki_courses_wng_accountOfferingProcess($ciniki, $tnid, $request, $item);
    }
    

    return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.197', 'msg'=>'Account page not found'));
}
?>
