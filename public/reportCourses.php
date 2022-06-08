<?php
//
// Description
// ===========
// This method returns a report for the program offered between the dates and the details about them.
//
// Arguments
// ---------
// api_key:
// auth_token:
// 
// Returns
// -------
//
function ciniki_courses_reportCourses($ciniki) {
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.reportCourses'); 
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
    $strsql = "SELECT courses.id AS course_id, "
        . "courses.name AS course_name, "
        . "offerings.id AS offering_id, "
        . "offerings.code AS offering_code, "
        . "offerings.name AS offering_name, "
        . "courses.level, "
        . "courses.type, "
        . "courses.category, "
        . "courses.medium, "
        . "courses.ages, "
        . "COUNT(IFNULL(registrations.id, 0)) AS num_registrations, "
        . "offerings.num_seats, "
        . "DATE_FORMAT(offerings.start_date, '%b %d, %Y') AS start_date, "
        . "DATE_FORMAT(offerings.end_date, '%b %d, %Y') AS end_date, "
        . "MAX(IFNULL(invoice_items.total_amount, 0)) AS max_price, "
        . "SUM(IFNULL(invoice_items.total_amount, 0)) AS total_revenue "
        . "FROM ciniki_courses AS courses "
        . "INNER JOIN ciniki_course_offerings AS offerings ON ("
            . "courses.id = offerings.course_id "
            . "AND offerings.start_date >= '" . ciniki_core_dbQuote($ciniki, $args['start_date']) . "' "
            . "AND offerings.end_date <= '" . ciniki_core_dbQuote($ciniki, $args['end_date']) . "' "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_offering_registrations AS registrations ON ("
            . "offerings.id = registrations.offering_id "
            . "AND registrations.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_sapos_invoice_items AS invoice_items ON ("
            . "invoice_items.object = 'ciniki.courses.offering_registration' "
            . "AND invoice_items.object_id = registrations.id "
            . "AND invoice_items.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "GROUP BY offerings.id "
        . "ORDER BY offerings.start_date, courses.name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'offering_id', 
            'fields'=>array('course_id', 'course_name', 'offering_id', 'offering_code', 'offering_name', 
                'level', 'type', 'category', 'medium', 'ages', 'num_registrations', 'num_seats', 
                'start_date', 'end_date', 'max_price', 'total_revenue'),
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.268', 'msg'=>'Unable to load offerings', 'err'=>$rc['err']));
    }
    $offerings = isset($rc['offerings']) ? $rc['offerings'] : array();

    //
    // Load instructors
    //
    $strsql = "SELECT offerings.id, "
        . "instructors.id AS instructor_id, "
        . "IFNULL(customers.display_name, CONCAT_WS(' ', instructors.first, instructors.last)) AS display_name "
        . "FROM ciniki_courses AS courses "
        . "INNER JOIN ciniki_course_offerings AS offerings ON ("
            . "courses.id = offerings.course_id "
            . "AND offerings.start_date >= '" . ciniki_core_dbQuote($ciniki, $args['start_date']) . "' "
            . "AND offerings.end_date <= '" . ciniki_core_dbQuote($ciniki, $args['end_date']) . "' "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_offering_instructors AS oi ON ("
            . "offerings.id = oi.offering_id "
            . "AND oi.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_course_instructors AS instructors ON ("
            . "oi.instructor_id = instructors.id "
            . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "LEFT JOIN ciniki_customers AS customers ON ("
            . "instructors.customer_id = customers.id "
            . "AND customers.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") "
        . "WHERE courses.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "ORDER BY offerings.id, customers.display_name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
    $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
        array('container'=>'offerings', 'fname'=>'id', 'fields'=>array('id', 'display_name')),
        array('container'=>'instructors', 'fname'=>'instructor_id', 'fields'=>array('id', 'display_name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.268', 'msg'=>'Unable to load offerings', 'err'=>$rc['err']));
    }
    $instructors = isset($rc['offerings']) ? $rc['offerings'] : array();

    //
    // Merge instructors into offerings
    //
    foreach($offerings as $oid => $offering) {
        $names = '';
        if( isset($instructors[$offering['offering_id']]['instructors']) ) {
            foreach($instructors[$offering['offering_id']]['instructors'] as $instructor) {
                if( $instructor['display_name'] != '' ) {
                    $names .= ($names != '' ? ', ' : '') . $instructor['display_name'];
                }
            }
        }
        $offerings[$oid]['instructors'] = $names;
    }

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
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Program', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Code', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Session', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Level', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Type', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Category', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Medium', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Ages', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Instructor', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Start', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'End', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Attendance', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Spots', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Cost', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Revenue', false);
        $row++;
        foreach($offerings as $offering) {
            $col = 0;
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['course_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['offering_code'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['offering_name'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['level'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['type'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['category'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['medium'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['ages'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['instructors'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['start_date'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['end_date'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['num_registrations'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['num_seats'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['max_price'], false);
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $offering['total_revenue'], false);
            $objPHPExcelWorksheet->getStyle('N' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);
            $objPHPExcelWorksheet->getStyle('O' . $row)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD_SIMPLE);

            $row++;
        }
        $objPHPExcelWorksheet->getStyle('A1:O1')->getFont()->setBold(true);
        $objPHPExcelWorksheet->getColumnDimension('A')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('B')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('C')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('D')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('E')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('F')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('G')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('H')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('I')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('J')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('K')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('L')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('M')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('N')->setAutoSize(true);
        $objPHPExcelWorksheet->getColumnDimension('O')->setAutoSize(true);
        $objPHPExcelWorksheet->freezePane('O2');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="programs.xls"');
        header('Cache-Control: max-age=0');
        
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        
        return array('stat'=>'exit');
    } 

    return array('stat'=>'ok', 'offerings'=>$offerings);
}
?>
