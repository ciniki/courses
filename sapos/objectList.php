<?php
//
// Description
// ===========
// This method returns the list of objects that can be returned
// as invoice items.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_courses_sapos_objectList($ciniki, $business_id) {

    $objects = array(
        //
        // this object should only be added to carts
        //
        'ciniki.courses.offering' => array(
            'name' => 'Course',
            ),
        'ciniki.courses.offering_price' => array(
            'name' => 'Course Price',
            ),
        'ciniki.courses.offering_registration' => array(
            'name' => 'Course Registration',
            ),
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
