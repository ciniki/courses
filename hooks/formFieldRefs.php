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
        'ciniki.courses.course.short_description' => array('module'=>$module, 'type'=>'textarea', 'name'=>'Exhibit Synopsis'),
        'ciniki.courses.course.long_description' => array('module'=>$module, 'type'=>'textarea', 'name'=>'Exhibit Description'),
        // Instructor
        'ciniki.courses.instructor.primary_image_id' => array('module'=>$module, 'type'=>'image', 'name'=>'Instructor Image'),
        'ciniki.courses.instructor.short_bio' => array('module'=>$module, 'type'=>'textarea', 'name'=>'Instructor Synopsis'),
        'ciniki.courses.instructor.long_bio' => array('module'=>$module, 'type'=>'textarea', 'name'=>'Instructor Bio'),
        'ciniki.courses.instructor.url' => array('module'=>$module, 'type'=>'url', 'name'=>'Instructor Website'),
        );

    return array('stat'=>'ok', 'refs'=>$refs);
}
?>
