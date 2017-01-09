<?php
//
// Description
// ===========
// This method will produce a PDF of the class.
//
// Arguments
// ---------
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_courses_templates_offeringRegistrationsExcel(&$ciniki, $business_id, $offering_id, $business_details, $courses_settings) {

    //
    // Load the class
    //
    $rsp = array('stat'=>'ok');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringLoad');
    $rc = ciniki_courses_offeringLoad($ciniki, $business_id, $offering_id, 
        array('classes'=>'yes', 'instructors'=>'yes', 'reglist'=>'yes'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.69', 'msg'=>'Unable to find requested class'));
    }
    $offering = $rc['offering'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');

    require($ciniki['config']['core']['lib_dir'] . '/PHPExcel/PHPExcel.php');
    $objPHPExcel = new PHPExcel();
    $objPHPExcelWorksheet = $objPHPExcel->setActiveSheetIndex(0);

    $col = 0;
    $row = 1;
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Student', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
    // Check if date of birth should be added 
    if( ($offering['flags']&0x01) == 0x01 ) {
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Age', false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Birthdate', false);
    }
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Parent', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Phone', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Email', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Status', false);
    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, 'Notes', false);

    if( ($offering['flags']&0x01) == 0x01 ) {
        $objPHPExcelWorksheet->getStyle('A1:J1')->getFont()->setBold(true);
    } else {
        $objPHPExcelWorksheet->getStyle('A1:H1')->getFont()->setBold(true);
    }

    $row++;
    foreach($offering['registrations'] as $reg) {
        //
        // Get the student information, so it can be added to the form and verified
        //
        $col = 0;
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $reg['student_name'], false);
        if( $reg['student_id'] > 0 ) {
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $business_id, 
                array('customer_id'=>$reg['student_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
//          print "<pre>" . print_r($rc, true) . "</pre>";
            if( isset($rc['customer']) ) {
                $customer = $rc['customer'];
                if( isset($customer['phones']) ) {
                    $phones = "";
                    foreach($customer['phones'] as $phone) {
                        if( count($customer['phones']) > 1 ) {
                            $p = $phone['phone_label'] . ': ' . $phone['phone_number'];
                            $phones .= ($phones!=''?', ':'') . $p;
                        } else {
                            $phones .= $phone['phone_number'];
                        }
                    }
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $phones, false);
                }
                $col++;
                if( isset($customer['emails']) ) {
                    $emails = '';
                    $comma = '';
                    foreach($customer['emails'] as $e => $email) {
                        $emails .= ($emails!=''?', ':'') . $email['email']['address'];
                    }
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $emails, false);
                }
                $col++;
            }
            if( ($offering['flags']&0x01) == 0x01 ) {
                if( $reg['customer_id'] != $reg['student_id'] && $reg['student_age'] != '' && $reg['student_age'] > 0 ) {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $reg['student_age'], false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $customer['birthdate'], false);
                } else {
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, '', false);
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, '', false);
                }
            }
        } else {
            if( ($offering['flags']&0x01) == 0x01 ) {
                $col+=5;
            } else {
                $col+=3;
            }
        }

        // If a business, then convert "Payment Required" to "Invoice"
        $business_information = '';
        if( $reg['customer_type'] == 2 || $reg['student_id'] != $reg['customer_id'] ) {
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $business_id, 
                array('customer_id'=>$reg['customer_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $reg['customer_name'], false);
            if( isset($rc['customer']) ) {
                $customer = $rc['customer'];
                if( isset($customer['phones']) ) {
                    $phones = "";
                    foreach($customer['phones'] as $phone) {
                        if( count($customer['phones']) > 1 ) {
                            $p = $phone['phone_label'] . ': ' . $phone['phone_number'];
                            $phones .= ($phones!=''?', ':'') . $p;
                        } else {
                            $phones .= $phone['phone_number'];
                        }
                    }
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $phones, false);
                }
                $col++;
                if( isset($customer['emails']) ) {
                    $emails = '';
                    $comma = '';
                    foreach($customer['emails'] as $e => $email) {
                        $emails .= ($emails!=''?', ':'') . $email['email']['address'];
                    }
                    $objPHPExcelWorksheet->setCellValueByColumnAndRow($col, $row, $emails, false);
                }
                $col++;
            }
        } else {    
            $col+=3;
        }

        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $reg['invoice_status_text'], false);
        $objPHPExcelWorksheet->setCellValueByColumnAndRow($col++, $row, $reg['notes'], false);
        $row++;
    }

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

    return array('stat'=>'ok', 'offering'=>$offering, 'excel'=>$objPHPExcel);
}
?>
