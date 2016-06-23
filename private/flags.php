<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_flags($ciniki, $modules) {
    $flags = array(
//      0x01
        array('flag'=>array('bit'=>'1', 'name'=>'Course Codes')),
        array('flag'=>array('bit'=>'2', 'name'=>'Instructors')),
        array('flag'=>array('bit'=>'3', 'name'=>'Course Prices')),
        array('flag'=>array('bit'=>'4', 'name'=>'Course Files')),
//      0x10
        array('flag'=>array('bit'=>'5', 'name'=>'Course Types')),
//      array('flag'=>array('bit'=>'6', 'name'=>'')),
        array('flag'=>array('bit'=>'7', 'name'=>'Registrations')),
        array('flag'=>array('bit'=>'8', 'name'=>'Online Registrations')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
