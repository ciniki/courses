<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get courses for.
//
// Returns
// -------
//
function ciniki_courses_hooks_uiSettings($ciniki, $tnid, $args) {

    //
    // Setup the default response
    //
    $rsp = array('stat'=>'ok', 'menu_items'=>array(), 'settings_menu_items'=>array());

    //
    // Check permissions for what menu items should be available
    //
    if( isset($ciniki['tenant']['modules']['ciniki.courses'])
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['ciniki.courses'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5200,
            'label'=>'Programs', 
            'edit'=>array('app'=>'ciniki.courses.main'),
            );
        $rsp['menu_items'][] = $menu_item;
//        $rsp['archived_items'][] = $menu_item;

        //
        // To be removed when finished converting to programs
        //
//        $menu_item = array(
//            'priority'=>5199,
//            'label'=>'Courses', 
//            'edit'=>array('app'=>'ciniki.courses.offerings'),
//            );
//        $rsp['menu_items'][] = $menu_item;
//        $rsp['archived_items'][] = $menu_item;
    } 

    if( isset($ciniki['tenant']['modules']['ciniki.courses'])
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
