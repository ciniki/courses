<?php
//
// Description
// ===========
// This method will return all the information about an photo album.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the photo album is attached to.
// album_id:          The ID of the photo album to get the details for.
//
// Returns
// -------
//
function ciniki_courses_albumGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'album_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Photo Album'),
        'images'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Image'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.albumGet');
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
    $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
    $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

    //
    // Return default for new Photo Album
    //
    if( $args['album_id'] == 0 ) {
        $strsql = "SELECT MAX(sequence) AS max_sequence "
            . "FROM ciniki_course_albums "
            . "WHERE ciniki_course_albums.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'max');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['max']['max_sequence']) ) {
            $seq = $rc['max']['max_sequence'] + 1;
        } else {
            $seq = 1;
        }
        
        $album = array('id'=>0,
            'course_id'=>'0',
            'offering_id'=>'0',
            'name'=>'',
            'permalink'=>'',
            'flags'=>'1',
            'sequence'=>$seq,
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Photo Album
    //
    else {
        $strsql = "SELECT ciniki_course_albums.id, "
            . "ciniki_course_albums.course_id, "
            . "ciniki_course_albums.offering_id, "
            . "ciniki_course_albums.name, "
            . "ciniki_course_albums.permalink, "
            . "ciniki_course_albums.flags, "
            . "ciniki_course_albums.sequence, "
            . "ciniki_course_albums.primary_image_id, "
            . "ciniki_course_albums.description "
            . "FROM ciniki_course_albums "
            . "WHERE ciniki_course_albums.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_albums.id = '" . ciniki_core_dbQuote($ciniki, $args['album_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'albums', 'fname'=>'id', 
                'fields'=>array('course_id', 'offering_id', 'name', 'permalink', 'flags', 'sequence', 'primary_image_id', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.85', 'msg'=>'Photo Album not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['albums'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.86', 'msg'=>'Unable to find Photo Album'));
        }
        $album = $rc['albums'][0];
    }
    $rsp = array('stat'=>'ok', 'album'=>$album);

    //
    // Check if images should be returned
    //
    if( isset($args['images']) && $args['images'] == 'yes' ) {
        $rsp['album']['images'] = array();
        $strsql = "SELECT ciniki_course_album_images.id, "
            . "ciniki_course_album_images.name, "
            . "ciniki_course_album_images.flags, "
            . "ciniki_course_album_images.image_id "
            . "FROM ciniki_course_album_images "
            . "WHERE album_id = '" . ciniki_core_dbQuote($ciniki, $args['album_id']) . "' "
            . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY ciniki_course_album_images.date_added DESC ";
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'images', 'fname'=>'id', 'fields'=>array('id', 'name', 'flags', 'image_id')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            $rsp['album']['images'] = $rc['images'];

            //
            // Add thumbnail information into list
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            foreach($rsp['album']['images'] as $iid => $image) {
                if( isset($image['image_id']) && $image['image_id'] > 0 ) {
                    $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $image['image_id'], 75);
                    if( $rc['stat'] != 'ok' ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.78', 'msg'=>'Unable to load image', 'err'=>$rc['err']));
                    }
                    $rsp['album']['images'][$iid]['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                }
            }
        }
    }

    return $rsp;
}
?>
