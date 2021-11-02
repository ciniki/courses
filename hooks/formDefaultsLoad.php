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
        // FIXME: Link instructors to customers module
        //

        //
        // Load the instructor if they exist
        //


/*
        if( isset($form['sections']) ) {
            foreach($form['sections'] as $sid => $section) {
                if( isset($section['fields']) ) {
                    foreach($section['fields'] as $fid => $field) {
                        //
                        // Check if field ref is for customer and if the reference exists
                        //
                        if( isset($field['field_ref']) && $field['field_ref'] != '' 
                            && preg_match("/^ciniki\.customers\.customer\.([^\.]+)/", $field['field_ref'], $m)
                            && isset($refs[$field['field_ref']])
                            ) {
                            if( isset($customer[$m[1]]) ) {
                                $form['sections'][$sid]['fields'][$fid]['default'] = $customer[$m[1]];
                            }
                        }
                    }
                }
            }
        }  */
    }

    return array('stat'=>'ok', 'form'=>$form);
}
?>
