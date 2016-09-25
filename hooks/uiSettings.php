<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get courses for.
//
// Returns
// -------
//
function ciniki_courses_hooks_uiSettings($ciniki, $business_id, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['business']['modules']['ciniki.courses'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5200,
            'label'=>'Courses', 
            'edit'=>array('app'=>'ciniki.courses.offerings'),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    if( isset($ciniki['business']['modules']['ciniki.courses'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $rsp['settings_menu_items'][] = array('priority'=>5200, 'label'=>'Courses', 'edit'=>array('app'=>'ciniki.courses.settings'));
    }

    return $rsp;
}
?>
