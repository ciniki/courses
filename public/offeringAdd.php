<?php
//
// Description
// ===========
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to add the course to.
//
// name:                The name of the course.
// webflags:            (optional) How the course is shared with the public and customers.  
//                      The default is the course is public.
//
//                      0x01 - Hidden, unavailable on the website
//
// short_description:   The short description of the course, for use in lists.
// long_description:    The long description of the course, for use in the details page.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_courses_offeringAdd(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'course_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Course'), 
        'copy_offering_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Copy Offering'), 
        'name'=>array('required'=>'yes', 'blank'=>'no', 'trimblanks'=>'yes', 'name'=>'Name'), 
        'code'=>array('required'=>'no', 'blank'=>'no', 'trimblanks'=>'yes', 'name'=>'Code'), 
        'status'=>array('required'=>'no', 'default'=>'10', 'blank'=>'no', 'validlist'=>array('10', '60'), 'name'=>'Status'), 
        'webflags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Web Flags'), 
        'class_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Start Date'),
        'end_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'End Date'),
        'days'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Days of the week for courses'),
        'skip_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'date', 'name'=>'Skip Date'),
        'start_time'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'time', 'name'=>'Start Time'),
        'end_time'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'time', 'name'=>'End Time'),
        'num_weeks'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Num Weeks'),
        'num_seats'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Number of Seats'),
        'reg_flags'=>array('required'=>'no', 'default'=>'0', 'blank'=>'no', 'name'=>'Registration Flags'),
        'primary_image_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'synopsis'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Primary Image'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'),
        'paid_content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Paid Content'),
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
    $rc = ciniki_courses_checkAccess($ciniki, $args['tnid'], 'ciniki.courses.offeringAdd'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $modules = $rc['modules'];

    if( isset($args['code']) && $args['code'] != '' ) {
        $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower($args['name'] . '-' . $args['code'])));
    } else {
        $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 \-]/', '', strtolower($args['name'])));
    }
    $args['permalink'] = preg_replace('/\-\-\-/', '-', $args['permalink']);
    $args['permalink'] = preg_replace('/\-\-/', '-', $args['permalink']);

    //
    // Check the permalink doesn't already exist
    //
    $strsql = "SELECT id, name, code, permalink FROM ciniki_course_offerings "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND course_id = '" . ciniki_core_dbQuote($ciniki, $args['course_id']) . "' "
        . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'course');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( $rc['num_rows'] > 0 ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.29', 'msg'=>'You already have a course with this name, please choose another name'));
    }

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];

    //
    // Load offering if copy_offering_id specified
    //
    if( isset($args['copy_offering_id']) && $args['copy_offering_id'] > 0 ) {
        $strsql = "SELECT offerings.id, "
            . "offerings.primary_image_id, "
            . "offerings.synopsis, "
            . "offerings.content, "
            . "offerings.paid_content "
            . "FROM ciniki_course_offerings AS offerings "
            . "WHERE offerings.id = '" . ciniki_core_dbQuote($ciniki, $args['copy_offering_id']) . "' "
            . "AND offerings.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.courses', 'offering');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.courses.201', 'msg'=>'Unable to load offering', 'err'=>$rc['err']));
        }
        if( isset($rc['offering']) ) {
            $offering = $rc['offering'];
            if( !isset($args['primary_image_id']) ) {
                $args['primary_image_id'] = $rc['offering']['primary_image_id'];
            }
            if( !isset($args['synopsis']) ) {
                $args['synopsis'] = $rc['offering']['synopsis'];
            }
            if( !isset($args['content']) ) {
                $args['content'] = $rc['offering']['content'];
            }
            if( !isset($args['paid_content']) ) {
                $args['paid_content'] = $rc['offering']['paid_content'];
            }
            //
            // Load offering prices
            //
            $strsql = "SELECT prices.id, "
                . "prices.name, "
                . "prices.available_to, "
                . "prices.valid_from, "
                . "prices.valid_to, "
                . "prices.unit_amount, "
                . "prices.unit_discount_amount, "
                . "prices.unit_discount_percentage, "
                . "prices.taxtype_id, "
                . "prices.webflags "
                . "FROM ciniki_course_offering_prices AS prices "
                . "WHERE prices.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
                . "AND prices.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY prices.name "
                . "";
            ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'prices', 'fname'=>'id',
                    'fields'=>array('name', 'available_to', 'valid_from', 'valid_to', 
                        'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id', 'webflags',
                        ),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $offering['prices'] = isset($rc['prices']) ? $rc['prices'] : array();

            //
            // Load offering instructors
            //
            $strsql = "SELECT instructors.id, "
                . "instructors.instructor_id "
                . "FROM ciniki_course_offering_instructors AS instructors "
                . "WHERE instructors.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
                . "AND instructors.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'instructors', 'fname'=>'id', 'fields'=>array('instructor_id')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $offering['instructors'] = isset($rc['instructors']) ? $rc['instructors'] : array();

            //
            // Load offering files
            //
            $strsql = "SELECT files.id, "
                . "files.uuid, "
                . "files.extension, "
                . "files.status, "
                . "files.name, "
                . "files.permalink, "
                . "files.webflags, "
                . "files.description, "
                . "files.org_filename "
                . "FROM ciniki_course_offering_files AS files "
                . "WHERE files.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
                . "AND files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'files', 'fname'=>'id', 'fields'=>array('uuid', 'extension', 'status', 'name', 'permalink',
                    'webflags', 'description', 'org_filename')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $offering['files'] = isset($rc['files']) ? $rc['files'] : array();

            //
            // Load offering notifications
            //
            $strsql = "SELECT notifications.id, "
                . "notifications.name, "
                . "notifications.ntrigger, "
                . "notifications.ntype, "
                . "notifications.offset_days, "
                . "notifications.status, "
                . "notifications.time_of_day, "
                . "notifications.subject, "
                . "notifications.content "
                . "FROM ciniki_course_offering_notifications AS notifications "
                . "WHERE notifications.offering_id = '" . ciniki_core_dbQuote($ciniki, $offering['id']) . "' "
                . "AND notifications.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "ORDER BY notifications.name "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.courses', array(
                array('container'=>'notifications', 'fname'=>'id',
                    'fields'=>array('name', 'ntrigger', 'ntype', 'offset_days', 
                        'status', 'time_of_day', 'subject', 'content',
                        ),
                    ),
                ));
            if( $rc['stat'] != 'ok' ) {
                return $rc;
            }
            $offering['notifications'] = isset($rc['notifications']) ? $rc['notifications'] : array();
        }
    }

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    // 
    // Set the default condensed date to blank, it will be updated if class_date has been specified
    //
    $args['condensed_date'] = '';
    if( isset($args['end_date']) && isset($args['class_date']) ) {
        $args['start_date'] = $args['class_date'];
    }

    //
    // Add the offering
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
    $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.offering', $args, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
        return $rc;
    }
    $offering_id = $rc['id'];

    //
    // Check if we should add some dates
    //
    if( isset($args['class_date']) && $args['class_date'] != '' && !isset($args['end_date']) ) {
        if( isset($args['num_weeks']) && $args['num_weeks'] != '' && $args['num_weeks'] > 1 ) {
            $repeat = $args['num_weeks'];
        } else {
            $repeat = 1;
        }
        $cur_date = date_create('@' . strtotime($args['class_date']));
        $start_day = $cur_date->format('N');
        $class_args = array(
            'course_id'=>$args['course_id'],
            'offering_id'=>$offering_id,
            'start_time'=>$args['start_time'],
            'end_time'=>$args['end_time'],
            'notes'=>'',
            );
        for($i=0;$i<$repeat;$i++) {
            $class_args['class_date'] = date_format($cur_date, 'Y-m-d');
            if( isset($args['skip_date']) && $class_args['class_date'] == $args['skip_date'] ) {
                //
                // Calculate next class date
                //
                $cur_date = date_add($cur_date, new DateInterval('P7D'));
                $class_args['class_date'] = date_format($cur_date, 'Y-m-d');
            }
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.offering_class', $class_args, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
                return $rc;
            }

            //
            // Calculate next class date
            //
            if( isset($args['days']) && $args['days'] > 0 ) {
                //
                // Check for other days of the week that need to be added, only check next 6 days
                //
                for($j=0;$j<6;$j++) {
                    $cur_date->add(new DateInterval('P1D'));
                    $day = $cur_date->format('w');
                    if( in_array($day, $args['days']) ) {
                        $class_args['class_date'] = date_format($cur_date, 'Y-m-d');
                        $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.offering_class', $class_args, 0x04);
                        if( $rc['stat'] != 'ok' ) {
                            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
                            return $rc;
                        }
                    }
                }
                //
                // Advance 1 more day to get to the same day of the week that the first date is on
                //
                $cur_date = date_add($cur_date, new DateInterval('P1D'));
            } else {
                $cur_date = date_add($cur_date, new DateInterval('P7D'));
            }
        }
    }

    //
    // Update the condensed date
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'courses', 'private', 'updateCondensedDate');
    $rc = ciniki_courses_updateCondensedDate($ciniki, $args['tnid'], $offering_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Add prices from copy offering
    //
    if( isset($offering['prices']) ) {
        foreach($offering['prices'] as $price) {
            $price['offering_id'] = $offering_id;
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.offering_price', $price, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
                return $rc;
            }
        }
    }

    //
    // Add instructors from copy offering
    //
    if( isset($offering['instructors']) ) {
        foreach($offering['instructors'] as $instructor) {
            $instructor['course_id'] = $args['course_id'];
            $instructor['offering_id'] = $offering_id;
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.offering_instructor', $instructor, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
                return $rc;
            }
        }
    }

    //
    // Add files from copy offering
    //
    if( isset($offering['files']) ) {
        foreach($offering['files'] as $file) {
            $file['offering_id'] = $offering_id;
            $old_uuid = $file['uuid'];
            unset($file['uuid']);
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.offering_file', $file, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
                return $rc;
            }
            $uuid = $rc['uuid'];
            //
            // Copy the file in storage
            //
            $old_filename = $tenant_storage_dir . '/ciniki.courses/files/' . $old_uuid[0] . '/' . $old_uuid;
            $new_filename = $tenant_storage_dir . '/ciniki.courses/files/' . $uuid[0] . '/' . $uuid;
            copy($old_filename, $new_filename);
        }
    }

    //
    // Add notifications from copy offering
    //
    if( isset($offering['notifications']) ) {
        foreach($offering['notifications'] as $notification) {
            $notification['offering_id'] = $offering_id;
            $rc = ciniki_core_objectAdd($ciniki, $args['tnid'], 'ciniki.courses.offering_notification', $notification, 0x04);
            if( $rc['stat'] != 'ok' ) {
                ciniki_core_dbTransactionRollback($ciniki, 'ciniki.courses');
                return $rc;
            }
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.courses');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'courses');

    //
    // Update the web index if enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'hookExec');
    ciniki_core_hookExec($ciniki, $args['tnid'], 'ciniki', 'web', 'indexObject', array('object'=>'ciniki.courses.offering', 'object_id'=>$offering_id));

    return array('stat'=>'ok', 'id'=>$offering_id);
}
?>
