<?php
//
// Description
// -----------
// Load the current offering notification queue, and build a new one then compare and update. 
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_offeringNQueueUpdate(&$ciniki, $tnid, $offering_id) {

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Load the queue
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueLoad');
    $rc = ciniki_courses_offeringNQueueLoad($ciniki, $tnid, $offering_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.234', 'msg'=>'Unable to load notification queue', 'err'=>$rc['err']));
    }
    $old_nqueue = isset($rc['nqueue']) ? $rc['nqueue'] : array();

    //
    // Build the queue
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringNQueueBuild');
    $rc = ciniki_courses_offeringNQueueBuild($ciniki, $tnid, $offering_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.235', 'msg'=>'Unable to build notification queue', 'err'=>$rc['err']));
    }
    $new_nqueue = isset($rc['nqueue']) ? $rc['nqueue'] : array();

    //
    // Remove any items no longer in the queue
    //
    foreach($old_nqueue AS $qid => $item) {
        if( !isset($new_nqueue[$qid]) ) {
            $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.courses.offering_nqueue', $item['id'], $item['uuid'], 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.236', 'msg'=>'Unable to remove queue item', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Add any items not in old nqueue
    //
    foreach($new_nqueue AS $qid => $item) {
        if( !isset($old_nqueue[$qid]) ) {
            $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.courses.offering_nqueue', $item, 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.237', 'msg'=>'Unable to add the queue item', 'err'=>$rc['err']));
            }
        } elseif( $old_nqueue[$qid]['scheduled_dt'] != $item['scheduled_dt'] ) {
            //
            // Update the scheduled date
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.courses.offering_nqueue', $old_nqueue[$qid]['id'], array(
                'scheduled_dt' => $item['scheduled_dt'],
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.241', 'msg'=>'Unable to update the offering_nqueue', 'err'=>$rc['err']));
            }
        }
    }

    return array('stat'=>'ok');
}
?>
