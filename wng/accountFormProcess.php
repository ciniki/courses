<?php
//
// Description
// -----------
// This function will process the juror voting for forms.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_wng_accountFormProcess(&$ciniki, $tnid, &$request, $item) {

    $blocks = array();

    if( !isset($item['ref']) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Request error, please contact us for help.."
            )));
    }

    if( !isset($request['session']['customer']['id']) || $request['session']['customer']['id'] <= 0 ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "You must be logged in to view your programs."
            )));
    }

    //
    // Load the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    
    //
    // Check if registration form is being requested
    //
    if( !isset($request['uri_split'][5]) || $request['uri_split'][4] != 'form' ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Error, unable to process request."
            )));
    }

    $course_permalink = $request['uri_split'][2];
    $offering_permalink = $request['uri_split'][3];
    $student_permalink = $request['uri_split'][5];

    $dt = new DateTime('now', new DateTimezone($intl_timezone));

    $base_url = $request['base_url'] . '/' . join('/', $request['uri_split']);

    //
    // Load the registration requested
    //
    $strsql = "SELECT registrations.id AS id, "
        . "courses.id AS course_id, "
        . "courses.name AS course_name, "
        . "courses.permalink AS course_permalink, "
        . "courses.flags AS course_flags, "
        . "courses.status AS course_status, "
        . "offerings.id AS offering_id, "
        . "offerings.name AS offering_name, "
        . "offerings.permalink AS offering_permalink, "
        . "offerings.status AS offering_status, "
        . "offerings.condensed_date, "
        . "IF(offerings.end_date < '" . ciniki_core_dbQuote($ciniki, $dt->format('Y-m-d')) . "', 'yes', 'no') AS ended, "
        . "offerings.form_id, "
        . "registrations.student_id, "
        . "students.display_name AS student_name, "
        . "students.permalink AS student_permalink "
        . "FROM ciniki_course_offering_registrations AS registrations "
        . "INNER JOIN ciniki_course_offerings AS offerings ON ("
            . "registrations.offering_id = offerings.id "
            . "AND offerings.permalink = '" . ciniki_core_dbQuote($ciniki, $offering_permalink) . "' "
            . "AND offerings.status = 10 "  // Active
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND courses.permalink = '" . ciniki_core_dbQuote($ciniki, $course_permalink) . "' "
            . "AND (courses.status = 30 OR courses.status = 70 ) "  // Active or private
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "INNER JOIN ciniki_customers AS students ON ("
            . "registrations.student_id = students.id "
            . "AND students.permalink = '" . ciniki_core_dbQuote($ciniki, $student_permalink) . "' "
            . "AND students.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . ") "
        . "WHERE ("
            . "registrations.customer_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . "OR registrations.student_id = '" . ciniki_core_dbQuote($ciniki, $request['session']['customer']['id']) . "' "
            . ") "
        . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ("
            . "offerings.end_date > NOW() " // Open offering
            . "OR ((courses.flags&0x10) = 0x10) " // Timeless course
            . ") "  
        . "ORDER BY offerings.start_date, courses.name, offerings.name, students.display_name "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'registration');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.265', 'msg'=>'Unable to load registration', 'err'=>$rc['err']));
    }
    if( !isset($rc['registration']) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Error, registration not found."
            )));
    }
    $registration = $rc['registration'];

    //
    // Check form and student is specified
    //
    if( !isset($registration['form_id']) || $registration['form_id'] < 1 ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Error, registration form not found."
            )));
    }
    if( !isset($registration['student_id']) || $registration['student_id'] < 1 ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Error, registration form not found."
            )));
    }

    //
    // Load the form
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formLoad');
    $rc = ciniki_forms_wng_formLoad($ciniki, $tnid, $request, $registration['form_id'], $registration['student_id']);
    if( $rc['stat'] != 'ok' || !isset($rc['form']['id']) ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Error, invalid registration form."
            )));
    }
    $form = $rc['form'];

    //
    // Load the submission
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionLoad');
    $rc = ciniki_forms_wng_submissionLoad($ciniki, $tnid, $request, $form);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'ok', 'blocks'=>array(array(
            'type' => 'msg', 
            'level' => 'error',
            'content' => "Error, invalid submission."
            )));
    }

    //
    // Apply defaults if no submission
    //
    if( $form['submission_id'] == 0 || $form['submission_id'] == 'new' ) {
        //
        // Create new submission
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
        $rc = ciniki_core_objectAdd($ciniki, $tnid, 'ciniki.forms.submission', array(
            'form_id' => $form['id'],
            'object' => $form['object'],
            'object_id' => $form['object_id'],
            'customer_id' => $form['customer_id'],
            'invoice_id' => 0,
            'status' => 10,
            'label' => 'New Submission',
            ), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.272', 'msg'=>'Unable to add the submission', 'err'=>$rc['err']));
        }
        $form['submission_id'] = $rc['id'];
        $form['submission_uuid'] = $rc['uuid'];

        //
        // Apply the defaults
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formDefaultsApply');
        $rc = ciniki_forms_wng_formDefaultsApply($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>array(array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "Error, invalid submission."
                )));
        }

        //
        // Reload the submission to get the default values
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionLoad');
        $rc = ciniki_forms_wng_submissionLoad($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>array(array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "Error, invalid submission."
                )));
        }
    }

    //
    // Check if form submitted
    //
    if( isset($_POST['action']) && $_POST['action'] == 'submit' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'formPOSTApply');
        $rc = ciniki_forms_wng_formPOSTApply($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>array(array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "Error, unable to parse form."
                )));
        }

        //
        // Save the submission updates
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'wng', 'submissionSave');
        $rc = ciniki_forms_wng_submissionSave($ciniki, $tnid, $request, $form);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>array(array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "Error, unable to save form."
                )));
        }

        //
        // Validate the submission
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'private', 'submissionValidate');
        $rc = ciniki_forms_submissionValidate($ciniki, $tnid, $form);
        if( $rc['stat'] == 'fail' && isset($rc['problems']) ) {
            $problem_list = "You must complete all the required field in the form. The following fields are missing:\n\n";
            foreach($rc['problems'] as $pid => $problem) {
                $problem_list .= $problem . "\n";
            }
            //
            // Form nvalidated, Update status
            //
            $dt_now = new DateTime('now', new DateTimezone('UTC'));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $form['submission']['id'], array(
                'status' => 10,
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'ok', 'blocks'=>array(array(
                    'type' => 'msg', 
                    'level' => 'error',
                    'content' => "Error, unable to submit form."
                    )));
            }
        }
        elseif( $rc['stat'] != 'ok' ) {
            return array('stat'=>'ok', 'blocks'=>array(array(
                'type' => 'msg', 
                'level' => 'error',
                'content' => "Error, unable to save form."
                )));
        }
        else {
            //
            // Form validated, update submitted dt
            //
            $dt_now = new DateTime('now', new DateTimezone('UTC'));
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
            $rc = ciniki_core_objectUpdate($ciniki, $tnid, 'ciniki.forms.submission', $form['submission']['id'], array(
                'status' => 90,
                'dt_last_submitted' => $dt_now->format('Y-m-d H:i:s'),
                ), 0x04);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'ok', 'blocks'=>array(array(
                    'type' => 'msg', 
                    'level' => 'error',
                    'content' => "Error, unable to submit form."
                    )));
            }
            //
            // Redirect to list
            //
            Header("Location: " . $request['base_url'] . '/' . $request['uri_split'][0] . '/' . $request['uri_split'][1]);
            return array('stat'=>'exit');
        }
    }

    //
    // Add the submit button
    //
    $form['sections']['submit']['fields']['submit'] = array(
        'id' => 'submit',
        'ftype' => 'submit',
        'label' => (isset($form['submit_label']) && $form['submit_label'] != '' ? $form['submit_label'] : 'Submit'),
        );

    //
    // Display the form
    //
    $blocks[] = array(  
        'title' => $form['name'],
        'type' => 'form',
        'section-selector' => 'no',
        'problem-list' => isset($problem_list) ? $problem_list : '',
        'form-id' => $form['id'],
        'guidelines' => $form['guidelines'],
        'form-sections' => $form['sections'],
        );
    
    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
