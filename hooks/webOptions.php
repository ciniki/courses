<?php
//
// Description
// -----------
// This function will return the list of options for the module that can be set for the website.
//
// Arguments
// ---------
// ciniki:
// settings:        The web settings structure.
// business_id:     The ID of the business to get courses for.
//
// args:            The possible arguments for posts
//
//
// Returns
// -------
//
function ciniki_courses_hooks_webOptions(&$ciniki, $business_id, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['business']['modules']['ciniki.courses']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.77', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Get the settings from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_web_settings', 'business_id', $business_id, 'ciniki.web', 'settings', 'page-courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['settings']) ) {
        $settings = array();
    } else {
        $settings = $rc['settings'];
    }


    $options = array();
    //
    // FIXME: Add settings
    //
/*    $options[] = array(
        'label'=>'Name',
        'setting'=>'page-courses-name', 
        'type'=>'text',
        'value'=>(isset($settings['page-courses-name'])?$settings['page-courses-name']:''),
        'hint'=>'Courses',
    );
    $options[] = array(
        'label'=>'Upcoming Courses',
        'setting'=>'page-courses-upcoming-active', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-courses-upcoming-active'])?$settings['page-courses-upcoming-active']:'no'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        );
    $options[] = array(
        'label'=>'Current Courses',
        'setting'=>'page-courses-current-active', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-courses-current-active'])?$settings['page-courses-current-active']:'no'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        );
    $options[] = array(
        'label'=>'Past Courses',
        'setting'=>'page-courses-past-active', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-courses-past-active'])?$settings['page-courses-past-active']:'no'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        );
    $options[] = array(
        'label'=>'Display Catalog Download',
        'setting'=>'page-courses-catalog-download-active', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-courses-catalog-download-active'])?$settings['page-courses-catalog-download-active']:'no'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        );
    $options[] = array(
        'label'=>'Display Course Level',
        'setting'=>'page-courses-level-display', 
        'type'=>'toggle',
        'value'=>(isset($settings['page-courses-level-display'])?$settings['page-courses-level-display']:'no'),
        'toggles'=>array(
            array('value'=>'no', 'label'=>'No'),
            array('value'=>'yes', 'label'=>'Yes'),
            ),
        ); */

    $pages['ciniki.courses'] = array('name'=>'Courses', 'options'=>$options);

    return array('stat'=>'ok', 'pages'=>$pages);
}
?>