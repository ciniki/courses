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
function ciniki_courses_reportStudents($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_date'=>array('required'=>'yes', 'blank'=>'no', 'type'=>'date', 'name'=>'Start Date'), 
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'), 
        'output'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'json', 'name'=>'Output'), 
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.reportStudents'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

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
    // Get the list of registrations between the dates
    //
    $strsql = "SELECT registrations.id, "
        . "offerings.name AS offering_name, "
        . "offerings.code AS offering_code, "
        . "offerings.condensed_date AS condensed_date, "
        . "courses.name AS course_name, "
        . "courses.code AS course_code, "
        . "customers.id AS customer_id, "
        . "customers.display_name, "
        . "DATE_FORMAT(offerings.start_date, '%b %d, %Y') AS start_date, "
        . "DATE_FORMAT(offerings.end_date, '%b %d, %Y') AS end_date, "
        . "IFNULL(prices.unit_amount, 0) AS price_amount, "
        . "IFNULL(prices.name, '') AS price_name, "
        . "emails.email AS emails "
        . "FROM ciniki_course_offering_classes AS classes "
        . "INNER JOIN ciniki_course_offering_registrations AS registrations ON ("
            . "classes.offering_id = registrations.offering_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_course_offerings AS offerings ON ("
            . "classes.offering_id = offerings.id "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "INNER JOIN ciniki_courses AS courses ON ("
            . "offerings.course_id = courses.id "
            . "AND courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_invoice_items AS items ON ("
            . "registrations.invoice_id = items.invoice_id "
            . "AND items.object_id = registrations.id "
            . "AND items.object = 'ciniki.courses.offering_registration' "
            . "AND items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_offering_prices AS prices ON ("
            . "items.price_id = prices.id "
            . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "registrations.student_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customer_emails AS emails ON ("
            . "customers.id = emails.customer_id "
            . "AND emails.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE classes.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND classes.class_date >= '" . ciniki_core_dbQuote($ciniki, $args['start_date']) . "' "
        . "";
    if( isset($args['end_date']) && $args['end_date'] != '' && $args['end_date'] != '0000-00-00' ) {
        $strsql .= "AND classes.class_date <= '" . ciniki_core_dbQuote($ciniki, $args['end_date']) . "' ";
    }
    $strsql .= "ORDER BY customers.display_name, courses.name, offerings.name, registrations.id "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'customers', 'fname'=>'id', 
            'fields'=>array('id', 'customer_id', 'display_name', 'offering_name', 'offering_code', 'course_code', 'course_name', 
                'condensed_date', 'price_name', 'start_date', 'end_date', 'emails'),
            'lists'=>array('emails'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.112', 'msg'=>'Unable to load offering_registrations', 'err'=>$rc['err']));
    }
    $customers = isset($rc['customers']) ? $rc['customers'] : array();

    //
    // Output PDF version
    //
    if( $args['output'] == 'pdf' ) {
        //
        // FIXME: Add pdf output
        //
/*        $rc = ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'templates', 'offeringRegistrationsPDF');
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
        } */
    }

    //
    // Output Excel version
    //
    elseif( $args['output'] == 'excel' ) {
        require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

        $col = 0;
        $row = 1;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Student', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Code', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Course', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Session', false);
        $row++;
        foreach($customers as $student) {
            $col = 0;
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $student['display_name'], false);
            if( isset($student['emails']) ) {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $student['emails'], false);
            } else {
                $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, '', false);
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $student['offering_code'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $student['course_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $student['offering_name'], false);

            $row++;
        }
        $objPHPExcelWorksheet->getStyle('A1:E1')->getFont()->setBold(true);
        $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="students.xls"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        
        return array('stat'=>'exit');
    } 

    return array('stat'=>'ok', 'customers'=>$customers);
}
?>
