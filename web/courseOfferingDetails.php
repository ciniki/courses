<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_courses_web_courseOfferingDetails($ciniki, $settings, $business_id, $course_permalink, $offering_permalink) {
	
	//
	// Load INTL settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
	$intl_currency = $rc['settings']['intl-default-currency'];

	//
	// Load the offering details
	//
	$strsql = "SELECT ciniki_course_offerings.id, "
		. "ciniki_course_offerings.condensed_date, "
		. "ciniki_courses.id AS course_id, "
		. "ciniki_courses.name, "
		. "ciniki_courses.code, "
		. "ciniki_courses.permalink, "
		. "ciniki_courses.primary_image_id, "
		. "ciniki_courses.level, "
		. "ciniki_courses.type, "
		. "ciniki_courses.category, "
		. "ciniki_courses.long_description, "
		. "ciniki_course_offering_classes.id AS class_id, "
		. "DATE_FORMAT(ciniki_course_offering_classes.class_date, '%W %b %e, %Y') AS class_date, "
		. "TIME_FORMAT(ciniki_course_offering_classes.start_time, '%l:%i %p') AS start_time, "
		. "TIME_FORMAT(ciniki_course_offering_classes.end_time, '%l:%i %p') AS end_time "
		. "FROM ciniki_course_offerings "
		. "LEFT JOIN ciniki_courses ON ("
			. "ciniki_course_offerings.course_id = ciniki_courses.id "
			. "AND ciniki_courses.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
		. "LEFT JOIN ciniki_course_offering_classes ON ("
			. "ciniki_course_offerings.id = ciniki_course_offering_classes.offering_id "
			. "AND ciniki_course_offering_classes.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "') "
		. "WHERE ciniki_course_offerings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_course_offerings.permalink = '" . ciniki_core_dbQuote($ciniki, $offering_permalink) . "' "
		. "AND ciniki_courses.permalink = '" . ciniki_core_dbQuote($ciniki, $course_permalink) . "' "
		. "AND ciniki_course_offerings.status = 10 "	// Active offering
		. "AND (ciniki_course_offerings.webflags&0x01) = 0 "	// Visible
		. "ORDER BY ciniki_course_offering_classes.class_date "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
	$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
		array('container'=>'offerings', 'fname'=>'id', 
			'fields'=>array('id', 'name', 'code', 'permalink', 'image_id'=>'primary_image_id', 
				'level', 'type', 'category', 'long_description', 'condensed_date')),
		array('container'=>'classes', 'fname'=>'class_id', 
			'fields'=>array('id'=>'class_id', 'class_date', 'start_time', 'end_time')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['offerings']) || count($rc['offerings']) < 1 ) {
		return array('stat'=>'404', 'err'=>array('pkg'=>'ciniki', 'code'=>'653', 'msg'=>"I'm sorry, but we can't seem to find the course you requested."));
	}
	$offering = array_pop($rc['offerings']);

	//
	// Check if there are files for this course to be displayed
	//
	if( ($ciniki['business']['modules']['ciniki.courses']['flags']&0x08) == 0x08 ) {
		$strsql = "SELECT ciniki_course_files.id, "
			. "ciniki_course_files.name, "
			. "ciniki_course_files.permalink, ciniki_course_files.extension "
			. "FROM ciniki_course_offering_files "
			. "LEFT JOIN ciniki_course_files ON (ciniki_course_offering_files.file_id = ciniki_course_files.id "
				. "AND ciniki_course_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ) "
			. "WHERE ciniki_course_offering_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_course_offering_files.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
			. "ORDER BY ciniki_course_files.name "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'files', 'fname'=>'id', 
				'fields'=>array('id', 'name', 'permalink', 'extension')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['files']) ) {
			$offering['files'] = $rc['files'];
		}
	}

	//
	// Check for prices
	//
	if( ($ciniki['business']['modules']['ciniki.courses']['flags']&0x04) > 0 ) {
		//
		// Get the price list for the event
		//
		$strsql = "SELECT id, name, unit_amount "
			. "FROM ciniki_course_offering_prices "
			. "WHERE ciniki_course_offering_prices.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
			. "AND ciniki_course_offering_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND (ciniki_course_offering_prices.webflags&0x01) = 0 "
			. "ORDER BY ciniki_course_offering_prices.name "
			. "";
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.courses', array(
			array('container'=>'prices', 'fname'=>'id',
				'fields'=>array('id', 'name', 'unit_amount')),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['prices']) ) {
			$offering['prices'] = $rc['prices'];
			foreach($offering['prices'] as $pid => $price) {
				$offering['prices'][$pid]['unit_amount_display'] = numfmt_format_currency(
					$intl_currency_fmt, $price['unit_amount'], $intl_currency);
			}
		} else {
			$offering['prices'] = array();
		}
	}

	return array('stat'=>'ok', 'offering'=>$offering);
}
?>
