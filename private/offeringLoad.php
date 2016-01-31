<?php
//
// Description
// -----------
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business.
// offering_id:			The ID of the offering to get.
//
// Returns
// -------
//
function ciniki_courses_offeringLoad($ciniki, $business_id, $offering_id, $args) {
	//
	// Load the business intl settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
	$time_format = ciniki_users_timeFormat($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');

	//
	// Get the main information
	//
	$strsql = "SELECT ciniki_course_offerings.id, "
		. "ciniki_course_offerings.course_id, "
		. "ciniki_course_offerings.name AS offering_name, "
		. "ciniki_course_offerings.permalink, "
		. "ciniki_course_offerings.status, "
		. "ciniki_course_offerings.status AS status_text, "
		. "ciniki_course_offerings.webflags, "
		. "ciniki_course_offerings.reg_flags, "
		. "ciniki_course_offerings.num_seats, "
		. "IF((ciniki_course_offerings.webflags&0x01)=1,'Hidden', 'Visible') AS web_visible, "
		. "ciniki_courses.name AS course_name, "
		. "ciniki_courses.code, "
		. "ciniki_courses.primary_image_id, "
		. "ciniki_courses.level, "
		. "ciniki_courses.type, "
		. "ciniki_courses.category, "
		. "ciniki_courses.short_description, "
		. "ciniki_courses.long_description "
		. "FROM ciniki_course_offerings "
		. "LEFT JOIN ciniki_courses ON (ciniki_course_offerings.course_id = ciniki_courses.id "
			. "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
		. "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_course_offerings.id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.courses', array(
		array('container'=>'offerings', 'fname'=>'id', 'name'=>'offering',
			'fields'=>array('id', 'offering_name', 'permalink', 'status', 'status_text', 
				'reg_flags', 'num_seats',
				'webflags', 'web_visible', 
				'primary_image_id', 'course_id', 'course_name', 'code', 'level', 'type', 
				'category', 'short_description', 'long_description'),
			'maps'=>array('status_text'=>array('10'=>'Active', '60'=>'Deleted'))),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['offerings']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'3055', 'msg'=>'Unable to find offering'));
	}
	$offering = $rc['offerings'][0]['offering'];

	if( isset($args['classes']) && $args['classes'] == 'yes' ) {
		$strsql = "SELECT id, "
			. "IFNULL(DATE_FORMAT(class_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS class_date, "
			. "IFNULL(DATE_FORMAT(start_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS start_time, "
			. "IFNULL(DATE_FORMAT(end_time, '" . ciniki_core_dbQuote($ciniki, $time_format) . "'), '') AS end_time "
			. "FROM ciniki_course_offering_classes "
			. "WHERE ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_course_offering_classes.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
			. "ORDER BY ciniki_course_offering_classes.class_date ASC "
			. "";
		$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'classes', 'fname'=>'id',
				'fields'=>array('id', 'class_date', 'start_time', 'end_time')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['classes']) ) {
			$offering['classes'] = $rc['classes'];
		} else {
			$offering['classes'] = array();
		}
	}

	//
	// Get the list of prices for the course, if requested
	//
	if( isset($args['prices']) && $args['prices'] == 'yes' ) {
		//
		// Get the price list for the event
		//
		$strsql = "SELECT id, name, unit_amount "
			. "FROM ciniki_course_offering_prices "
			. "WHERE ciniki_course_offering_prices.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
			. "AND ciniki_course_offering_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "ORDER BY ciniki_course_offering_prices.name "
			. "";
		$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.offerings', array(
			array('container'=>'prices', 'fname'=>'id',
				'fields'=>array('id', 'name', 'unit_amount')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['prices']) ) {
			$offering['prices'] = $rc['prices'];
			foreach($offering['prices'] as $pid => $price) {
				$offering['prices'][$pid]['price']['unit_amount_display'] = numfmt_format_currency(
					$intl_currency_fmt, $price['price']['unit_amount'], $intl_currency);
			}
		} else {
			$offering['prices'] = array();
		}
	}

	//
	// Get the list of instructors for a course, if requested
	//
	if( isset($args['instructors']) && $args['instructors'] == 'yes' ) {
		$strsql = "SELECT ciniki_course_offering_instructors.id, "
			. "ciniki_course_instructors.id AS instructor_id, "
			. "CONCAT_WS(' ', ciniki_course_instructors.first, ciniki_course_instructors.last) AS name "
			. "FROM ciniki_course_offering_instructors "
			. "LEFT JOIN ciniki_course_instructors ON (ciniki_course_offering_instructors.instructor_id = ciniki_course_instructors.id "
				. "AND ciniki_course_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
			. "WHERE ciniki_course_offering_instructors.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_course_offering_instructors.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
			. "ORDER BY ciniki_course_instructors.last "
			. "";
		$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'instructors', 'fname'=>'id',
				'fields'=>array('id', 'instructor_id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['instructors']) ) {
			$offering['instructors'] = $rc['instructors'];
		} else {
			$offering['instructors'] = array();
		}
	}

	if( isset($args['files']) && $args['files'] == 'yes' ) {
		$strsql = "SELECT ciniki_course_offering_files.id, "
			. "ciniki_course_files.id AS file_id, "
			. "ciniki_course_files.name "
			. "FROM ciniki_course_offering_files "
			. "LEFT JOIN ciniki_course_files ON (ciniki_course_offering_files.file_id = ciniki_course_files.id "
				. "AND ciniki_course_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
			. "WHERE ciniki_course_offering_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_course_offering_files.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
			. "ORDER BY ciniki_course_files.name "
			. "";
		$rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'files', 'fname'=>'id',
				'fields'=>array('id', 'file_id', 'name')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['files']) ) {
			$offering['files'] = $rc['files'];
		} else {
			$offering['files'] = array();
		}
	}

	//
	// Get the number of registrations, if set for the offering
	//
	$offering['seats_sold'] = 0;
	if( isset($args['registrations']) && isset($offering['reg_flags']) && ($offering['reg_flags']&0x03) > 0 ) {
		$strsql = "SELECT 'num_seats', SUM(num_seats) AS num_seats "	
			. "FROM ciniki_course_offering_registrations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.courses', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['num_seats']) ) {
			$offering['seats_sold'] = $rc['num']['num_seats'];
		}
	}

    //
    // Get the list of registrations
    //
    if( isset($args['reglist']) && $args['reglist'] == 'yes' && isset($offering['reg_flags']) && ($offering['reg_flags']&0x03) > 0 ) {
        if( isset($ciniki['business']['modules']['ciniki.sapos']) ) {
            //
            // Load the status maps for the text description of each status
            //
            ciniki_core_loadMethod($ciniki, 'ciniki', 'sapos', 'private', 'maps');
            $rc = ciniki_sapos_maps($ciniki);
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $status_maps = $rc['maps']['invoice']['payment_status'];
            $status_maps[0] = 'No Invoice';

            $strsql = "SELECT ciniki_course_offering_registrations.id, "
                . "ciniki_course_offering_registrations.customer_id, "
                . "ciniki_course_offering_registrations.student_id, "
                . "IFNULL(c1.type, '0') AS customer_type, "
                . "IFNULL(c1.display_name, '') AS customer_name, "
                . "IFNULL(c2.type, '0') AS student_type, "
                . "IFNULL(c2.display_name, '') AS student_name, "
                . "IFNULL(c2.display_name, IFNULL(c1.display_name, '')) AS sort_name, "
                . "ciniki_course_offering_registrations.num_seats, "
                . "ciniki_course_offering_registrations.invoice_id, "
                . "ciniki_sapos_invoices.payment_status AS invoice_status, "
                . "IFNULL(ciniki_sapos_invoices.payment_status, 0) AS invoice_status_text "
                . "FROM ciniki_course_offering_registrations "
                . "LEFT JOIN ciniki_customers AS c1 ON ("
                    . "ciniki_course_offering_registrations.customer_id = c1.id "
                    . "AND c1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS c2 ON ("
                    . "ciniki_course_offering_registrations.student_id = c2.id "
                    . "AND c2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "LEFT JOIN ciniki_sapos_invoices ON (ciniki_course_offering_registrations.invoice_id = ciniki_sapos_invoices.id "
                    . "AND ciniki_sapos_invoices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "WHERE ciniki_course_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
                . "ORDER BY sort_name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'registrations', 'fname'=>'id',
                    'fields'=>array('id', 'customer_id', 'customer_type', 'student_id', 'student_type', 'customer_name', 'student_name', 'num_seats', 
                        'invoice_id', 'invoice_status', 'invoice_status_text'),
                    'maps'=>array('invoice_status_text'=>$status_maps)),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3056', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
            }
            if( !isset($rc['registrations']) ) {
                $offering['registrations'] = array();
            } else {
                $offering['registrations'] = $rc['registrations'];
            }
        } else {
            $strsql = "SELECT ciniki_course_offering_registrations.id, "
                . "ciniki_course_offering_registrations.customer_id, "
                . "ciniki_course_offering_registrations.student_id, "
                . "IFNULL(c1.type, '0') AS customer_type, "
                . "IFNULL(c1.display_name, '') AS customer_name, "
                . "IFNULL(c2.type, '0') AS student_type, "
                . "IFNULL(c2.display_name, '') AS student_name, "
                . "IFNULL(c2.display_name, IFNULL(c1.display_name, '')) AS sort_name, "
                . "ciniki_course_offering_registrations.num_seats, "
                . "ciniki_course_offering_registrations.invoice_id "
                . "FROM ciniki_course_offering_registrations "
                . "LEFT JOIN ciniki_customers AS c1 ON ("
                    . "ciniki_course_offering_registrations.customer_id = c1.id "
                    . "AND c1.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "LEFT JOIN ciniki_customers AS c2 ON ("
                    . "ciniki_course_offering_registrations.student_id = c2.id "
                    . "AND c2.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                    . ") "
                . "WHERE ciniki_course_offering_registrations.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
                . "AND ciniki_course_offering_registrations.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering_id) . "' "
                . "ORDER BY sort_name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'registrations', 'fname'=>'id',
                    'fields'=>array('id', 'customer_id', 'customer_type', 'student_id', 'customer_name', 'student_type', 'student_name', 'num_seats', 'invoice_id')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3057', 'msg'=>'Unable to get the list of registrations', 'err'=>$rc['err']));
            }
            if( !isset($rc['registrations']) ) {
                $offering['registrations'] = array();
            } else {
                $offering['registrations'] = $rc['registrations'];
            }
        }
    }

	
	return array('stat'=>'ok', 'offering'=>$offering);
}
?>