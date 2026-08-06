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

    $sections = [];

    //
    // Get the list of course types
    //
    $strsql = "SELECT DISTINCT ciniki_courses.type "
        . "FROM ciniki_courses "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY type "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'types', 'fname'=>'type', 'fields'=>array('type')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.309', 'msg'=>'Unable to load courses', 'err'=>$rc['err']));
    }
    $types = isset($rc['types']) ? $rc['types'] : array();

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
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.319', 'msg'=>'Unable to load courses', 'err'=>$rc['err']));
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
    $sections['ciniki.courses.courseprices'] = [
        'name' => 'Program Price List',
        'module' => 'Programs',
        'settings' => [
            'title' => ['label'=>'Title', 'type'=>'text'],
            'course-id' => ['label'=>'Program', 'type'=>'select', 
                'complex_options'=>['value'=>'id', 'name'=>'name'],
                'options'=>$courses,
                ],
            ],
        ];

    //
    // Upcoming offerings
    //
    $sections['ciniki.courses.upcoming'] = [
        'name' => 'Upcoming Sessions',
        'module' => 'Programs',
        'settings' => [
            'title' => ['label'=>'Title', 'type'=>'text'],
            'content' => ['label'=>'Intro', 'type'=>'htmlarea'],
            'course-type' => ['label'=>'Program Type', 'type'=>'select', 'options'=>$types, 'complex_options'=>['value'=>'type', 'name'=>'type']],
            'layout' => ['label'=>'Format', 'type'=>'select', 'options'=>[
                'flexcards' => 'Flex Cards',
                'tradingcards' => 'Trading Cards',
                ]],
            'image-ratio' => ['label' => 'Image Ratio', 
                'type'=>'select', 
                'default'=>'1-1', 
                'options'=>[
                    '2-1' => 'Panoramic',
                    '16-9' => 'Letterbox',
                    '6-4' => 'Wider',
                    '4-3' => 'Wide',
                    '1-1' => 'Square',
                    '3-4' => 'Tall',
                    '4-6' => 'Taller',
                ]],
            'include-current' => ['label'=>'Include Current', 'type'=>'toggle', 'default'=>'no', 'toggles'=>[
                'no'=>'No',
                'yes'=>'Yes',
                ]],
            'button-text' => ['label'=>'Button Text', 'type'=>'text'],
            'instructor-image-position'=>['label'=>'Instructor Image Position', 'type'=>'select', 'default'=>'top-right', 'separator'=>'yes', 'options'=>[
                'top-left' => 'Top Left',
                'top-left-inline' => 'Top Left Inline',
                'bottom-left' => 'Bottom Left',
                'top-right' => 'Top Right',
                'top-right-inline' => 'Top Right Inline',
                'bottom-right' => 'Bottom Right',
                ]],
            'instructor-image-size'=>['label'=>'Instructor Image Size', 'type'=>'toggle', 'default'=>'half', 'toggles'=>[
                'half' => 'Full',
                'large' => 'Large',
                'medium' => 'Medium',
                'small' => 'Small',
                'tiny' => 'Tiny',
                ]],
            ],
        ];

    //
    // Instructors
    //
    $sections['ciniki.courses.instructors'] = [
        'name' => 'Instructors',
        'module' => 'Programs',
        'settings' => [
            'title' => ['label'=>'Title', 'type'=>'text'],
            'content' => ['label'=>'Intro', 'type'=>'htmlarea'],
            'layout' => ['label'=>'Format', 'type'=>'select', 'options'=>[
                'contentphoto' => 'Content + Photo',
                'imagebuttons' => 'Image Buttons',
                'tradingcards' => 'Trading Cards',
                ]],
            'image-position'=>['label'=>'Image Position', 'type'=>'select', 'default'=>'top-right', 'options'=>[
                'top-left' => 'Top Left',
                'top-left-inline' => 'Top Left Inline',
                'bottom-left' => 'Bottom Left',
                'top-right' => 'Top Right',
                'top-right-inline' => 'Top Right Inline',
                'bottom-right' => 'Bottom Right',
                ]],
            'image-size'=>['label'=>'Image Size', 'type'=>'toggle', 'default'=>'half', 'toggles'=>[
                'half' => 'Full',
                'large' => 'Large',
                'medium' => 'Medium',
                'small' => 'Small',
                'tiny' => 'Tiny',
                ]],
            ],
        ];


    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
