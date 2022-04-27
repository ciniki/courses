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
        'name' => 'Course',
        'sync' => 'yes',
        'o_name' => 'course',
        'o_container' => 'courses',
        'table' => 'ciniki_courses',
        'fields' => array(
            'name'=>array('name'=>'Name'),
            'code'=>array('name'=>'Code', 'default'=>''),
            'permalink'=>array('name'=>'Permalink'),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'primary_image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>'0'),
            'level'=>array('name'=>'Level', 'default'=>''),
            'type'=>array('name'=>'Type', 'default'=>''),
            'category'=>array('name'=>'Category', 'default'=>''),
            'medium'=>array('name'=>'Medium', 'default'=>''),
            'ages'=>array('name'=>'Age Range', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>'0'),
            'short_description'=>array('name'=>'Synopsis', 'default'=>''),
            'long_description'=>array('name'=>'Description', 'default'=>''),
            'paid_content'=>array('name'=>'Paid Content', 'default'=>''),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['file'] = array(
        'name'=>'File',
        'sync'=>'yes',
        'o_name' => 'file',
        'o_container' => 'files',
        'table'=>'ciniki_course_files',
        'fields'=>array(
            'course_id'=>array('name'=>'Course', 'ref'=>'ciniki.courses.course'),
            'type'=>array('name'=>'Type', 'default'=>20),
            'extension'=>array('name'=>'Extension'),
            'status'=>array('name'=>'Status', 'default'=>1),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'webflags'=>array('name'=>'Options', 'default'=>0),
            'description'=>array('name'=>'Description', 'default'=>''),
            'org_filename'=>array('name'=>'Original Filename'),
            'publish_date'=>array('name'=>'Publish Date', 'default'=>''),
            'binary_content'=>array('name'=>'Binary Content', 'default'=>'', 'history'=>'no'),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['image'] = array(
        'name'=>'Image',
        'sync'=>'yes',
        'o_name'=>'image',
        'o_container'=>'images',
        'table'=>'ciniki_course_images',
        'fields'=>array(
            'course_id'=>array('name'=>'Course', 'ref'=>'ciniki.courses.course'),
            'name'=>array('name'=>'Name', 'default'=>''),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>0),
            'image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image'),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['instructor'] = array(
        'name'=>'Instructor',
        'sync'=>'yes',
        'o_name'=>'instructor',
        'o_container'=>'instructors',
        'table'=>'ciniki_course_instructors',
        'fields'=>array(
            'first'=>array('name'=>'First'),
            'last'=>array('name'=>'First', 'default'=>''),
            'status'=>array('name'=>'Status', 'default'=>'10'),
            'permalink'=>array('name'=>'Permalink'),
            'primary_image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image', 'default'=>0),
            'webflags'=>array('name'=>'Options', 'default'=>0),
            'short_bio'=>array('name'=>'Synopsis', 'default'=>''),
            'full_bio'=>array('name'=>'Full Bio', 'default'=>''),
            'url'=>array('name'=>'Website', 'default'=>''),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['instructor_image'] = array(
        'name'=>'Instructor Image',
        'sync'=>'yes',
        'o_name'=>'image',
        'o_container'=>'images',
        'table'=>'ciniki_course_instructor_images',
        'fields'=>array(
            'instructor_id'=>array('ref'=>'ciniki.courses.instructor'),
            'name'=>array('name'=>'Name', 'default'=>''),
            'permalink'=>array('name'=>'Permalink'),
            'webflags'=>array('name'=>'Options', 'default'=>0),
            'image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image'),
            'description'=>array('name'=>'Description', 'default'=>''),
            'url'=>array('name'=>'Website', 'default'=>''),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['offering'] = array(
        'name'=>'Course Offering',
        'sync'=>'yes',
        'o_name'=>'offering',
        'o_container'=>'offerings',
        'table'=>'ciniki_course_offerings',
        'fields'=>array(
            'course_id'=>array('name'=>'Course', 'ref'=>'ciniki.courses.course'),
            'name'=>array('name'=>'Name'),
            'code'=>array('name'=>'Offering Code', 'default'=>''),
            'permalink'=>array('name'=>'Permalink'),
            'status'=>array('name'=>'Status'),
            'webflags'=>array('name'=>'Options', 'default'=>0),
            'start_date'=>array('name'=>'Start Date', 'default'=>''),
            'end_date'=>array('name'=>'End Date', 'default'=>''),
            'condensed_date'=>array('name'=>'Condensed Text Date'),
            'num_seats'=>array('name'=>'Number of Seats', 'default'=>0),
            'reg_flags'=>array('name'=>'Registration Options', 'default'=>0),
            'primary_image_id'=>array('name'=>'Image', 'default'=>0, 'ref'=>'ciniki.images.image'),
            'synopsis'=>array('name'=>'Synopsis', 'default'=>''),
            'content'=>array('name'=>'Content', 'default'=>''),
            'paid_content'=>array('name'=>'Paid Content', 'default'=>''),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['offering_class'] = array(
        'name'=>'Course Offering Class',
        'sync'=>'yes',
        'o_name'=>'class',
        'o_container'=>'classes',
        'table'=>'ciniki_course_offering_classes',
        'fields'=>array(
            'course_id'=>array('name'=>'Course', 'ref'=>'ciniki.courses.course'),
            'offering_id'=>array('name'=>'Offering', 'ref'=>'ciniki.courses.offering'),
            'class_date'=>array('name'=>'Date'),
            'start_time'=>array('name'=>'Start Time', 'default'=>''),
            'end_time'=>array('name'=>'End Time', 'default'=>''),
            'notes'=>array('name'=>'Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['offering_registration'] = array(
        'name'=>'Course Offering Registration',
        'sync'=>'yes',
        'o_name'=>'registration',
        'o_container'=>'registrations',
        'table'=>'ciniki_course_offering_registrations',
        'fields'=>array(
            'offering_id'=>array('name'=>'Offering', 'ref'=>'ciniki.courses.offering'),
            'customer_id'=>array('name'=>'Customer', 'ref'=>'ciniki.customers.customer'),
            'student_id'=>array('name'=>'Student', 'ref'=>'ciniki.customers.customer'),
            'invoice_id'=>array('name'=>'Invoice', 'ref'=>'ciniki.sapos.invoice'),
            'num_seats'=>array('name'=>'Num Seats', 'default'=>1),
            'customer_notes'=>array('name'=>'Customer Notes', 'default'=>''),
            'notes'=>array('name'=>'Internal Notes', 'default'=>''),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['offering_file'] = array(
        'name'=>'Course Offering File',
        'sync'=>'yes',
        'o_name'=>'file',
        'o_container'=>'files',
        'table'=>'ciniki_course_offering_files',
        'fields'=>array(
            'offering_id'=>array('name'=>'Offering', 'ref'=>'ciniki.courses.offering'),
            'extension'=>array('name'=>'Extension'),
            'status'=>array('name'=>'Status', 'default'=>10),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'webflags'=>array('name'=>'Options', 'default'=>0),
            'description'=>array('name'=>'Description', 'default'=>''),
            'org_filename'=>array('name'=>'Original Filename'),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['offering_image'] = array(
        'name'=>'Course Offering Image',
        'sync'=>'yes',
        'o_name'=>'image',
        'o_container'=>'images',
        'table'=>'ciniki_course_offering_images',
        'fields'=>array(
            'offering_id'=>array('name'=>'Offering', 'ref'=>'ciniki.courses.offering'),
            'name'=>array('name'=>'Name', 'default'=>''),
            'permalink'=>array('name'=>'Permalink', 'default'=>''),
            'flags'=>array('name'=>'Options', 'default'=>0),
            'image_id'=>array('name'=>'Image', 'ref'=>'ciniki.images.image'),
            'description'=>array('name'=>'Description', 'default'=>''),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['offering_price'] = array(
        'name'=>'Course Offering Price',
        'sync'=>'yes',
        'o_name'=>'price',
        'o_container'=>'prices',
        'table'=>'ciniki_course_offering_prices',
        'fields'=>array(
            'offering_id'=>array('name'=>'Offering', 'ref'=>'ciniki.courses.offering'),
            'name'=>array('name'=>'Name'),
            'available_to'=>array('name'=>'Available To', 'default'=>'1'),
            'valid_from'=>array('name'=>'Valid From', 'default'=>''),
            'valid_to'=>array('name'=>'Valid To', 'default'=>''),
            'unit_amount'=>array('name'=>'Amount'),
            'unit_discount_amount'=>array('name'=>'Discount Amount', 'default'=>''),
            'unit_discount_percentage'=>array('name'=>'Discount %', 'default'=>''),
            'taxtype_id'=>array('name'=>'Tax', 'ref'=>'ciniki.taxes.type', 'default'=>0),
            'webflags'=>array('name'=>'Options', 'default'=>0),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['offering_instructor'] = array(
        'name'=>'Course Offering Instructor',
        'sync'=>'yes',
        'o_name'=>'instructor',
        'o_container'=>'instructors',
        'table'=>'ciniki_course_offering_instructors',
        'fields'=>array(
            'course_id'=>array('name'=>'Course', 'ref'=>'ciniki.courses.course'),
            'offering_id'=>array('name'=>'Offering', 'ref'=>'ciniki.courses.offering'),
            'instructor_id'=>array('name'=>'Instructor', 'ref'=>'ciniki.courses.instructor'),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['offering_notification'] = array(
        'name'=>'Offering Notification',
        'sync'=>'yes',
        'o_name'=>'notification',
        'o_container'=>'notifications',
        'table'=>'ciniki_course_offering_notifications',
        'fields'=>array(
            'offering_id'=>array('name'=>'Program Session', 'ref'=>'ciniki.courses.offering'),
            'name'=>array('name'=>'Name'),
            'ntrigger'=>array('name'=>'Trigger'),
            'ntype'=>array('name'=>'Notification Type', 'default'=>10),
            'offset_days'=>array('name'=>'Offset Days', 'default'=>0),
            'status'=>array('name'=>'Status', 'default'=>0),
            'time_of_day'=>array('name'=>'Time of Day', 'default'=>''),
            'subject'=>array('name'=>'Subject', 'default'=>''),
            'content'=>array('name'=>'Content'),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['offering_nqueue'] = array(
        'name'=>'Offering Notification Queue',
        'sync'=>'yes',
        'o_name'=>'notification',
        'o_container'=>'queue',
        'table'=>'ciniki_course_offering_nqueue',
        'fields'=>array(
            'scheduled_dt'=>array('name'=>'Scheduled Date/Time'),
            'notification_id'=>array('name'=>'Notification', 'ref'=>'ciniki.courses.offering_notification'),
            'registration_id'=>array('name'=>'Registration', 'ref'=>'ciniki.courses.offering_registration'),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['album'] = array(
        'name'=>'Photo Album',
        'sync'=>'yes',
        'o_name'=>'album',
        'o_container'=>'albums',
        'table'=>'ciniki_course_albums',
        'fields'=>array(
            'course_id'=>array('name'=>'Course', 'ref'=>'ciniki.courses.course', 'default'=>'0'),
            'offering_id'=>array('name'=>'Offering', 'ref'=>'ciniki.courses.offering', 'default'=>'0'),
            'name'=>array('name'=>'Name'),
            'permalink'=>array('name'=>'Permalink'),
            'flags'=>array('name'=>'Options', 'default'=>0),
            'sequence'=>array('name'=>'Name', 'default'=>'1'),
            'primary_image_id'=>array('name'=>'Image', 'default'=>'0'),
            'description'=>array('name'=>'Name', 'default'=>''),
            ),
        'history_table'=>'ciniki_course_history',
        );
    $objects['album_image'] = array(
        'name'=>'Photo Album Image',
        'sync'=>'yes',
        'o_name'=>'image',
        'o_container'=>'images',
        'table'=>'ciniki_course_album_images',
        'fields'=>array(
            'album_id'=>array('name'=>'Course', 'ref'=>'ciniki.courses.course', 'default'=>'0'),
            'name'=>array('name'=>'Name', 'default'=>''),
            'permalink'=>array('name'=>'Permalink'),
            'flags'=>array('name'=>'Options', 'default'=>1),
            'image_id'=>array('name'=>'Image'),
            'description'=>array('name'=>'Name', 'default'=>''),
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
