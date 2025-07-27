<?php
//
// Description
// ===========
// This method will return all the information about an subcategory.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the subcategory is attached to.
// subcategory_id:          The ID of the subcategory to get the details for.
//
// Returns
// -------
//
function ciniki_courses_subcategoryGet($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'),
        'subcategory_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subcategory'),
        'category_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.subcategoryGet');
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
    // Return default for new Subcategory
    //
    if( $args['subcategory_id'] == 0 ) {
        $subcategory = array('id'=>0,
            'category_id'=>'',
            'name'=>'',
            'permalink'=>'',
            'category_id' => (isset($args['category_id']) && $args['category_id'] > 0 ? $args['category_id'] : 0),
            'sequence'=>'1',
            'image_id'=>'0',
            'synopsis'=>'',
            'description'=>'',
        );

        if( isset($args['category_id']) && $args['category_id'] > 0 ) {
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'sequencesNext');
            $rc = ciniki_core_sequencesNext($ciniki, $args['tnid'], 'ciniki.courses.subcategory', 'category_id', $args['category_id']);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $subcategory['sequence'] = $rc['sequence'];
        }
    }

    //
    // Get the details for an existing Subcategory
    //
    else {
        $strsql = "SELECT ciniki_course_subcategories.id, "
            . "ciniki_course_subcategories.category_id, "
            . "ciniki_course_subcategories.name, "
            . "ciniki_course_subcategories.permalink, "
            . "ciniki_course_subcategories.sequence, "
            . "ciniki_course_subcategories.image_id, "
            . "ciniki_course_subcategories.synopsis, "
            . "ciniki_course_subcategories.description "
            . "FROM ciniki_course_subcategories "
            . "WHERE ciniki_course_subcategories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_course_subcategories.id = '" . ciniki_core_dbQuote($ciniki, $args['subcategory_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'subcategories', 'fname'=>'id', 
                'fields'=>array('category_id', 'name', 'permalink', 'sequence', 'image_id', 'synopsis', 'description'),
                ),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.302', 'msg'=>'Subcategory not found', 'err'=>$rc['err']));
        }
        if( !isset($rc['subcategories'][0]) ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.303', 'msg'=>'Unable to find Subcategory'));
        }
        $subcategory = $rc['subcategories'][0];
    }
    $rsp = array('stat'=>'ok', 'subcategory'=>$subcategory);

    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.courses', 0x100000) ) {
        $strsql = "SELECT categories.id, "
            . "categories.name "
            . "FROM ciniki_course_categories AS categories "
            . "WHERE categories.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "ORDER BY categories.sequence, categories.name "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
            array('container'=>'org_categories', 'fname'=>'id', 'fields'=>array('id', 'name')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.288', 'msg'=>'Unable to load org_categories', 'err'=>$rc['err']));
        }
        $rsp['org_categories'] = isset($rc['org_categories']) ? $rc['org_categories'] : array();
    }

    return $rsp;
}
?>
