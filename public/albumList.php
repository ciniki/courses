<?php
//
// Description
// -----------
// This method will return the list of Photo Albums for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:        The ID of the tenant to get Photo Album for.
//
// Returns
// -------
//
function ciniki_courses_albumList($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'course_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Course'),
        'offering_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Offering'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];

    //
    // Check access to tnid as owner, or sys admin.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.albumList');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of albums
    //
    $strsql = "SELECT ciniki_course_albums.id, "
        . "ciniki_course_albums.course_id, "
        . "ciniki_course_albums.offering_id, "
        . "ciniki_course_albums.name, "
        . "ciniki_course_albums.permalink, "
        . "ciniki_course_albums.flags, "
        . "ciniki_course_albums.sequence "
        . "FROM ciniki_course_albums "
        . "WHERE ciniki_course_albums.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_course_albums.course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
        . "AND ciniki_course_albums.offering_id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "ORDER BY sequence, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'albums', 'fname'=>'id', 
            'fields'=>array('id', 'course_id', 'offering_id', 'name', 'permalink', 'flags', 'sequence')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['albums']) ) {
        $albums = $rc['albums'];
        $album_ids = array();
        foreach($albums as $iid => $album) {
            $album_ids[] = $album['id'];
        }
    } else {
        $albums = array();
        $album_ids = array();
    }

    return array('stat'=>'ok', 'albums'=>$albums, 'nplist'=>$album_ids);
}
?>
