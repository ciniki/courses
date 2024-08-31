<?php
//
// Description
// ===========
// This method will return all the information about an photo album image.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the photo album image is attached to.
// albumimage_id:          The ID of the photo album image to get the details for.
//
// Returns
// -------
//
function ciniki_courses_albumImageGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'albumimage_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Photo Album Image'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.albumImageGet');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Load tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Return default for new Photo Album Image
    //
    if( $args['albumimage_id'] == 0 ) {
        $image = array('id'=>0,
            'album_id'=>0,
            'name'=>'',
            'permalink'=>'',
            'flags'=>1,
            'image_id'=>0,
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Photo Album Image
    //
    else {
        $strsql = "SELECT ciniki_course_album_images.id, "
            . "ciniki_course_album_images.album_id, "
            . "ciniki_course_album_images.name, "
            . "ciniki_course_album_images.permalink, "
            . "ciniki_course_album_images.flags, "
            . "ciniki_course_album_images.image_id, "
            . "ciniki_course_album_images.description "
            . "FROM ciniki_course_album_images "
            . "WHERE ciniki_course_album_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_album_images.id = '" . ciniki_core_dbQuote($ciniki, $args['albumimage_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'images', 'fname'=>'id', 
                'fields'=>array('album_id', 'name', 'permalink', 'flags', 'image_id', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.92', 'msg'=>'Photo Album Image not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['images'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.93', 'msg'=>'Unable to find Photo Album Image'));
        }
        $image = $rc['images'][0];
    }

    return array('stat'=>'ok', 'image'=>$image);
}
?>
