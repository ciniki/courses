<?php
//
// Description
// -----------
// This function will update exhibitor names and a customer name has been updated.
//
// Arguments
// ---------
// ciniki:
// tnid:                The tenant ID to check the session user against.
// args:                The requested method.
//
// Returns
// -------
// <rsp stat='ok' />
//
function ciniki_courses_hooks_customerNameUpdate($ciniki, $tnid, $args) {
    //
    // Check to see if the customer is an instructor
    //
    if( isset($args['customer_id']) && $args['customer_id'] > 0 ) {
        //
        // Load the instructor and customer permalink
        //
        $strsql = "SELECT instructors.id, "
            . "instructors.customer_id, "
            . "instructors.permalink, "
            . "customers.permalink AS customer_permalink "
            . "FROM ciniki_course_instructors AS instructors "
            . "LEFT JOIN ciniki_customers AS customers ON ("
                . "instructors.customer_id = customers.id "
                . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE instructors.customer_id = '" . ciniki_core_dbQuote($ciniki, $args['customer_id']) . "' "
            . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.customers', 'item');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['item']['customer_id']) && $rc['item']['permalink'] != $rc['item']['customer_permalink'] ) {
            $instructor = $rc['item'];
            //
            // Update the instructor
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.courses.instructor', $instructor['id'], array(
                'permalink' => $instructor['customer_permalink'],
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'code'=>'ciniki.courses.224', 'msg'=>'Unable to update course instructor', 'err'=>$rc['err']);
            }
        }
    }

    return array('stat'=>'ok');
}
?>
