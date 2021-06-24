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
function ciniki_courses_templates_offeringRegistrationsPDF(&$ciniki, $tnid, $offering_id, $tenant_details, $courses_settings) {

    //
    // Load the class
    //
    $rsp = array('stat'=>'ok');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'offeringLoad');
    $rc = ciniki_courses_offeringLoad($ciniki, $tnid, $offering_id, 
        array('classes'=>'yes', 'instructors'=>'yes', 'reglist'=>'yes'));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['offering']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.120', 'msg'=>'Unable to find requested class'));
    }
    $offering = $rc['offering'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'customers', 'hooks', 'customerDetails');
    //
    // Load TCPDF library
    //
    require_once($ciniki['config']['ciniki.core']['lib_dir'] . '/tcpdf/tcpdf.php');

    class MYPDF extends TCPDF {
        public $left_margin = 18;
        public $right_margin = 18;
        public $top_margin = 18;
        //Page header
        public $header_image = null;
        public $header_name = '';
        public $header_addr = array();
        public $header_details = array();
        public $header_height = 0;      // The height of the image and address
        public $tenant_details = array();
        public $courses_settings = array();
        public $footer_text = '';

        public function Header() {
        }

        // Page footer
        public function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(150, 10, $this->footer_text,
                0, false, 'L', 0, '', 0, false, 'T', 'M');
            $this->Cell(0, 10, 'Page ' . $this->pageNo().'/'.$this->getAliasNbPages(), 
                0, false, 'C', 0, '', 0, false, 'T', 'M');
        }
    }

    //
    // Start a new document
    //
    $pdf = new MYPDF('P', PDF_UNIT, 'LETTER', true, 'UTF-8', false);

    //
    // Figure out the header tenant name and address information
    //
    $pdf->header_height = 0;
    $pdf->header_name = '';
    $pdf->header_height += (count($pdf->header_addr)*5);

    //
    // Load the header image
    //
    if( isset($courses_settings['default-header-image']) && $courses_settings['default-header-image'] > 0 ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadImage');
        $rc = ciniki_images_loadImage($ciniki, $tnid, 
            $courses_settings['default-header-image'], 'original');
        if( $rc['stat'] == 'ok' ) {
            $pdf->header_image = $rc['image'];
        }
    }

    $pdf->tenant_details = $tenant_details;
    $pdf->courses_settings = $courses_settings;

//  print "<pre>" . print_r($offering, true) . "</pre>";

    $instructors = '';
    if( isset($offering['instructors']) ) {
        foreach($offering['instructors'] as $iid => $instructor) {
            $instructors .= ($instructors!=''?', ':'') . $instructor['name'];
        }
    }

    $dates = '';
    if( isset($offering['classes']) ) { 
        if( count($offering['classes']) > 0 ) {
            $first_date = array_shift($offering['classes']);
            $dates .= $first_date['class_date'];
            if( count($offering['classes']) > 0 ) {
                $last_date = array_pop($offering['classes']);
                $dates .= " - " . $last_date['class_date'];
            }
        }
    }

    //
    // Setup the title
    //
    $title = ($offering['course_code'] != '' ? $offering['course_code'] : '');
    $title .= ($offering['course_name'] != '' ? ($title != '' ? ' - ' : '') . $offering['course_name'] : '');
    $session = ($offering['offering_code'] != '' ? $offering['offering_code'] : '');
    $session .= ($offering['offering_name'] != '' ? ($session != '' ? ' - ' : '') . $offering['offering_name'] : '');

    //
    // Determine the header details
    //
    $pdf->header_details = array(
        array('label'=>'Date(s)', 'value'=>$dates),
        array('label'=>'Course', 'value'=>$title),
        array('label'=>'Session', 'value'=>$session),
        array('label'=>'Instructors', 'value'=>$instructors),
        array('label'=>'Registrations', 'value'=>count($offering['registrations'])),
        );

    //
    // Setup the PDF basics
    //
    $pdf->SetCreator('Ciniki');
    $pdf->SetAuthor($tenant_details['name']);
    $pdf->footer_text = $title . ($title != '' && $session != '' ? ' - ' : '') . $session;
    $pdf->SetTitle($title . ($title != '' && $session != '' ? ' - ' : '') . $session);
    $pdf->SetSubject('');
    $pdf->SetKeywords('');

    // set margins
    $pdf->SetMargins($pdf->left_margin, $pdf->top_margin + $pdf->header_height, $pdf->right_margin);
    $pdf->SetHeaderMargin($pdf->top_margin);


    // set font
    $pdf->SetFont('times', 'BI', 10);
    $pdf->SetCellPadding(2);

    // add a page
    $pdf->AddPage();
    $pdf->SetFillColor(255);
    $pdf->SetTextColor(0);
    $pdf->SetDrawColor(51);
    $pdf->SetLineWidth(0.15);

    //
    // Add header image if specified
    //
    if( $pdf->header_image != null ) {
        $height = $pdf->header_image->getImageHeight();
        $width = $pdf->header_image->getImageWidth();
        $image_ratio = $width/$height;
        $img_width = 70;
        $available_ratio = $img_width/40;
        // Check if the ratio of the image will make it too large for the height,
        // and scaled based on either height or width.
        if( $available_ratio < $image_ratio ) {
            $pdf->Image('@'.$pdf->header_image->getImageBlob(), $pdf->left_margin, $pdf->top_margin, 
                $img_width, 0, 'JPEG', '', 'TL', 2, '150');
        } else {
            $pdf->Image('@'.$pdf->header_image->getImageBlob(), $pdf->left_margin, $pdf->top_margin, 
                0, 42, 'JPEG', '', 'TL', 2, '150');
        }
    }

    //
    // Add the information to the first page
    //
    $w = array(25, 75);
    foreach($pdf->header_details as $detail) {
        $pdf->SetFillColor(224);
        $pdf->SetX($pdf->left_margin + 80);
        $pdf->SetFont('', 'B');
        $pdf->Cell($w[0], 6, $detail['label'], 1, 0, 'L', 1);
        $pdf->SetFillColor(255);
        $pdf->SetFont('', '');
        $pdf->Cell($w[1], 6, $detail['value'], 1, 0, 'L', 1);
        $pdf->Ln();
    }
    $pdf->Ln();

    $parents = 'no';
    foreach($offering['registrations'] as $reg) {
        if( $reg['student_id'] != $reg['customer_id'] ) {
            $parents = 'yes';
        }
    }

    //
    // Add the registrations
    //
    if( $parents == 'yes' ) {
        $w = array(73, 73, 34);
    } else {
        $w = array(130, 0, 50);
    }
    $pdf->SetFillColor(224);
    $pdf->SetFont('', 'B');
    $pdf->SetCellPadding(2);
    $pdf->Cell($w[0], 6, 'Student', 1, 0, 'L', 1);
    if( $parents == 'yes' ) {
        $pdf->Cell($w[1], 6, 'Parent', 1, 0, 'L', 1);
    }
    $pdf->Cell($w[2], 6, 'Status', 1, 0, 'L', 1);
    $pdf->Ln();
    $pdf->SetFillColor(236);
    $pdf->SetTextColor(0);
    $pdf->SetFont('');

    $fill=0;
    foreach($offering['registrations'] as $reg) {
        //
        // Get the student information, so it can be added to the form and verified
        //
        $student_information = $reg['student_name'] . "\n";
        if( $reg['student_id'] > 0 ) {
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, 
                array('customer_id'=>$reg['student_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            if( isset($rc['customer']) ) {
                $customer = $rc['customer'];
                $student_information = $customer['first'] . ' ' . $customer['last'] . "\n";
                if( isset($customer['phones']) ) {
                    $phones = "";
                    foreach($customer['phones'] as $phone) {
                        if( count($customer['phones']) > 1 ) {
                            $p = $phone['phone_label'] . ': ' . $phone['phone_number'];
                            if( $pdf->getStringWidth($phones . ', ' . $p) > $w[1] ) {
                                $phones .= "\n" . $p;
                            } else {
                                $phones .= ($phones!=''?', ':'') . $p;
                            }
                        } else {
                            $phones .= $phone['phone_number'];
                        }
                    }
                    if( count($customer['phones']) > 0 ) {
                        $student_information .= $phones . "\n";
                    } else {
                        $student_information .= "Phone: \n";
                    }
                }
                if( isset($customer['emails']) ) {
                    $emails = '';
                    $comma = '';
                    foreach($customer['emails'] as $e => $email) {
                        $emails .= ($emails!=''?', ':'') . $email['email']['address'];
                    }
                    $student_information .= $emails . "\n";
                }
            }
        }
        // Check if date of birth should be added 
        if( ($offering['flags']&0x01) == 0x01 && $reg['customer_id'] != $reg['student_id'] && $reg['student_age'] != '' && $reg['student_age'] > 0 ) {
            $student_information .= "Age: " . $reg['student_age'] . " (" . $customer['birthdate'] . ") ";
        }
        if( $reg['notes'] != '' ) {
            $student_information .= "\n" . $reg['notes'];
        }

        // If a tenant, then convert "Payment Required" to "Invoice"
        $tenant_information = '';
        if( $reg['student_id'] != $reg['customer_id'] ) {
            $rc = ciniki_customers_hooks_customerDetails($ciniki, $tnid, 
                array('customer_id'=>$reg['customer_id'], 'addresses'=>'yes', 'phones'=>'yes', 'emails'=>'yes'));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $tenant_information = $reg['customer_name'] . "\n";
            if( isset($rc['customer']) ) {
                $customer = $rc['customer'];
                if( isset($customer['phones']) ) {
                    $phones = "";
                    foreach($customer['phones'] as $phone) {
                        if( count($customer['phones']) > 1 ) {
                            $p = $phone['phone_label'] . ': ' . $phone['phone_number'];
                            if( $pdf->getStringWidth($phones . ', ' . $p) > $w[2] ) {
                                $phones .= "\n" . $p;
                            } else {
                                $phones .= ($phones!=''?', ':'') . $p;
                            }
                        } else {
                            $phones .= $phone['phone_number'];
                        }
                    }
                    if( count($customer['phones']) > 0 ) {
                        $tenant_information .= $phones . "\n";
                    } else {
                        $tenant_information .= "Phone: \n";
                    }
                }
                if( isset($customer['emails']) ) {
                    $emails = '';
                    $comma = '';
                    foreach($customer['emails'] as $e => $email) {
                        $emails .= ($emails!=''?', ':'') . $email['email']['address'];
                    }
                    $tenant_information .= $emails . "\n";
                }
            }
        }

        // Calculate the line height required
        $lh = $pdf->getStringHeight($w[1], $tenant_information);
        $lh1 = $pdf->getStringHeight($w[1], $student_information);
        $lh2 = $pdf->getStringHeight($w[2], $reg['invoice_status_text']);
        if( $lh1 > $lh ) { $lh = $lh1; }
        if( $lh2 > $lh ) { $lh = $lh2; }

        // Check if we need a page break
        if( $pdf->getY() > ($pdf->getPageHeight() - $lh - $pdf->top_margin - $pdf->header_height) ) {
            $pdf->AddPage();
            $pdf->SetFillColor(224);
            $pdf->SetFont('', 'B');
            $pdf->Cell($w[0], 6, 'Student', 1, 0, 'L', 1);
            if( $parents == 'yes' ) {
                $pdf->Cell($w[1], 6, 'Parent', 1, 0, 'L', 1);
            }
            $pdf->Cell($w[2], 6, 'Status', 1, 0, 'L', 1);
            $pdf->Ln();
            $pdf->SetFillColor(236);
            $pdf->SetTextColor(0);
            $pdf->SetFont('');
        }

        $pdf->MultiCell($w[0], $lh, $student_information, 1, 'L', $fill, 0);
        if( $parents == 'yes' ) {
            $pdf->MultiCell($w[1], $lh, $tenant_information, 1, 'L', $fill, 0);
        }
        $pdf->MultiCell($w[2], $lh, $reg['invoice_status_text'], 1, 'L', $fill, 0);
        $pdf->Ln();
//        if( isset($reg['notes']) && $reg['notes'] != '' ) {
//            $pdf->MultiCell(180, 6, $reg['notes'], 1, 'L', $fill, 0);
//            $pdf->Ln();
//        }

        $fill=!$fill;
    }

    return array('stat'=>'ok', 'offering'=>$offering, 'pdf'=>$pdf);
}
?>
