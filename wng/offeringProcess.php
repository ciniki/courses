<?php
//
// Description
// -----------
// This function will process the list of upcoming sessions
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_wng_offeringProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.courses']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.325', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.326', 'msg'=>"Nothing specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    $now = new DateTime('now', new DateTimezone($intl_timezone)); 

    if( !isset($s['offering-id']) || $s['offering-id'] == '' || $s['offering-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.313', 'msg'=>'Nothing specified'));
    }

    //
    // Load the offering
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'wng', 'offeringLoad');
    $rc = ciniki_courses_wng_offeringLoad($ciniki, $tnid, $request, $s['offering-id']);
    if( $rc['stat'] != 'ok' ) {
        $blocks[] = [
            'type' => 'msg',
            'level' => 'error',
            'content' => 'Unable to find requested session',
            ];
        return array('stat'=>'ok', 'blocks'=>$blocks);
    }
    $offering = $rc['offering'];

    if( $offering['image-id'] == 0 && $offering['course_image_id'] > 0 ) {
        $offering['image-id'] = $offering['course_image_id'];
    }

    if( isset($s['name-format']) && $s['name-format'] == 'course' ) {
        $offering['name'] = $offering['course_name'];
    } elseif( isset($s['name-format']) && $s['name-format'] == 'offering' ) {
        $offering['name'] = $offering['offering_name'];
    } else {
        $offering['name'] = $offering['course_name'] . ' - ' . $offering['offering_name'];
    } 

    $blocks[] = array(
        'type' => 'contentphoto', 
        'level' => 1,
        'title' => $offering['name'],
        'subtitle' => $offering['condensed_date'],
        'content' => $offering['content'],
        'image-id' => $offering['image-id'],
        );
  
    if( isset($offering['prices']) && count($offering['prices']) > 0 ) {
//    error_log(print_r($offering['prices'],true));
        $blocks[] = [
            'type' => 'pricelist',
            'prices' => $offering['prices'],
            ];
    }

    //
    // Check for title, content
    //
/*    if( isset($s['content']) && $s['content'] != '' ) {
        $blocks[] = array(
            'type' => 'text', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : '',
            'content' => $s['content'],
            );
    } elseif( isset($s['title']) && $s['title'] != '' ) {
        $blocks[] = array(
            'type' => 'title', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => $s['title'],
            );
    }

    //
    // Display the offerings
    //
    if( isset($s['layout']) && $s['layout'] == 'tradingcards' ) {
        $blocks[] = array(
            'type' => 'tradingcards',
            'class' => 'courses-offerings',
            'size' => '25',
            'image-ratio' => isset($s['image-ratio']) ? $s['image-ratio'] : '1-1',
            'items' => $offerings,
            );
    } else {
        $blocks[] = array(
            'type' => 'flexcards',
            'class' => 'courses-offerings',
            'image-ratio' => isset($s['image-ratio']) ? $s['image-ratio'] : '1-1',
            'items' => $offerings,
            );
        
    } */

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
