<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_maps($ciniki) {
    $maps = array();
    $maps['course'] = array(
        'status' => array(
            10 => 'Draft',
            30 => 'Active',
            70 => 'Private',
            90 => 'Archived',
            ),
        );
    $maps['offering'] = array(
        'status' => array(
            10 => 'Active',
            60 => 'Cancelled',
            90 => 'Archived',
            ),
        );
    $maps['instructor'] = array(
        'status' => array(
            10 => 'Active',
            90 => 'Archived',
            ),
        );
    $maps['price'] = array(
        'available_to'=>array(
            0x01=>'Public',
            0x02=>'Private',
            0x10=>'Customers',
            0x20=>'Members',
            0x40=>'Dealers',
            0x80=>'Distributors',
        ));
    $maps['offering_notification'] = array(
        'ntrigger' => array(
            '20' => 'Payment Received',
            '40' => 'After Payment',
            '60' => 'Offering Start',
            '80' => 'Offering End',
            '90' => 'Each Class',
            '94' => 'First Class',
            '95' => 'Last Class',
            '96' => 'Other Classes',
            ),
        'ntype' => array(
            '10' => 'Email',
            '20' => 'SMS',
            ),
        'status' => array(
            '0' => 'Inactive',
            '10' => 'Require Approval',
            '20' => 'Auto Send',
            ),
        );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
