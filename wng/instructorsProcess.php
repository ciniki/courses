<?php
//
// Description
// -----------
// This function will process a wng request for the blog module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_wng_instructorsProcess(&$ciniki, $tnid, &$request, $section) {

    if( !isset($ciniki['tenant']['modules']['ciniki.courses']) ) {
        return array('stat'=>'404', 'err'=>array('code'=>'ciniki.courses.320', 'msg'=>"I'm sorry, the page you requested does not exist."));
    }

    //
    // Make sure a valid section was passed
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.321', 'msg'=>"No course specified"));
    }
    $s = $section['settings'];
    $blocks = array();

    //
    // Load the instructors
    //
    $strsql = "SELECT instructors.id, "
        . "instructors.customer_id, "
        . "instructors.first, "
        . "instructors.last, "
        . "instructors.permalink, "
        . "instructors.primary_image_id, "
        . "instructors.webflags, "
        . "instructors.short_bio AS synopsis, "
        . "instructors.full_bio AS description "
        . "FROM ciniki_course_instructors AS instructors "
        . "WHERE (instructors.webflags&0x01) = 0 " // Visible
        . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "ORDER BY instructors.first, instructors.last "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'instructors', 'fname'=>'permalink', 
            'fields'=>array('id', 'first', 'last', 'permalink', 'webflags', 
                'image-id'=>'primary_image_id', 'description', 'permalink',
                )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.322', 'msg'=>'Unable to load instructor', 'err'=>$rc['err']));
    }
    $instructors = isset($rc['instructors']) ? $rc['instructors'] : array();
    foreach($instructors as $iid => $instructor) {
        $instructors[$iid]['display_name'] = trim($instructor['first'] . ' ' . $instructor['last']);
    }

    if( count($instructors) > 0 ) {
        //
        // Add the title block
        //
        $blocks[] = array(
            'type' => 'title', 
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'title' => isset($s['title']) ? $s['title'] : 'Instructors',
            );
        //
        // Add the instructors
        //
        if( isset($request['uri_split'][($request['cur_uri_pos']+1)]) ) {
            if( isset($instructors[$request['uri_split'][($request['cur_uri_pos']+1)]]) ) {
                $instructor = $instructors[$request['uri_split'][($request['cur_uri_pos']+1)]];
                $block = array(
                    'type' => 'contentphoto',
                    'title' => $instructor['display_name'],
                    'image-id' => $instructor['image-id'],
                    'content' => $instructor['description'],
                    'image-position' => (isset($s['image-position']) && $s['image-position'] != '' ? $s['image-position'] : ''),
                    'image-size' => (isset($s['image-size']) && $s['image-size'] != '' ? $s['image-size'] : ''),
                    );

                $blocks[] = $block;
                return array('stat'=>'ok', 'blocks'=>$blocks, 'stop'=>'yes', 'clear'=>'yes');
//            } else {
//                $blocks[] = array(
//                    'type' => 'msg', 
//                    'level' => 'error',
//                    'content' => 'Could not find the instructor you requested.', 
//                    );
            }
        }

        if( isset($s['layout']) && $s['layout'] == 'imagebuttons' ) {
            foreach($instructors as $iid => $instructor) {
                $instructors[$iid]['title'] = $instructor['display_name'];
                $instructors[$iid]['image-ratio'] = '1-1';
                $instructors[$iid]['title-position'] = 'overlay-bottomhalf';
                $instructors[$iid]['url'] = $request['page']['path'] . '/' . $instructor['permalink'];
            }
            $blocks[] = array(
                'type' => 'imagebuttons',
                'class' => 'courses-instructors',
                'items' => $instructors,
                );
        } 
        elseif( isset($s['layout']) && $s['layout'] == 'flexcards' ) {
            foreach($instructors as $iid => $instructor) {
                $instructors[$iid]['title'] = $instructor['display_name'];
                $instructors[$iid]['button-class'] = isset($s['button-class']) && $s['button-class'] != '' ? $s['button-class'] : 'button';
                $instructors[$iid]['button-1-text'] = 'Read Bio';
                $instructors[$iid]['button-1-url'] = $request['page']['path'] . '/' . $instructor['permalink'];
                $instructors[$iid]['url'] = $request['page']['path'] . '/' . $instructor['permalink'];
            }
            $blocks[] = array(
                'type' => 'flexcards',
                'class' => 'courses-instructors',
                'items' => $instructors,
                );
        } 
        elseif( isset($s['layout']) && $s['layout'] == 'tradingcards' ) {
            foreach($instructors as $iid => $instructor) {
                $instructors[$iid]['title'] = $instructor['display_name'];
                $instructors[$iid]['button-class'] = isset($s['button-class']) && $s['button-class'] != '' ? $s['button-class'] : 'button';
                $instructors[$iid]['button-1-text'] = 'Read Bio';
                $instructors[$iid]['button-1-url'] = $request['page']['path'] . '/' . $instructor['permalink'];
                $instructors[$iid]['url'] = $request['page']['path'] . '/' . $instructor['permalink'];
            }
            $blocks[] = array(
                'type' => 'tradingcards',
                'class' => 'courses-instructors',
                'size' => '25',
                'items' => $instructors,
                );
        } 
        else {
            $side = 'right';
            foreach($instructors as $instructor) {
                $blocks[] = array(
                    'type' => 'contentphoto', 
                    'image-position' => 'top-' . $side,
                    'title' => $instructor['display_name'],
                    'image-id' => (isset($instructor['image-id']) && $instructor['image-id'] > 0  ? $instructor['image-id'] : 0),
                    'image-position' => (isset($s['image-position']) && $s['image-position'] != '' ? $s['image-position'] : ''),
                    'image-size' => (isset($s['image-size']) && $s['image-size'] != '' ? $s['image-size'] : ''),
                    'content' => $instructor['description'],
                    );
                $side = $side == 'right' ? 'left' : 'right';
            } 
        }
    } else {
        $blocks[] = array(
            'type' => 'text', 
            'title' => isset($s['title']) ? $s['title'] : 'Instructors',
            'level' => $section['sequence'] == 1 ? 1 : 2,
            'content' => (isset($s['no-instructors-content']) && $s['no-instructors-content'] ? $s['no-instructors-content'] : "We don't currently have any instructors."),
            );
    } 

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
