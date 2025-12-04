<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_wng_coursePricesProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.courses']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.316', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.317', 'msg'=>"No festival specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Make sure a festival was specified
    //
    if( !isset($s['course-id']) || $s['course-id'] == '' || $s['course-id'] == 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.318', 'msg'=>"No program specified"));
    }

    //
    // Load the course 
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'wng', 'courseDetails');
    $rc = ciniki_courses_wng_courseDetails($ciniki, $tnid, $request, $s['course-id']);
    if( $rc['stat'] != 'ok' ) {
    $blocks[] = ['type'=>'html', 'html'=>"<pre>" . print_r($rc, true) . "</pre>"];
    return array('stat'=>'ok', 'blocks'=>$blocks);
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.315', 'msg'=>'', 'err'=>$rc['err']));
    }
    $course = $rc['course'];

    if( !isset($course['offerings']) ) {
        return array('stat'=>'ok', 'blocks'=>[[]]);
    }

    //
    // Build the price list
    //
    $prices = [];
    foreach($course['offerings'] as $offering) {
        if( isset($offering['prices']) ) {
            foreach($offering['prices'] as $price) {
                $price['name'] = $offering['name'] . ($price['name'] != '' ? " - {$price['name']}" : '');
                $prices[] = $price;
            }
        }
    }

    if( count($prices) > 0 ) {
        $blocks[] = [
            'type' => 'pricelist',
            'title' => isset($s['title']) ? $s['title'] : '',
            'prices' => $prices,
            ];
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
