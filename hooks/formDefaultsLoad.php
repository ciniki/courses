<?php
//
// Description
// -----------
// Return the list of available field refs for ciniki.forms module.
// 
// Arguments
// ---------
// ciniki: 
// tnid:            The ID of the current tenant.
// 
// Returns
// ---------
// 
function ciniki_courses_hooks_formDefaultsLoad(&$ciniki, $tnid, $args) {
   
    if( !isset($args['form']) ) {
        return array('stat'=>'ok');
    }
    $form = $args['form'];
  
    //
    // Process the customer if specified
    //
    if( isset($form['customer_id']) && $form['customer_id'] != '' && $form['customer_id'] > 0 ) {
        //
        // Check for instructor details
        //
        $strsql = "SELECT instructors.id, "
            . "instructors.primary_image_id, "
            . "instructors.short_bio, "
            . "instructors.full_bio, "
            . "instructors.url "
            . "FROM ciniki_course_instructors AS instructors "
            . "WHERE instructors.customer_id = '" . ciniki_core_dbQuote($ciniki, $form['customer_id']) . "' "
            . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'instructor');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.226', 'msg'=>'Unable to load instructor', 'err'=>$rc['err']));
        }
        if( isset($rc['instructor']) ) {
            $instructor = $rc['instructor'];

            //
            // Load the refs
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'hooks', 'formFieldRefs');
            $rc = ciniki_courses_hooks_formFieldRefs($ciniki, $tnid, array());
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $refs = $rc['refs'];        

            if( isset($form['sections']) ) {
                foreach($form['sections'] as $sid => $section) {
                    if( isset($section['fields']) ) {
                        foreach($section['fields'] as $fid => $field) {
                            //
                            // Check if field ref is for instructor and if the reference exists
                            //
                            if( isset($field['prefill_ref']) && $field['prefill_ref'] != '' 
                                && preg_match("/^ciniki\.courses\.instructor\.([^\.]+)/", $field['prefill_ref'], $m)
                                && isset($refs[$field['prefill_ref']])
                                ) {
                                if( isset($instructor[$m[1]]) ) {
                                    $form['sections'][$sid]['fields'][$fid]['default'] = $instructor[$m[1]];
                                }
                            }
                        }
                    }
                }
            } 
        }
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
