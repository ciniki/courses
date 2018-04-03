<?php
//
// Description
// -----------
// This function returns the index details for an object
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant to get courses for.
//
// Returns
// -------
//
function ciniki_courses_hooks_webIndexObject($ciniki, $tnid, $args) {

    if( !isset($args['object']) || $args['object'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.99', 'msg'=>'No object specified'));
    }

    if( !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.106', 'msg'=>'No object ID specified'));
    }

    //
    // Setup the base_url for use in index
    //
    if( isset($args['base_url']) ) {
        $base_url = $args['base_url'];
    } else {
        $base_url = '/courses';
    }

    if( $args['object'] == 'ciniki.courses.offering' ) {
        $strsql = "SELECT offerings.id, "
            . "offerings.code, "
            . "courses.name AS title, "
            . "courses.permalink AS course_permalink, "
            . "offerings.permalink AS offering_permalink, "
            . "offerings.status, "
            . "offerings.webflags, "
            . "offerings.condensed_date, "
            . "courses.primary_image_id, "
            . "courses.short_description, "
            . "courses.long_description "
            . "FROM ciniki_course_offerings AS offerings "
            . "INNER JOIN ciniki_courses AS courses ON ("
                . "offerings.course_id = courses.id "
                . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . ") "
            . "WHERE offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'item');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.107', 'msg'=>'Object not found'));
        }
        if( !isset($rc['item']) ) {
            return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.courses.108', 'msg'=>'Object not found'));
        }

        //
        // Check if item is visible on website
        //
        if( $rc['item']['status'] != 10 ) {
            return array('stat'=>'ok');
        }
        if( ($rc['item']['webflags']&0x01) != 0 ) {
            return array('stat'=>'ok');
        }
        $object = array(
            'label'=>'Course',
            'title'=>$rc['item']['code'] . ' - ' . $rc['item']['title'],
            'subtitle'=>'',
            'meta'=>$rc['item']['condensed_date'],
            'primary_image_id'=>$rc['item']['primary_image_id'],
            'synopsis'=>$rc['item']['short_description'],
            'object'=>'ciniki.courses.offering',
            'object_id'=>$rc['item']['id'],
            'primary_words'=>$rc['item']['code'] . ' - ' . $rc['item']['title'],
            'secondary_words'=>$rc['item']['short_description'],
            'tertiary_words'=>$rc['item']['long_description'],
            'weight'=>20000,
            'url'=>$base_url 
//                . (isset($category_permalink) ? '/category/' . $category_permalink : '')
                . '/course/' . $rc['item']['course_permalink'] . '/' . $rc['item']['offering_permalink'] 
            );
        return array('stat'=>'ok', 'object'=>$object);
    }

    return array('stat'=>'ok');
}
?>
