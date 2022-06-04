<?php
//
// Description
// ===========
// This method returns the PDF or excel of the form submissions for the form attached to this offering.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
// Returns
// -------
//
function ciniki_courses_offeringRegistrationForms($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'), 
        'template'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Template'), 
        'output'=>array('required'=>'no', 'blank'=>'no', 'default'=>'pdf', 'name'=>'Output Format'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'checkAccess');
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringRegistrationForms'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    //
    // Load tenant details
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'tenantDetails');
    $rc = ciniki_tenants_tenantDetails($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['details']) && is_array($rc['details']) ) {   
        $tenant_details = $rc['details'];
    } else {
        $tenant_details = array();
    }

    //
    // Load the invoice settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_course_settings', 'tnid', $args['tnid'],
        'ciniki.courses', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['settings']) ) {
        $courses_settings = $rc['settings'];
    } else {
        $courses_settings = array();
    }

    //
    // Load offering
    //
    $strsql = "SELECT ciniki_course_offerings.id, "
        . "ciniki_course_offerings.course_id, "
        . "ciniki_course_offerings.name AS offering_name, "
        . "ciniki_course_offerings.code AS offering_code, "
        . "ciniki_course_offerings.permalink, "
        . "ciniki_course_offerings.status, "
        . "ciniki_course_offerings.status AS status_text, "
        . "ciniki_course_offerings.webflags, "
        . "ciniki_course_offerings.reg_flags, "
        . "ciniki_course_offerings.num_seats, "
        . "IF((ciniki_course_offerings.webflags&0x01)=1,'Hidden', 'Visible') AS web_visible, "
        . "ciniki_courses.name AS course_name, "
        . "ciniki_courses.code AS course_code, "
        . "ciniki_courses.primary_image_id, "
        . "ciniki_courses.level, "
        . "ciniki_courses.type, "
        . "ciniki_courses.category, "
        . "ciniki_courses.flags, "
        . "ciniki_courses.short_description, "
        . "ciniki_courses.long_description "
        . "FROM ciniki_course_offerings "
        . "LEFT JOIN ciniki_courses ON ("
            . "ciniki_course_offerings.course_id = ciniki_courses.id "
            . "AND ciniki_courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE ciniki_course_offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering',
            'fields'=>array('id', 'offering_name', 'offering_code', 'permalink', 'status', 'status_text', 
                'reg_flags', 'num_seats',
                'webflags', 'web_visible', 
                'primary_image_id', 'course_id', 'course_name', 'course_code', 'level', 'type', 
                'category', 'flags', 'short_description', 'long_description'),
            'maps'=>array('status_text'=>array('10'=>'Active', '60'=>'Deleted'))),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offerings']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.courses.3', 'msg'=>'Unable to find offering'));
    }
    $offering = $rc['offerings'][0]['offering'];

    //
    // Get the list of form submissions for this offering
    //
    $strsql = "SELECT submissions.id "
        . "FROM ciniki_course_offerings AS offerings "
        . "INNER JOIN ciniki_course_offering_registrations AS registrations ON ("
            . "offerings.id = registrations.offering_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_form_submissions AS submissions ON ("
            . "registrations.student_id = submissions.customer_id "
            . "AND offerings.form_id = submissions.form_id "
            . "AND submissions.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['offering_id']) . "' "
        . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
    $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.courses', 'submissions', 'id');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.267', 'msg'=>'Unable to load the list of ', 'err'=>$rc['err']));
    }
    $submission_ids = isset($rc['submissions']) ? $rc['submissions'] : array();

    $title = '';
    if( $offering['course_code'] != '' ) {
        $title .= $offering['course_code'] . ' - ' . $offering['course_name'];
    } elseif( $offering['offering_code'] != '' ) {
        $title .= $offering['offering_code'] . ' - ' . $offering['course_name'];
    } else {
        $title .= $offering['course_name'];
    }
    if( $offering['course_code'] != '' && $offering['offering_code'] != '' ) {
        $title .= $offering['offering_code'] . ' - ' . $offering['offering_name'];
    } else {
        $title .= ' - ' . $offering['offering_name'];
    }
    $title .= ' - Forms';
    $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $title));

    //
    // Output PDF version
    //
    if( $args['output'] == 'pdf' ) {
        $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'templates', 'submissionsPDF');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $fn = $rc['function_call'];

        $rc = $fn($ciniki, $args['tnid'], array(
            'title' => $title,
            'tenant_details' => $tenant_details, 
            'submission_ids' => $submission_ids,
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($filename . '.pdf', 'I');
        }
    }

    //
    // Output Excel version
    //
    elseif( $args['output'] == 'excel' ) {
        $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'forms', 'templates', 'submissionsExcel');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $fn = $rc['function_call'];

        $rc = $fn($ciniki, $args['tnid'], array(
            'title' => $title,
            'tenant_details' => $tenant_details, 
            'submission_ids' => $submission_ids,
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        
        if( isset($rc['excel']) ) {
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $filename . '.xls"');
            header('Cache-Control: max-age=0');
            
            $objWriter = PHPExcel_IOFactory::createWriter($rc['excel'], 'Excel5');
            $objWriter->save('php://output');
        }
    }

    return array('stat'=>'exit');
}
?>
