<?php
//
// Description
// -----------
// This function will return the calendar options for the this module.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get exhibitions for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_courses_hooks_calendarsWebOptions(&$ciniki, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.courses']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.67', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    $settings = $args['settings'];

    $options = array();
    $options[] = array(
        'label'=>'Course Display Times',
        'setting'=>'ciniki-courses-display-times',
        'type'=>'toggle',
        'value'=>(isset($settings['ciniki-courses-display-times'])?$settings['ciniki-courses-display-times']:'no'),
        'toggles'=>array(
            array('value'=>'none', 'label'=>'None'),
            array('value'=>'start', 'label'=>'Start'),
            array('value'=>'startend', 'label'=>'Start - End'),
            ),
    );

    return array('stat'=>'ok', 'options'=>$options);
}
?>
