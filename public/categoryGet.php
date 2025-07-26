<?php
//
// Description
// ===========
// This method will return all the information about an category.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the category is attached to.
// category_id:          The ID of the category to get the details for.
//
// Returns
// -------
//
function ciniki_courses_categoryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'category_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Category'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.categoryGet');
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
    // Return default for new Category
    //
    if( $args['category_id'] == 0 ) {
        $category = array('id'=>0,
            'name'=>'',
            'permalink'=>'',
            'sequence'=>'1',
            'image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );
    }

    //
    // Get the details for an existing Category
    //
    else {
        $strsql = "SELECT ciniki_course_categories.id, "
            . "ciniki_course_categories.name, "
            . "ciniki_course_categories.permalink, "
            . "ciniki_course_categories.sequence, "
            . "ciniki_course_categories.image_id, "
            . "ciniki_course_categories.synopsis, "
            . "ciniki_course_categories.description "
            . "FROM ciniki_course_categories "
            . "WHERE ciniki_course_categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_categories.id = '" . ciniki_core_dbQuote($ciniki, $args['category_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'categories', 'fname'=>'id', 
                'fields'=>array('name', 'permalink', 'sequence', 'image_id', 'synopsis', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.295', 'msg'=>'Category not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['categories'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.296', 'msg'=>'Unable to find Category'));
        }
        $category = $rc['categories'][0];
    }

    return array('stat'=>'ok', 'category'=>$category);
}
?>
