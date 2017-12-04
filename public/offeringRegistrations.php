<?php
//
// Description
// ===========
// This method returns all the information for a offering (a group of offerings at the same time location)
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
// Returns
// -------
//
function ciniki_courses_offeringRegistrations($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'offering_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Class'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringRegistrations'); 
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
    // Output PDF version
    //
    if( $args['output'] == 'pdf' ) {
        $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'templates', 'offeringRegistrationsPDF');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $fn = $rc['function_call'];

        $rc = $fn($ciniki, $args['tnid'], $args['offering_id'], $tenant_details, $courses_settings);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        $title = $rc['offering']['code'] . '_' . $rc['offering']['course_name'] . '_' . $rc['offering']['course_name'];

        $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $title));
        if( isset($rc['pdf']) ) {
            $rc['pdf']->Output($filename . '.pdf', 'D');
        }
    }

    //
    // Output Excel version
    //
    elseif( $args['output'] == 'excel' ) {
        $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'templates', 'offeringRegistrationsExcel');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        $fn = $rc['function_call'];

        $rc = $fn($ciniki, $args['tnid'], $args['offering_id'], $tenant_details, $courses_settings);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        $title = $rc['offering']['code'] . '_' . $rc['offering']['course_name'] . '_' . $rc['offering']['course_name'];

        $filename = preg_replace('/[^a-zA-Z0-9_]/', '', preg_replace('/ /', '_', $title));

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
