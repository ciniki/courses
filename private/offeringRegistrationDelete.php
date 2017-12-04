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
function ciniki_courses__offeringRegistrationDelete($ciniki, $tnid, $id, $uuid) {
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');

    //
    // Delete the registration
    //
    return ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.courses.offering_registration', $id, $uuid, 0x04);
}
?>
