<?php
//
// Description
// ===========
// This method will return all the information about an course offering file.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the course offering file is attached to.
// file_id:          The ID of the course offering file to get the details for.
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
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course Offering File'),
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
    // Return default for new Course Offering File
    //
    if( $args['file_id'] == 0 ) {
        $file = array('id'=>0,
            'offering_id'=>'',
            'extension'=>'',
            'status'=>'',
            'name'=>'',
            'permalink'=>'',
            'webflags'=>'1',
            'description'=>'',
            'org_filename'=>'',
        );
    }

    //
    // Get the details for an existing Course Offering File
    //
    else {
        $strsql = "SELECT ciniki_course_offering_files.id, "
            . "ciniki_course_offering_files.offering_id, "
            . "ciniki_course_offering_files.extension, "
            . "ciniki_course_offering_files.status, "
            . "ciniki_course_offering_files.name, "
            . "ciniki_course_offering_files.permalink, "
            . "ciniki_course_offering_files.webflags, "
            . "ciniki_course_offering_files.description, "
            . "ciniki_course_offering_files.org_filename "
            . "FROM ciniki_course_offering_files "
            . "WHERE ciniki_course_offering_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_offering_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'files', 'fname'=>'id', 
                'fields'=>array('offering_id', 'extension', 'status', 'name', 'permalink', 'webflags', 'description', 'org_filename'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.206', 'msg'=>'Course Offering File not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['files'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.207', 'msg'=>'Unable to find Course Offering File'));
        }
        $file = $rc['files'][0];
    }

    return array('stat'=>'ok', 'file'=>$file);
}
?>
