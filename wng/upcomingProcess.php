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
function ciniki_courses_wng_upcomingProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.courses']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.323', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.324', 'msg'=>"No course specified"));
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

    //
    // Check if type specified
    //
    $course_type_sql = '';
    if( isset($s['course-type']) && $s['course-type'] != '' ) {
        $course_type_sql = "AND courses.type = '" . ciniki_core_dbQuote($ciniki, $s['course-type']) . "' ";
    }

    //
    // Load the upcoming sessions
    //
    $strsql = "SELECT offerings.id, "
        . "offerings.name, "
        . "offerings.permalink, "
        . "offerings.status, "
        . "offerings.condensed_date, "
        . "offerings.dt_end_reg, "
        . "offerings.primary_image_id AS image_id, "
        . "offerings.synopsis, "
        . "courses.id AS course_id, "
        . "courses.name AS course_name, "
        . "courses.permalink AS course_permalink, "
        . "courses.primary_image_id AS course_image_id "
        . "FROM ciniki_course_offerings AS offerings "
        . "INNER JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND courses.status = 30 "
            . $course_type_sql
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") ";
    if( isset($s['include-current']) && $s['include-current'] == 'yes' ) {
        $strsql .= "WHERE offerings.end_date >= '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' ";
    } else {
        $strsql .= "WHERE offerings.start_date > '" . ciniki_core_dbQuote($ciniki, $now->format('Y-m-d')) . "' ";
    }
    $strsql .= "AND (offerings.webflags&0x01) = 0 " // Visible
        . "AND offerings.status = 10 "
        . "ORDER BY offerings.start_date, offerings.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'id', 
            'fields'=>array('id', 'name', 'permalink', 'status', 'condensed_date', 'dt_end_reg', 'image-id'=>'image_id', 'synopsis',
                'course_id', 'course_name', 'course_permalink', 'course_image_id'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.312', 'msg'=>'Unable to load offerings', 'err'=>$rc['err']));
    }
    $offerings = isset($rc['offerings']) ? $rc['offerings'] : array();

    //
    // Process the offerings
    //
    foreach($offerings as $oid => $offering) {
        //
        // Check if offering selected
        //
        if( isset($request['uri_split'][($request['cur_uri_pos']+1)]) 
            && $request['uri_split'][($request['cur_uri_pos']+1)] == $offering['course_permalink'] 
            && $request['uri_split'][($request['cur_uri_pos']+2)] == $offering['permalink'] 
            ) {
            $section['settings']['offering-id'] = $offering['id'];
            $request['cur_uri_pos']++;
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'wng', 'offeringProcess');
            $rc = ciniki_courses_wng_offeringProcess($ciniki, $tnid, $request, $section);
            $rc['stop'] = 'yes';
            $rc['clear'] = 'yes';
            return $rc;
        }
        if( $offering['image-id'] == 0 && $offering['course_image_id'] > 0 ) {
            $offerings[$oid]['image-id'] = $offering['course_image_id'];
        }
        $offerings[$oid]['title'] = "{$offering['course_name']} - {$offering['name']}";
        $offerings[$oid]['url'] = "{$request['ssl_domain_base_url']}{$request['page']['path']}/{$offering['course_permalink']}/{$offering['permalink']}";
        $offerings[$oid]['link-text'] = isset($s['button-text']) && $s['button-text'] != '' ? $s['button-text'] : "More Information";
    }

    //
    // Check for title, content
    //
    if( isset($s['content']) && $s['content'] != '' ) {
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
        
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
