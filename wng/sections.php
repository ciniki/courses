<?php
//
// Description
// -----------
// Return the list of sections available from the courses module
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure forms module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.courses']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.308', 'msg'=>'Module not enabled'));
    }

    $sections = array();


    //
    // Get the list of courses
    //
    $strsql = "SELECT ciniki_courses.id, "
        . "ciniki_courses.name "
        . "FROM ciniki_courses "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY status, name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'courses', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.309', 'msg'=>'Unable to load courses', 'err'=>$rc['err']));
    }
    $courses = isset($rc['courses']) ? $rc['courses'] : array();

    //
    // Section to display a course
    //
    // NOTE: Future
/*    $sections['ciniki.courses.course'] = array(
        'name' => 'Program Details',
        'module' => 'Programs',
        'settings' => array(
            'course-id' => array('label'=>'Program', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$courses,
                ),
            ),
        ); */
    //
    // Section to display the list of sessions and prices as pricelist to add to cart
    //
    $sections['ciniki.courses.courseprices'] = array(
        'name' => 'Program Price List',
        'module' => 'Programs',
        'settings' => array(
            'course-id' => array('label'=>'Program', 'type'=>'select', 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                'options'=>$courses,
                ),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
