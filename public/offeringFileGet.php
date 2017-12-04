<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant.
// offering_file_id:            The ID of the course offering file to get.
//
// Returns
// -------
//
function ciniki_courses_offeringFileGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offering_file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'),
        'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringFileGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki);

    //
    // Get the main information
    //
    $strsql = "SELECT ciniki_course_offering_files.id, "
        . "ciniki_course_files.id AS file_id, "
        . "ciniki_course_files.name, "
        . "ciniki_course_files.permalink, "
        . "ciniki_course_files.webflags, "
        . "IF((ciniki_course_files.webflags&0x01)=1, 'Hidden', 'Visible') AS web_visible "
        . "FROM ciniki_course_offering_files "
        . "LEFT JOIN ciniki_course_files ON (ciniki_course_offering_files.file_id = ciniki_course_files.id "
            . "AND ciniki_course_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "') "
        . "WHERE ciniki_course_offering_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_course_offering_files.id = '" . ciniki_core_dbQuote($ciniki, $args['offering_file_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'files', 'fname'=>'id', 'name'=>'file',
            'fields'=>array('id', 'file_id', 'first', 'last', 'name', 'permalink', 'primary_image_id', 'webflags', 'web_visible', 'short_bio', 'full_bio', 'url')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['files']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.courses.37', 'msg'=>'Unable to find file'));
    }
    $file = $rc['files'][0]['file'];

    if( isset($args['images']) && $args['images'] == 'yes' ) {
        $strsql = "SELECT "
            . "id, "
            . "name, "
            . "webflags, "
            . "image_id, "
            . "description, "
            . "url "
            . "FROM ciniki_course_file_images "
            . "WHERE ciniki_course_file_images.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_file_images.file_id = '" . ciniki_core_dbQuote($ciniki, $file['file_id']) . "' "
            . "ORDER BY ciniki_course_file_images.id ASC ";
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'images', 'fname'=>'id', 'name'=>'image',
                'fields'=>array('id', 'name', 'webflags', 'image_id', 'description', 'url')),
        ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['images']) ) {
            $file['images'] = $rc['images'];
            ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
            if( isset($file['images']) ) {
                foreach($file['images'] as $img_id => $img) {
                    if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
                        $rc = ciniki_images_loadCacheThumbnail($ciniki, $args['tnid'], $img['image']['image_id'], 75);
                        if( $rc['stat'] != 'ok' ) {
                            return $rc;
                        }
                        $file['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
                    }
                }
            }
        } 
    }
    
    return array('stat'=>'ok', 'file'=>$file);
}
?>
