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

    if( $offering['course_content'] != '' && $offering['content'] != '' ) {
        $offering['content'] = $offering['course_content'] . $offering['content'];
    }

    $blocks[] = array(
        'type' => 'contentphoto', 
        'level' => 1,
        'title' => $offering['name'],
        'subtitle' => $offering['condensed_date'],
        'subsubtitle' => isset($offering['location']) ? preg_replace("/\n/", "<br>", $offering['location']) : '',
        'content' => $offering['content'],
        'image-id' => $offering['image-id'],
        'image-position' => isset($s['offering-image-position']) ? $s['offering-image-position'] : 'top-right',
        'image-size' => isset($s['offering-image-size']) ? $s['offering-image-size'] : 'half',
        );
  
    if( isset($offering['prices']) && count($offering['prices']) > 0 ) {
        $blocks[] = [
            'type' => 'pricelist',
            'prices' => $offering['prices'],
            ];
    }

    if( isset($offering['images']) && count($offering['images']) > 0 ) {
        $blocks[] = [
            'type' => 'title',
            'level' => 2,
            'title' => 'Gallery',
            ];
        $blocks[] = [
            'type' => 'gallery',
            'layout' => 'originals',
            'items' => $offering['images'],
            ];
    }

    if( isset($offering['instructors']) && count($offering['instructors']) > 0 ) {
        $blocks[] = [
            'type' => 'title',
            'level' => 2,
            'title' => 'Instructors',
            ];
        foreach($offering['instructors'] as $instructor) {
            $blocks[] = [
                'type' => 'contentphoto',
                'title' => $instructor['name'],
                'level' => 3,
                'image-id' => $instructor['image-id'],
                'image-position' => (isset($s['instructor-image-position']) && $s['instructor-image-position'] != '' ? $s['instructor-image-position'] : ''),
                'image-size' => (isset($s['instructor-image-size']) && $s['instructor-image-size'] != '' ? $s['instructor-image-size'] : ''),
                'content' => $instructor['full_bio'],
                ];
        }
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
