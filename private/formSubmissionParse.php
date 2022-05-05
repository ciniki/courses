<?php
//
// Description
// -----------
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_formSubmissionParse(&$ciniki, $tnid, $submission_id) {

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionLoad');
    $rc = ciniki_forms_submissionLoad($ciniki, $tnid, $submission_id);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.ags.255', 'msg'=>'Submission not found', 'err'=>$rc['err']));
    }
    $form = isset($rc['form']) ? $rc['form'] : array();
    if( !isset($form['sections']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.ags.254', 'msg'=>'Incomplete form'));
    }

    $instructor = array();
    $course = array();
    if( isset($form['submission']['customer_id']) ) {
        $instructor['customer_id'] = $form['submission']['customer_id'];
    }

    //
    // Extract the items
    //
    $item = array();        // Global item across all sections (typically items are only in repeat sections
    foreach($form['sections'] as $sid => $section) {
        if( isset($section['fields']) && isset($section['flags']) && ($section['flags']&0x01) == 0x01 ) {
/*            for($i = 1; $i <= $section['max_repeats']; $i++) {
                $ritem = array(); // repeat section item
                foreach($section['fields'] as $fid => $field) {
                    if( !isset($form['submission']['data'][$field['id']]['repeats'][$i]['data']) ) {
                        continue;
                    }
                    $data = $form['submission']['data'][$field['id']]['repeats'][$i]['data'];
                    if( $field['field_ref'] == 'ciniki.ags.item.primary_image_id' && $data > 0 ) {
                        $ritem['primary_image_id'] = $data; 
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.name' && $data != '' ) {
                        $ritem['name'] = $data;
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.flags.1' && $data == 'on' ) {
                        $ritem['flags'] = 0x01;
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.unit_amount' && $data != '' ) {
                        $ritem['unit_amount'] = preg_replace("/[^0-9\.]/", '', $data);
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.description' && $data != '' ) {
                        $ritem['description'] = $data;
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.creation_year' && $data != '' ) {
                        $ritem['creation_year'] = $data;
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.medium' && $data != '' ) {
                        $ritem['medium'] = $data;
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.size' && $data != '' ) {
                        $ritem['size'] = $data;
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.framed_size' && $data != '' ) {
                        $ritem['framed_size'] = $data;
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.current_condition' && $data != '' ) {
                        $ritem['current_condition'] = $data;
                    }
                    elseif( $field['field_ref'] == 'ciniki.ags.item.notes' && $data != '' ) {
                        $ritem['notes'] = $data;
                    }
                }
                //
                // Add the item if a name is specified
                //
                if( $ritem['name'] != '' ) {
                    $exhibit['items'][] = $ritem;
                }
            } */
        }
        elseif( isset($section['fields']) ) {
            foreach($section['fields'] as $fid => $field) {
                if( isset($form['submission']['data'][$field['id']]['data']) ) {
                    $data = $form['submission']['data'][$field['id']]['data'];
                    //
                    // Item Fields
                    //
                    if( $field['field_ref'] == 'ciniki.courses.course.primary_image_id' && $data > 0 ) {
                        $course['primary_image_id'] = $data; 
                    }
                    elseif( $field['field_ref'] == 'ciniki.courses.instructor.primary_image_id' && $data > 0 ) {
                        $instructor['primary_image_id'] = $data; 
                    }
                    elseif( preg_match("/^ciniki.courses.course.(.*)$/", $field['field_ref'], $m) && $data != '' ) {    
                        $course[$m[1]] = $data; 
                    }
                    elseif( preg_match("/^ciniki.courses.instructor.(.*)$/", $field['field_ref'], $m) && $data != '' ) {    
                        $instructor[$m[1]] = $data; 
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'course'=>$course, 'instructor'=>$instructor);
}
?>
