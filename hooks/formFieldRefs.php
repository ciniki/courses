<?php
//
// Description
// -----------
// Return the list of available field refs for ciniki.forms module.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_hooks_formFieldRefs(&$ciniki, $tnid, $args) {
    
    $module = 'Programs';
    $refs = array(
        // Course
        'ciniki.courses.course.name' => array('module'=>$module, 'type'=>'text', 'name'=>'Program Name'),
        'ciniki.courses.course.primary_image_id' => array('module'=>$module, 'type'=>'image', 'name'=>'Program Image'),
        'ciniki.courses.course.short_description' => array('module'=>$module, 'type'=>'textarea', 'name'=>'Program Synopsis'),
        'ciniki.courses.course.long_description' => array('module'=>$module, 'type'=>'textarea', 'name'=>'Program Description'),
        'ciniki.courses.course.materials_list' => array('module'=>$module, 'type'=>'textarea', 'name'=>'Program Materials List'),
        'ciniki.courses.course.level' => array('module'=>$module, 'type'=>'select', 'name'=>'Program Level'),
        'ciniki.courses.course.type' => array('module'=>$module, 'type'=>'select', 'name'=>'Program Type'),
        'ciniki.courses.course.category' => array('module'=>$module, 'type'=>'select', 'name'=>'Program Category'),
        'ciniki.courses.course.medium' => array('module'=>$module, 'type'=>'select', 'name'=>'Program Medium'),
        'ciniki.courses.course.ages' => array('module'=>$module, 'type'=>'select', 'name'=>'Program Ages'),
        // Instructor
        'ciniki.courses.instructor.primary_image_id' => array('module'=>$module, 'type'=>'image', 'name'=>'Instructor Image'),
        'ciniki.courses.instructor.short_bio' => array('module'=>$module, 'type'=>'textarea', 'name'=>'Instructor Synopsis'),
        'ciniki.courses.instructor.full_bio' => array('module'=>$module, 'type'=>'textarea', 'name'=>'Instructor Bio'),
        'ciniki.courses.instructor.url' => array('module'=>$module, 'type'=>'url', 'name'=>'Instructor Website'),
        );

    return array('stat'=>'ok', 'refs'=>$refs);
}
?>
