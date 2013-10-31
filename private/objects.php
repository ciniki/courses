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
function ciniki_courses_objects($ciniki) {
	$objects = array();
	$objects['course'] = array(
		'name'=>'Course',
		'sync'=>'yes',
		'table'=>'ciniki_courses',
		'fields'=>array(
			'name'=>array(),
			'code'=>array(),
			'permalink'=>array(),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			'level'=>array(),
			'type'=>array(),
			'category'=>array(),
			'short_description'=>array(),
			'long_description'=>array(),
			),
		'history_table'=>'ciniki_course_history',
		);
	$objects['file'] = array(
		'name'=>'File',
		'sync'=>'yes',
		'table'=>'ciniki_course_files',
		'fields'=>array(
			'type'=>array(),
			'extension'=>array(),
			'status'=>array(),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'description'=>array(),
			'org_filename'=>array(),
			'publish_date'=>array(),
			'binary_content'=>array('history'=>'no'),
			),
		'history_table'=>'ciniki_course_history',
		);
	$objects['instructor'] = array(
		'name'=>'Instructor',
		'sync'=>'yes',
		'table'=>'ciniki_course_instructors',
		'fields'=>array(
			'first'=>array(),
			'last'=>array(),
			'permalink'=>array(),
			'primary_image_id'=>array('ref'=>'ciniki.images.image'),
			'webflags'=>array(),
			'short_bio'=>array(),
			'full_bio'=>array(),
			'url'=>array(),
			),
		'history_table'=>'ciniki_course_history',
		);
	$objects['instructor_image'] = array(
		'name'=>'Instructor Image',
		'sync'=>'yes',
		'table'=>'ciniki_course_instructor_images',
		'fields'=>array(
			'instructor_id'=>array('ref'=>'ciniki.courses.instructor'),
			'name'=>array(),
			'permalink'=>array(),
			'webflags'=>array(),
			'image_id'=>array('ref'=>'ciniki.images.image'),
			'description'=>array(),
			'url'=>array(),
			),
		'history_table'=>'ciniki_course_history',
		);
	$objects['offering'] = array(
		'name'=>'Course Offering',
		'sync'=>'yes',
		'table'=>'ciniki_course_offerings',
		'fields'=>array(
			'course_id'=>array('ref'=>'ciniki.courses.course'),
			'name'=>array(),
			'permalink'=>array(),
			'status'=>array(),
			'webflags'=>array(),
			'condensed_date'=>array(),
			),
		'history_table'=>'ciniki_course_history',
		);
	$objects['offering_class'] = array(
		'name'=>'Course Offering Class',
		'sync'=>'yes',
		'table'=>'ciniki_course_offering_classes',
		'fields'=>array(
			'course_id'=>array('ref'=>'ciniki.courses.course'),
			'offering_id'=>array('ref'=>'ciniki.courses.offering'),
			'class_date'=>array(),
			'start_time'=>array(),
			'end_time'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_course_history',
	 	);
	$objects['offering_customer'] = array(
		'name'=>'Course Offering Customer',
		'sync'=>'yes',
		'table'=>'ciniki_course_offering_customers',
		'fields'=>array(
			'course_id'=>array('ref'=>'ciniki.courses.course'),
			'offering_id'=>array('ref'=>'ciniki.courses.offering'),
			'customer_id'=>array('ref'=>'ciniki.customers.customer'),
			'status'=>array(),
			'notes'=>array(),
			),
		'history_table'=>'ciniki_course_history',
		);
	$objects['offering_file'] = array(
		'name'=>'Course Offering File',
		'sync'=>'yes',
		'table'=>'ciniki_course_offering_files',
		'fields'=>array(
			'course_id'=>array('ref'=>'ciniki.courses.course'),
			'offering_id'=>array('ref'=>'ciniki.courses.offering'),
			'file_id'=>array('ref'=>'ciniki.courses.file'),
			),
		'history_table'=>'ciniki_course_history',
		);
	$objects['offering_instructor'] = array(
		'name'=>'Course Offering Instructor',
		'sync'=>'yes',
		'table'=>'ciniki_course_offering_instructors',
		'fields'=>array(
			'course_id'=>array('ref'=>'ciniki.courses.course'),
			'offering_id'=>array('ref'=>'ciniki.courses.offering'),
			'instructor_id'=>array('ref'=>'ciniki.courses.instructor'),
			),
		'history_table'=>'ciniki_course_history',
		);
	$objects['setting'] = array(
		'type'=>'settings',
		'name'=>'Course Settings',
		'table'=>'ciniki_course_settings',
		'history_table'=>'ciniki_course_history',
		);
	
	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
