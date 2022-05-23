//
// This is the main app for the courses module
//
function ciniki_courses_main() {

    this.menutabs = {'label':'', 'type':'menutabs', 'selected':'offerings', 'tabs':{
        'offerings':{'label':'Sessions', 'fn':'M.ciniki_courses_main.switchTab("offerings");'},
        'courses':{'label':'Programs', 'fn':'M.ciniki_courses_main.switchTab("courses");'},
        'instructors':{'label':'Instructors', 'fn':'M.ciniki_courses_main.switchTab("instructors");'},
        'students':{'label':'Students', 'fn':'M.ciniki_courses_main.switchTab("students");'},
        }};
    this.switchTab = function(t) {
        this.menutabs.selected = t;
        if( this[t] == null ) {
            this.courses.open(null,t);
        } else {
            this[t].open();
        }
    }

    //
    // The panel to list the offerings
    //
    this.offerings = new M.panel('Program Sessions', 'ciniki_courses_main', 'offerings', 'mc', 'xlarge narrowaside', 'sectioned', 'ciniki.courses.main.offerings');
    this.offerings.data = {};
    this.offerings.nplist = [];
    this.offerings.sections = {
        '_tabs':this.menutabs,
        'statuses':{'label':'Status', 'type':'simplegrid', 'selected':'10', 'num_cols':1, 'aside':'yes',
            },
        'years':{'label':'Years', 'type':'simplegrid', 'selected':'all', 'num_cols':1, 'aside':'yes',
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search sessions',
            'noData':'No sessions found',
            },
        'offerings':{'label':'Program Sessions', 'type':'simplegrid', 'num_cols':1,
            'headerValues':[],
            'sortable':'yes',
            'sortTypes':[],
            'dataMaps':[],
            'noData':'No sessions',
            'addTxt':'Add Session',
            'addFn':'M.ciniki_courses_main.offering.open(\'M.ciniki_courses_main.offerings.open();\',0,0);'
            },
    }
    this.offerings.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.courses.offeringSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_courses_main.offerings.liveSearchShow('search',null,M.gE(M.ciniki_courses_main.offerings.panelUID + '_' + s), rsp.offerings);
                });
        }
    }
    this.offerings.liveSearchResultValue = function(s, f, i, j, d) {
        return this.cellValue(s, i, j, d);
    }
    this.offerings.liveSearchResultRowFn = function(s, f, i, j, d) {
        return this.rowFn(s, i, d);
    }
    this.offerings.cellValue = function(s, i, j, d) {
        if( s == 'statuses' || s == 'years' ) {
            return M.textCount(d.label, d.num_offerings);
        }
        if( s == 'search' || s == 'offerings' ) {
            if( this.sections.offerings.dataMaps[j] == 'start_date' && (d.course_flags&0x10) == 0x10 ) {
                return 'n/a';
            }
            if( this.sections.offerings.dataMaps[j] == 'end_date' && (d.course_flags&0x10) == 0x10 ) {
                return 'n/a';
            }
            if( this.sections.offerings.dataMaps[j] == 'course_name' ) {
                if( d.course_code != '' ) {
                    return d.course_code + ' - ' + d.course_name;
                }
                return d.course_name;
            }
            if( this.sections.offerings.dataMaps[j] == 'registrations' ) {
                return d.num_registrations + '/' + d.num_seats;
            }
            return d[this.sections.offerings.dataMaps[j]];
        }
    }
    this.offerings.rowClass = function(s, i, d) {
        if( s == 'statuses' || s == 'years' ) {
            if( this.sections[s].selected == d.value ) {
                return 'highlight';
            }
        }
        return '';
    }
    this.offerings.rowFn = function(s, i, d) {
        if( s == 'statuses' || s == 'years' ) {
            return 'M.ciniki_courses_main.offerings.setFilter(\'' + s + '\',\'' + d.value + '\');'
        }
        if( s == 'search' || s == 'offerings' ) {
            return 'M.ciniki_courses_main.offering.open(\'M.ciniki_courses_main.offerings.open();\',\'' + d.id + '\');'; 
        }
    }
    this.offerings.setFilter = function(s, v) {
        this.sections[s].selected = v;
        if( s == 'statuses' ) {
            this.sections['years'].selected = 'all';
        }
        if( s == 'years' ) {
            this.sections['statuses'].selected = 'all';
        }
        this.lastY = 0;
        this.open();
    }
    this.offerings.open = function(cb) {
        // Get the list of existing offerings
        M.api.getJSONCb('ciniki.courses.offeringList', {'tnid':M.curTenantID, 
            'status':this.sections.statuses.selected, 'year':this.sections.years.selected, 'stats':'yes'}, 
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_courses_main.offerings;
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
    };
    this.offerings.addButton('add', 'Add', 'M.ciniki_courses_main.offering.open(\'M.ciniki_courses_main.offerings.open();\',0,0);');
    this.offerings.addClose('Back');

    //
    // The panel to edit Program offering
    //
    this.offering = new M.panel('Program Session', 'ciniki_courses_main', 'offering', 'mc', 'large mediumaside', 'sectioned', 'ciniki.courses.main.offering');
    this.offering.data = null;
    this.offering.course_id = 0;
    this.offering.offering_id = 0;
    this.offering.nplist = [];
    this.offering.sections = {
        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 
            'visible':function() { return (M.ciniki_courses_main.offering.data.course_flags&0x20) == 0x20 ? 'yes' : 'no';},
            'fields':{
                'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                    'addDropImage':function(iid) {
                        M.ciniki_courses_main.offering.setFieldValue('primary_image_id', iid);
                        return true;
                        },
                    'addDropImageRefresh':'',
                    'deleteImage':function(iid) {
                        M.ciniki_courses_main.offering.setFieldValue('primary_image_id', 0);
                        return true;
                        },
                 },
            }},
        'general':{'label':'Session', 'aside':'yes', 'fields':{
            'course_id':{'label':'Program', 'required':'yes', 'type':'select', 
                'editable':'afterclick',
                'confirmMsg':'Are you sure you wish to move this offering to a new course?',
                'confirmButton':'Change Program',
                'confirmFn':function() {
                    M.ciniki_courses_main.offering.editSelect('general', 'course_id', 'yes');
                    },
                'options':[], 
                'complex_options':{'value':'id', 'name':'name'},
                },
            'code':{'label':'Code', 'type':'text', 'size':'small',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x20);},
                },
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'status':{'label':'Status', 'required':'yes', 'type':'toggle', 'toggles':{'10':'Active', '90':'Archived'}},
            'webflags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Hidden'}}},
            'start_date':{'label':'Start Date', 'type':'date', 'editable':'no',
                'onchangeFn':'M.ciniki_courses_main.offering.refreshSessions',
                'visible':function() { return (M.ciniki_courses_main.offering.data.course_flags&0x10) == 0x10 ? 'no' : 'yes'; },
                },
            'end_date':{'label':'End Date', 'type':'date', 'editable':'no',
                'visible':function() { return (M.ciniki_courses_main.offering.data.course_flags&0x10) == 0x10 ? 'no' : 'yes'; },
                },
            'condensed_date':{'label':'Dates', 'type':'text', 'editable':'no',
                'visible':function() { return (M.ciniki_courses_main.offering.data.course_flags&0x10) == 0x10 ? 'no' : 'yes'; },
                },
            }},
        '_reg':{'label':'Registration Options', 'aside':'yes', 'fields':{
            'reg_flags':{'label':'Options', 'type':'flags', 'flags':{
                '1':{'name':'Track Registrations'},
                '2':{'name':'Online Registrations'},
                '4':{'name':'Sold Out'},
                }},
            'num_seats':{'label':'# Seats', 'type':'text', 'size':'small'},
            'seats_sold':{'label':'Seats Sold', 'type':'text', 'editable':'no'},
            'form_id':{'label':'Form', 'type':'select', 'options':{}, 
                'visible':function() { return M.modOn('ciniki.forms') ? 'yes' : 'no'; },
                'complex_options':{'value':'id', 'name':'name'},
                },
            }},
        '_actions':{'label':'', 'aside':'yes', 'size':'half', 'buttons':{
            'registrationspdf':{'label':'Class List (PDF)', 'fn':'M.ciniki_courses_main.offering.registrationsPDF();'},
            'attendancepdf':{'label':'Attendance (PDF)', 'fn':'M.ciniki_courses_main.offering.attendancePDF();'},
            'registrationsexcel':{'label':'Class List (Excel)', 'fn':'M.ciniki_courses_main.offering.registrationsExcel();'},
            'email':{'label':'Email Class', 'fn':'M.ciniki_courses_main.offering.emailShow();'},
            }},
        'prices':{'label':'Prices', 'type':'simplegrid', 'num_cols':3, 'aside':'yes',
            'cellClasses':['multiline', 'alignright', 'alignright'],
            'noData':'No prices added',
            'addTxt':'Add Price',
            'addTopFn':'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.price.open(\'M.ciniki_courses_main.offering.open();\',0,M.ciniki_courses_main.offering.offering_id);");',
            },
        'instructors':{'label':'Instructors', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['multiline', 'alignright'],
            'noData':'No instructors added',
            'addTxt':'Add Instructor',
            'addTopFn':'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.oinstructor.open(\'M.ciniki_courses_main.offering.open();\',M.ciniki_courses_main.offering.offering_id,M.ciniki_courses_main.offering.course_id);");',
            },
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'registrations', 'tabs':{
            'content':{'label':'Details', 
                'visible':function() { return (M.ciniki_courses_main.offering.data.course_flags&0x20) == 0x20 ? 'yes' : 'no';},
                'fn':'M.ciniki_courses_main.offering.switchTab("content");',
                },
            'paid':{'label':'Paid', 
                'visible':function() { return (M.ciniki_courses_main.offering.data.course_flags&0x40) == 0x40 ? 'yes' : 'no';},
                'fn':'M.ciniki_courses_main.offering.switchTab("paid");',
                },
            'files':{'label':'Files', 
                'visible':function() { return M.modFlagOn('ciniki.courses', 0x08) && (M.ciniki_courses_main.offering.data.course_flags&0x20) == 0x20 ? 'yes' : 'no';},
                'fn':'M.ciniki_courses_main.offering.switchTab("files");',
                },
            'images':{'label':'Gallery', 'fn':'M.ciniki_courses_main.offering.switchTab("images");',
                'visible':function() { return M.modFlagOn('ciniki.courses', 0x0200) && (M.ciniki_courses_main.offering.data.course_flags&0x20) == 0x20 ? 'yes' : 'no';},
                },
            'registrations':{'label':'Registrations', 'fn':'M.ciniki_courses_main.offering.switchTab("registrations");'},
            'classes':{'label':'Classes', 'fn':'M.ciniki_courses_main.offering.switchTab("classes");'},
            'emails':{'label':'Emails', 'fn':'M.ciniki_courses_main.offering.switchTab("emails");'},
            'notifications':{'label':'Notifications', 
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x080000);},
                'fn':'M.ciniki_courses_main.offering.switchTab("notifications");',
                },
//            'nqueue':{'label':'Queue', 
//                'visible':function() { return M.modFlagSet('ciniki.courses', 0x080000);},
//                'fn':'M.ciniki_courses_main.offering.switchTab("nqueue");',
//                },
            }},
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':3, 
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'registrations' ? 'yes' : 'hidden';},
            'noData':'No Registrations',
            'headerValues':['Name', 'Student', 'Age', 'Paid', 'Price', 'Amount', 'Form'],
            'headerClasses':['', '', '', 'alignright', 'alignright', 'alignright', 'alignright'],
            'cellClasses':['', '', '', 'alignright', 'alignright', 'alignright', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'number', 'text', 'text', 'number', 'text'],
            },
        'messages':{'label':'Emails', 'type':'simplegrid', 'num_cols':2,
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'emails' ? 'yes' : 'hidden';},
            'cellClasses':['multiline', 'multiline'],
            'headerValues':['Name/Date', 'Email/Subject'],
            'sortable':'yes',
            'sortTypes':['text','text'],
            'noData':'No Emails Sent',
            },
        'classes':{'label':'Classes', 'type':'simplegrid', 'num_cols':3, 
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'classes' ? 'yes' : 'hidden';},
            'noData':'No Classes Added',
            'headerValues':['Date', 'Start Time', 'End Time'],
            'sortable':'yes',
            'sortTypes':['date','number', 'number'],
            'addTxt':'Add Class',
            'addFn':'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.cclass.open(\'M.ciniki_courses_main.offering.open();\',0,M.ciniki_courses_main.offering.offering_id,M.ciniki_courses_main.offering.course_id);");',
            },
        '_synopsis':{'label':'Synopsis', 
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'content' ? 'yes' : 'hidden'; },
            'fields':{
                'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_content':{'label':'Content', 
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'content' ? 'yes' : 'hidden'; },
            'fields':{
                'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_materials':{'label':'Materials List', 
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'content' ? 'yes' : 'hidden'; },
            'fields':{
                'materials_list':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_paid':{'label':'Paid Content', 
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'paid' ? 'yes' : 'hidden'; },
            'fields':{
                'paid_content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'xlarge'},
            }},
        'files':{'label':'Session Files', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'files' ? 'yes' : 'hidden'; },
            'headerValues':['File', 'Visible', 'Paid'],
            'noData':'No files',
            'addTxt':'Add Session File',
            'addFn':'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.offeringfile.open(\'M.ciniki_courses_main.offering.open();\',0,M.ciniki_courses_main.offering.offering_id);");',
            },
        'images':{'label':'Gallery', 'type':'simplethumbs',
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'images' ? 'yes' : 'hidden';},
            },
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'images' ? 'yes' : 'hidden';},
            'addTxt':'Add Image',
            'addFn':'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.offeringimage.open(\'M.ciniki_courses_main.offering.open();\',0,M.ciniki_courses_main.offering.offering_id);");',
            },
        'notifications':{'label':'Notifications', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'notifications' ? 'yes' : 'hidden';},
            'headerValues':['Name', 'Trigger', 'Offset', 'Time', 'Subject'],
            'noData':'No notifications setup',
            'addTxt':'Add Notification',
            'addFn':'M.ciniki_courses_main.notification.open(\'M.ciniki_courses_main.offering.open();\',0,M.ciniki_courses_main.offering.offering_id);',
            },
        'nqueue':{'label':'Notification Queue', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_courses_main.offering.sections._tabs.selected == 'notifications' ? 'yes' : 'hidden';},
            'headerValues':['Date', 'Customer', 'Subject'],
            'sortable':'yes',
            'sortTypes':['date', 'text', 'text'],
            'cellClasses':['multiline', '', '', ''],
            'noData':'No notifications queued',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.offering.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_courses_main.offering.offering_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_courses_main.offering.remove();'},
            }},
        };
    this.offering.fieldValue = function(s, i, d) { return this.data[i]; }
    this.offering.thumbFn = function(s, i, d) {
        return 'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.offeringimage.open(\'M.ciniki_courses_main.offering.open();\',' + d.id + ',M.ciniki_courses_main.offering.offering_id);");';
    }
    this.offering.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.offeringHistory', 'args':{'tnid':M.curTenantID, 'offering_id':this.offering_id, 'field':i}};
    }
    this.offering.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSection('_tabs');
        this.showHideSections(['classes', 'messages', 'registrations', '_synopsis', '_content', '_materials', '_paid', 'files', 'images', '_images', 'notifications', 'nqueue']);
    }
    this.offering.cellValue = function(s, i, j, d) {
        if( s == 'prices' ) {
            switch(j) {
                case 0: return M.multiline(d.name, M.subdue(' ', d.available_to_text, ''));
                case 1: return d.unit_amount_display;
                case 2: return M.btn('+&nbsp;Reg', 'M.ciniki_courses_main.offering.save(\'M.ciniki_courses_main.offering.addReg(' + d.id + ');\');');
            }
        }
        if( s == 'files' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.visible;
                case 2: return d.paid_content;
            }
        }
        if( s == 'instructors' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return M.btn('Remove', 'M.ciniki_courses_main.offering.removeInstructor(\'' + i + '\');');
            }
        }
        if( s == 'registrations' ) {
            if( j == 6 ) {
                if( M.ciniki_courses_main.offering.data.form_id > 0 ) {
                    return d.submission_status == 90 ? 'Yes' : (d.submission_id > 0 ? 'N/C' : '');
                } else {
                    return 'N/A';
                }
            }
            switch(j) {
                case 0: return d.customer_name;
                case 1: return d.student_name;
                case 2: return d.yearsold;
                case 3: return d.invoice_status_text;
                case 4: return d.price_name;
                case 5: return d.registration_amount;
                case 6: return d.submission_status == 90 ? 'Yes' : 'No';
            }
        } 
        if( s == 'messages' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.customer_name + '</span>'    
                    + '<span class="subtext">' + d.status_text + ' - ' + d.date_sent + '</span>';
                case 1: return '<span class="maintext">' + d.customer_email + '</span>' 
                    + '<span class="subtext">' + d.subject + '</span>';
            }
        }
        if( s == 'classes' ) {
            switch(j) {
                case 0: return d.class_date;
                case 1: return d.start_time;
                case 2: return d.end_time;
            }
        }
        if( s == 'notifications' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.ntrigger_text;
                case 2: return (d.ntrigger > 30 ? d.offset_days + ' day' + (d.offset_days > 1 || d.offset_days < -1 ? 's':''): 'n/a');
                case 3: return (d.ntrigger > 30 ? d.time_of_day : 'n/a');
                case 3: return d.time_of_day;
                case 4: return d.subject;
            }
        } 
        if( s == 'nqueue' ) {
            switch(j) {
                case 0: return M.multiline(d.date_text, d.time_text);
                case 1: if( d.student_id > 0 && d.student_id != d.customer_id ) {
                    return M.multiline(d.student_name, d.customer_name);
                    }
                    return d.customer_name;
                case 2: return d.subject;
            }
        } 
    }
    this.offering.cellFn = function(s, i, j, d) {
        if( s == 'registrations' && j == 6 && d.submission_id > 0 ) {
            return 'event.stopPropagation();M.startApp(\'ciniki.forms.main\',null,\'M.ciniki_courses_main.offering.open();\',\'mc\',{\'submission_id\':\'' + d.submission_id + '\'});';
        }
    }
    this.offering.rowFn = function(s, i, d) {
        if( s == 'prices' ) {
            return 'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.price.open(\'M.ciniki_courses_main.offering.open();\',\'' + d.id + '\',M.ciniki_courses_main.offering.offering_id);");';
        }
        if( s == 'instructors' ) {
            return 'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.instructor.open(\'M.ciniki_courses_main.offering.open();\',\'' + d.instructor_id + '\',0);");';
        }
        if( s == 'registrations' ) {
            return 'M.startApp(\'ciniki.courses.sapos\',null,\'M.ciniki_courses_main.offering.open();\',\'mc\',{\'registration_id\':\'' + d.id + '\',\'source\':\'offering\'});';
        }
        if( s == 'messages' ) {
            return 'M.startApp(\'ciniki.mail.main\',null,\'M.ciniki_courses_main.offering.open();\',\'mc\',{\'message_id\':\'' + d.id + '\'});';
        }
        if( s == 'files' ) {
            return 'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.offeringfile.open(\'M.ciniki_courses_main.offering.open();\',\'' + d.id + '\',M.ciniki_courses_main.offering.offering_id);");';
        }
        if( s == 'classes' ) {
            return 'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.cclass.open(\'M.ciniki_courses_main.offering.open();\',\'' + d.id + '\',M.ciniki_courses_main.offering.offering_id,M.ciniki_courses_main.offering.course_id);");';
        }
        if( s == 'notifications' ) {
            return 'M.ciniki_courses_main.offering.save("M.ciniki_courses_main.notification.open(\'M.ciniki_courses_main.offering.open();\',\'' + d.id + '\',M.ciniki_courses_main.offering.offering_id);");';
        }
        return '';
    }
    this.offering.addReg = function(p) {
        M.startApp('ciniki.courses.sapos',null,'M.ciniki_courses_main.offering.open();','mc',{
            'offering_id':M.ciniki_courses_main.offering.offering_id,
            'price_id':p,
            'source':'offering',
            });
    }
    this.offering.removeInstructor = function(i) {
        if( this.data.instructors[i] != null ) {
            M.confirm("Are you sure you want to remove " + this.data.instructors[i].name + "?",null,function() {
                M.api.getJSONCb('ciniki.courses.offeringInstructorDelete', {'tnid':M.curTenantID, 'offering_instructor_id':M.ciniki_courses_main.offering.data.instructors[i].id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    delete M.ciniki_courses_main.offering.data.instructors[i];
                    M.ciniki_courses_main.offering.refreshSection('instructors');
                });
            });
        }
    }
    this.offering.registrationsPDF = function(oid) {
        M.api.openFile('ciniki.courses.offeringRegistrations', 
            {'tnid':M.curTenantID, 'output':'pdf', 'offering_id':this.offering_id});
    }
    this.offering.attendancePDF = function(oid) {
        M.api.openFile('ciniki.courses.offeringRegistrations', 
            {'tnid':M.curTenantID, 'template':'attendance', 'output':'pdf', 'offering_id':this.offering_id});
    }
    this.offering.emailShow = function() {
        var customers = [];
        for(var i in this.data.registrations) {
            customers[i] = {
                'id':this.data.registrations[i].customer_id,
                'name':this.data.registrations[i].customer_name,
                };
        }
        M.startApp('ciniki.mail.omessage',
            null,
            'M.ciniki_courses_main.offering.open();',
            'mc',
            {'subject':'Re: ' + this.data.course_name + ' - ' + this.data.name + ' (' + this.data.condensed_date + ')', 
                'list':customers, 
                'object':'ciniki.courses.offering',
                'object_id':this.offering_id,
                'removeable':'yes',
            });
    }
    this.offering.registrationsExcel = function(oid) {
        M.api.openFile('ciniki.courses.offeringRegistrations', 
            {'tnid':M.curTenantID, 'output':'excel', 'offering_id':this.offering_id});
    }
    this.offering.open = function(cb, oid, cid, list, copy_id) {
        if( cid != null ) { this.course_id = cid; }
        if( oid == 0 ) {
            this.cb = cb;
            M.ciniki_courses_main.offeringadd.open(cb, cid, copy_id);
            return true;
        }
        if( oid != null ) { this.offering_id = oid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.courses.offeringGet', {'tnid':M.curTenantID, 'offering_id':this.offering_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.offering;
            p.data = rsp.offering;
            p.course_id = rsp.offering.course_id;
            if( p.sections._tabs.selected == 'content' && (p.data.course_flags&0x20) == 0 ) {
                p.sections._tabs.selected = 'registrations';
            }
            if( p.sections._tabs.selected == 'paid' && (p.data.course_flags&0x40) == 0 ) {
                p.sections._tabs.selected = 'registrations';
            }
            if( rsp.offering.classes != null && rsp.offering.classes.length > 0 ) {
                p.sections.general.fields.start_date.editable = 'no';
                p.sections.general.fields.end_date.editable = 'no';
            } else {
                p.sections.general.fields.start_date.editable = 'yes';
                p.sections.general.fields.end_date.editable = 'yes';
            }
            p.sections.general.fields.course_id.options = rsp.courses;
            p.sections._reg.fields.form_id.options = [{'id':0, 'name':'None'}];
            if( M.modOn('ciniki.forms') && rsp.forms != null ) {
                p.sections._reg.fields.form_id.options = rsp.forms;
                p.sections._reg.fields.form_id.options.unshift({'id':0, 'name':'None'});
            }
            if( M.modOn('ciniki.sapos') ) {
                p.sections.registrations.num_cols = 6;
                if( M.modOn('ciniki.forms') && rsp.offering.form_id > 0 ) {
                    p.sections.registrations.num_cols = 7;
                }
            } else {
                p.sections.registrations.num_cols = 3;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.offering.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_courses_main.offering.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.offering_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.courses.offeringUpdate', {'tnid':M.curTenantID, 'offering_id':this.offering_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.courses.offeringAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.offering.offering_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.offering.remove = function() {
        M.confirm('Are you sure you want to remove \'' + this.data.name + '\'?  All information about it will be removed and unrecoverable.',null,function() {
            M.api.getJSONCb('ciniki.courses.offeringDelete', {'tnid':M.curTenantID, 'offering_id':M.ciniki_courses_main.offering.offering_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.offering.close();
            });
        });
    }
    this.offering.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.offering_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_courses_main.offering.save(\'M.ciniki_courses_main.offering.open(null,' + this.nplist[this.nplist.indexOf('' + this.offering_id) + 1] + ');\');';
        }
        return null;
    }
    this.offering.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.offering_id) > 0 ) {
            return 'M.ciniki_courses_main.offering.save(\'M.ciniki_courses_main.offering.open(null,' + this.nplist[this.nplist.indexOf('' + this.offering_id) - 1] + ');\');';
        }
        return null;
    }
    this.offering.addButton('save', 'Save', 'M.ciniki_courses_main.offering.save();');
    this.offering.addClose('Cancel');
    this.offering.addButton('next', 'Next');
    this.offering.addLeftButton('prev', 'Prev');

    //
    // The panel to add a new Program offering
    //
    this.offeringadd = new M.panel('Add Session', 'ciniki_courses_main', 'offeringadd', 'mc', 'medium', 'sectioned', 'ciniki.courses.main.offeringadd');
    this.offeringadd.data = null;
    this.offeringadd.course_id = 0;
    this.offeringadd.copy_offering_id = 0;
    this.offeringadd.sections = {
        'general':{'label':'Session', 'fields':{
            'course_id':{'label':'Program', 'required':'yes', 'type':'select', 
                'options':[], 
                'complex_options':{'value':'id', 'name':'name'},
                },
            'code':{'label':'Code', 'type':'text', 'size':'small',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x20);},
                },
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'status':{'label':'Status', 'required':'yes', 'type':'toggle', 'toggles':{'10':'Active', '90':'Archived'}},
            'webflags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Hidden'}}},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'classes', 
            'visible':function() { return (M.ciniki_courses_main.offeringadd.data.course_flags&0x10) == 0x10 ? 'no' : 'yes';},
            'tabs':{
                'classes':{'label':'Scheduled Classes', 'fn':'M.ciniki_courses_main.offeringadd.switchTab("classes");'},
                'noclasses':{'label':'No Schedule', 'fn':'M.ciniki_courses_main.offeringadd.switchTab("noclasses");'},
            }},
        '_classes':{'label':'Classes', 
            'active':function() { return (M.ciniki_courses_main.offeringadd.data.course_flags&0x10) == 0x10 ? 'no' : 'yes';},
            'fields':{
                'class_date':{'label':'First Date', 'required':'yes', 'type':'date'},
                'end_date':{'label':'Last Date', 'type':'date',
                    'active':function() {return M.ciniki_courses_main.offeringadd.sections._tabs.selected == 'noclasses' ? 'yes':'no';},
                    },
                'num_weeks':{'label':'Weeks', 'type':'text', 'size':'small',
                    'active':function() {return M.ciniki_courses_main.offeringadd.sections._tabs.selected == 'classes' ? 'yes':'no';},
                    },
                'days':{'label':'Days', 'none':'yes', 'type':'multiselect', 
                    'active':function() {return M.ciniki_courses_main.offeringadd.sections._tabs.selected == 'classes' ? 'yes':'no';},
                    'options':['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    },
                'skip_date':{'label':'Skip Date', 'type':'date', 'size':'small',
                    'active':function() {return M.ciniki_courses_main.offeringadd.sections._tabs.selected == 'classes' ? 'yes':'no';},
                    },
                'start_time':{'label':'Start Time', 'type':'text', 'size':'small',
                    'active':function() {return M.ciniki_courses_main.offeringadd.sections._tabs.selected == 'classes' ? 'yes':'no';},
                    },
                'end_time':{'label':'End Time', 'type':'text', 'size':'small',
                    'active':function() {return M.ciniki_courses_main.offeringadd.sections._tabs.selected == 'classes' ? 'yes':'no';},
                    },
            }},
        '_reg':{'label':'Registration Options', 'fields':{
            'reg_flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Track Registrations'},'2':{'name':'Online Registrations'}}},
            'num_seats':{'label':'Number of Seats', 'type':'text', 'size':'small'},
            'form_id':{'label':'Form', 'type':'select', 'options':{}, 
                'visible':function() { return M.modOn('ciniki.forms') ? 'yes' : 'no'; },
                'complex_options':{'value':'id', 'name':'name'},
                },
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.offeringadd.save();'},
            }},
        };
//    this.offeringadd.fieldValue = function(s, i, d) { return this.data[i]; }
    this.offeringadd.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSections(['_tabs', '_classes']);
    }
    this.offeringadd.open = function(cb, cid, copy_id) {
        if( cid != null ) { this.course_id = cid; }
        this.copy_offering_id = (copy_id != null ? copy_id : 0);
        M.api.getJSONCb('ciniki.courses.offeringGet', {'tnid':M.curTenantID, 'offering_id':0, 'course_id':cid, 'copy_offering_id':this.copy_offering_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.offeringadd;
            p.data = rsp.offering;
//            if( cid > 0 ) {
//                p.data.course_id = cid;
//            }
            p.sections.general.fields.course_id.options = rsp.courses;
            p.sections._reg.fields.form_id.options = [{'id':0, 'name':'None'}];
            if( M.modOn('ciniki.forms') && rsp.forms != null ) {
                p.sections._reg.fields.form_id.options = rsp.forms;
                p.sections._reg.fields.form_id.options.unshift({'id':0, 'name':'None'});
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.offeringadd.save = function(cb) {
        if( !this.checkForm() ) { return false; }
        var c = this.serializeForm('yes');
        M.api.postJSONCb('ciniki.courses.offeringAdd', {'tnid':M.curTenantID, 'copy_offering_id':this.copy_offering_id}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_courses_main.offering.offering_id = rsp.id;
            M.ciniki_courses_main.offeringadd.close();
        });
    }
    this.offeringadd.addButton('save', 'Save', 'M.ciniki_courses_main.offeringadd.save();');
    this.offeringadd.addClose('Cancel');

    //
    // The panel for editing a registrant
    //
    this.price = new M.panel('Session Price', 'ciniki_courses_main', 'price', 'mc', 'medium', 'sectioned', 'ciniki.courses.main.price');
    this.price.data = null;
    this.price.offering_id = 0;
    this.price.price_id = 0;
    this.price.sections = { 
        'price':{'label':'Price', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'available_to':{'label':'Available', 'type':'flags', 'default':'1', 'flags':{}},
//              'valid_from':{'label':'Valid From', 'hint':'', 'type':'text'},
//              'valid_to':{'label':'Valid To', 'hint':'', 'type':'text'},
            'unit_amount':{'label':'Unit Amount', 'type':'text', 'size':'small'},
            'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
            'unit_discount_percentage':{'label':'Discount Percent', 'type':'text', 'size':'small'},
            'taxtype_id':{'label':'Taxes', 'active':'no', 'type':'select', 'options':{}},
            'webflags':{'label':'Web', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':{}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.price.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_courses_main.price.remove();',
                'visible':function() { return M.ciniki_courses_main.price.price_id > 0 ? 'yes' : 'no'; },
                },
            }},
        };  
    this.price.fieldValue = function(s, i, d) { return this.data[i]; }
    this.price.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.offeringPriceHistory', 'args':{'tnid':M.curTenantID, 
            'price_id':this.price_id, 'offering_id':this.offering_id, 'field':i}};
    }
    this.price.sectionData = function(s) {
        return this.data[s];
    }
    this.price.rowFn = function(s, i, d) { return ''; }
    this.price.open = function(cb, pid, oid) {
        this.reset();
        if( pid != null ) { this.price_id = pid; }
        if( oid != null ) { this.offering_id = oid; }
        this.sections._buttons.buttons.delete.visible = 'yes';
        M.api.getJSONCb('ciniki.courses.offeringPriceGet', {'tnid':M.curTenantID, 'price_id':this.price_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.price;
            p.data = rsp.price;
            if( rsp.price.offering_id > 0 ) {
                p.offering_id = rsp.price.offering_id;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.price.save = function() {
        if( this.price_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.courses.offeringPriceUpdate', 
                    {'tnid':M.curTenantID, 
                    'price_id':M.ciniki_courses_main.price.price_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                    M.ciniki_courses_main.price.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.courses.offeringPriceAdd', 
                {'tnid':M.curTenantID, 'offering_id':this.offering_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_courses_main.price.close();
                });
        }
    }
    this.price.remove = function() {
        M.confirm("Are you sure you want to remove this price?",null,function() {
            M.api.getJSONCb('ciniki.courses.offeringPriceDelete', 
                {'tnid':M.curTenantID, 'price_id':M.ciniki_courses_main.price.price_id}, 
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_main.price.close();   
                });
        });
    }
    this.price.addButton('save', 'Save', 'M.ciniki_courses_main.price.save();');
    this.price.addClose('Cancel');

    //
    // The panel to display the add/edit a class (called cclass[course class] so not using reserved word)
    //
    this.cclass = new M.panel('Edit Class', 'ciniki_courses_main', 'cclass', 'mc', 'medium', 'sectioned', 'ciniki.courses.main.cclass');
    this.cclass.default_data = {'class_date':'', 'start_time':'', 'end_time':'', 'notes':''};
    this.cclass.data = {};
    this.cclass.course_id = 0;
    this.cclass.offering_id = 0;
    this.cclass.class_id = 0;
    this.cclass.sections = {
        'info':{'label':'Information', 'type':'simpleform', 'fields':{
            'class_date':{'label':'Date', 'type':'date'},
            'start_time':{'label':'Start Time', 'type':'text', 'size':'small'},
            'end_time':{'label':'End Time', 'type':'text', 'size':'small'},
        }},
        '_notes':{'label':'Notes', 'type':'simpleform', 'fields':{
            'notes':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
        }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.cclass.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_courses_main.cclass.remove();'},
        }},
    };
    this.cclass.fieldValue = function(s, i, d) { 
        if( this.data[i] != null ) {
            return this.data[i]; 
        } 
        return ''; 
    };
    this.cclass.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.offeringClassHistory', 'args':{'tnid':M.curTenantID, 
            'class_id':this.class_id, 'field':i}};
    };
    this.cclass.open = function(cb, cid, oid, ocid) {
        if( cid != null ) { this.class_id = cid; }
        if( oid != null ) { this.offering_id = oid; }
        if( ocid != null ) { this.course_id = ocid; }
        M.api.getJSONCb('ciniki.courses.offeringClassGet', {'tnid':M.curTenantID, 'class_id':this.class_id, 'offering_id':this.offering_id}, function (rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_courses_main.cclass.data = rsp['class'];
            M.ciniki_courses_main.cclass.refresh();
            M.ciniki_courses_main.cclass.show(cb);
        });
    }
    this.cclass.save = function() {
        if( this.class_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.courses.offeringClassUpdate', {'tnid':M.curTenantID, 'class_id':this.class_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_courses_main.cclass.close();
                    });
            }
        } else {
            var c = this.serializeForm('yes');
            c += '&offering_id=' + encodeURIComponent(this.offering_id);
            c += '&course_id=' + encodeURIComponent(this.course_id);
            if( c != null ) {
                M.api.postJSONFormData('ciniki.courses.offeringClassAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_courses_main.cclass.close();
                });
            }
        }
    }
    this.cclass.remove = function() {
        M.confirm('Are you sure you want to delete \'' + this.data.class_date + '\'?',null,function() {
            M.api.getJSONCb('ciniki.courses.offeringClassDelete', {'tnid':M.curTenantID, 
                'class_id':M.ciniki_courses_main.cclass.class_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_main.cclass.close();
                });
        });
    }
    this.cclass.addButton('save', 'Save', 'M.ciniki_courses_main.cclass.save();');
    this.cclass.addClose('Cancel');

    //
    // The panel to attach an instructor to an offering
    //
    this.oinstructor = new M.panel('Session Instructor', 'ciniki_courses_main', 'oinstructor', 'mc', 'medium', 'sectioned', 'ciniki.courses.main.oinstructor');
    this.oinstructor.data = {};
    this.oinstructor.offering_id = 0;
    this.oinstructor.course_id = 0;
    this.oinstructor.sections = {
        '_instructor':{'label':'Choose Instructor', 'fields':{
            'instructor_id':{'label':'', 'hidelabel':'yes', 'type':'select', 'options':{}, 'complex_options':{'value':'id', 'name':'name'}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'add':{'label':'Add Instructor', 'fn':'M.ciniki_courses_main.oinstructor.save();'},
            'cancel':{'label':'Cancel', 'fn':'M.ciniki_courses_main.oinstructor.close();'},
            }},
    }
    this.oinstructor.open = function(cb, oid, cid) {
        if( oid != null ) { this.offering_id = oid; }
        if( cid != null ) { this.course_id = cid; }
        M.api.getJSONCb('ciniki.courses.instructorList', {'tnid':M.curTenantID, 'status':'10'}, 
            function (rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_courses_main.oinstructor;
                //
                // Remove instructors already attached to offering
                //
                for(var i in M.ciniki_courses_main.offering.data.instructors) {
                    for(var j in rsp.instructors) {
                        if( M.ciniki_courses_main.offering.data.instructors[i].instructor_id == rsp.instructors[j].id ) {
                            delete rsp.instructors[j];
                        }
                    }
                }
                p.sections._instructor.fields.instructor_id.options = rsp.instructors;
                p.refresh();
                p.show(cb);
            });
    }
    this.oinstructor.save = function() {
        var c = this.serializeForm('yes');
        c += '&offering_id=' + encodeURIComponent(this.offering_id);
        c += '&course_id=' + encodeURIComponent(this.course_id);
        if( c != null ) {
            M.api.postJSONFormData('ciniki.courses.offeringInstructorAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_courses_main.oinstructor.close();
            });
        }
    }
    this.oinstructor.addButton('save', 'Save', 'M.ciniki_courses_main.oinstructor.save();');
    this.oinstructor.addClose('Cancel');

    //
    // Add or edit a course file
    //
    this.offeringfile = new M.panel('Session File', 'ciniki_courses_main', 'offeringfile', 'mc', 'medium', 'sectioned', 'ciniki.courses.main.offeringfile');
    this.offeringfile.file_id = 0;
    this.offeringfile.offering_id = 0;
    this.offeringfile.data = null;
    this.offeringfile.sections = {
        '_file':{'label':'File', 
            'visible':function() { return M.ciniki_courses_main.offeringfile.file_id > 0 ? 'no' : 'yes'; },
            'fields':{
                'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
        }},
        'info':{'label':'Details', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text'},
            'webflags':{'label':'Options', 'type':'flags', 'default':1, 'flags':{'1':{'name':'Visible'}, '5':{'name':'Paid Content'}}},
        }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.offeringfile.save();'},
            'download':{'label':'Download', 'fn':'M.ciniki_courses_main.offeringfile.download();',
                'visible':function() { return M.ciniki_courses_main.offeringfile.file_id > 0 ? 'yes' : 'no'; },
                },
            'delete':{'label':'Delete', 'fn':'M.ciniki_courses_main.offeringfile.remove();',
                'visible':function() { return M.ciniki_courses_main.offeringfile.file_id > 0 ? 'yes' : 'no'; },
                },
        }},
    }
    this.offeringfile.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.offeringFileHistory', 'args':{'tnid':M.curTenantID, 
            'file_id':this.file_id, 'field':i}};
    }
    this.offeringfile.download = function() {
        M.api.openFile('ciniki.courses.offeringFileDownload', {'tnid':M.curTenantID, 'file_id':this.file_id});
    }
    this.offeringfile.open = function(cb, fid, oid) {
        if( fid != null ) { this.file_id = fid; }
        if( oid != null ) { this.offering_id = oid; }
        M.api.getJSONCb('ciniki.courses.offeringFileGet', {'tnid':M.curTenantID, 'file_id':this.file_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.offeringfile;
            p.data = rsp.file;
            p.refresh();
            p.show(cb);
        });
    }
    this.offeringfile.save = function() {
        if( this.file_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.courses.offeringFileUpdate', {'tnid':M.curTenantID, 'file_id':this.file_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_courses_main.offeringfile.close();
                    });
            }
        } else {
            var c = this.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.courses.offeringFileAdd', {'tnid':M.curTenantID, 'offering_id':this.offering_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_courses_main.offeringfile.close();
                });
        }
    }
    this.offeringfile.remove = function() {
        M.confirm('Are you sure you want to delete \'' + this.data.name + '\'?  All information about it will be removed and unrecoverable.',null,function() {
            M.api.getJSONCb('ciniki.courses.offeringFileDelete', {'tnid':M.curTenantID, 
                'file_id':M.ciniki_courses_main.offeringfile.file_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_courses_main.offeringfile.close();
                });
        });
    }
    this.offeringfile.addButton('save', 'Save', 'M.ciniki_courses_main.offeringfile.save();');
    this.offeringfile.addClose('Cancel');
    
    //
    // The panel to edit Image
    //
    this.offeringimage = new M.panel('Session Image', 'ciniki_courses_main', 'offeringimage', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.courses.main.offeringimage');
    this.offeringimage.data = null;
    this.offeringimage.offering_id = 0;
    this.offeringimage.offering_image_id = 0;
    this.offeringimage.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_courses_main.offeringimage.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.offeringimage.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_courses_main.offeringimage.offering_image_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_courses_main.offeringimage.remove();'},
            }},
        };
    this.offeringimage.fieldValue = function(s, i, d) { return this.data[i]; }
    this.offeringimage.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.offeringImageHistory', 'args':{'tnid':M.curTenantID, 'offering_image_id':this.offering_image_id, 'field':i}};
    }
    this.offeringimage.open = function(cb, cid, offering_id) {
        if( cid != null ) { this.offering_image_id = cid; }
        if( offering_id != null ) { this.offering_id = offering_id; }
        M.api.getJSONCb('ciniki.courses.offeringImageGet', {'tnid':M.curTenantID, 'offering_image_id':this.offering_image_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.offeringimage;
            p.data = rsp.image;
            p.refresh();
            p.show(cb);
        });
    }
    this.offeringimage.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_courses_main.offeringimage.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.offering_image_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.courses.offeringImageUpdate', {'tnid':M.curTenantID, 'offering_image_id':this.offering_image_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.courses.offeringImageAdd', {'tnid':M.curTenantID, 'offering_id':this.offering_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.offeringimage.offering_image_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.offeringimage.remove = function() {
        M.confirm('Are you sure you want to remove image?',null,function() {
            M.api.getJSONCb('ciniki.courses.offeringImageDelete', {'tnid':M.curTenantID, 'offering_image_id':M.ciniki_courses_main.offeringimage.offering_image_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.offeringimage.close();
            });
        });
    }
    this.offeringimage.addButton('save', 'Save', 'M.ciniki_courses_main.offeringimage.save();');
    this.offeringimage.addClose('Cancel');

    //
    // The panel to list the course
    //
    this.courses = new M.panel('Programs', 'ciniki_courses_main', 'courses', 'mc', 'xlarge narrowaside', 'sectioned', 'ciniki.courses.main.courses');
    this.courses.data = {};
    this.courses.nplist = [];
    this.courses.sections = {
        '_tabs':this.menutabs,
        'statuses':{'label':'Status', 'type':'simplegrid', 'num_cols':1, 'aside':'yes', 'selected':'30',
            },
        'levels':{'label':'Levels', 'type':'simplegrid', 'num_cols':1, 'aside':'yes', 'selected':'__',
            'collapsable':'yes', 'collapse':'all',
            },
        'types':{'label':'Types', 'type':'simplegrid', 'num_cols':1, 'aside':'yes', 'selected':'__',
            'visible':function() { return M.modFlagSet('ciniki.courses', 0x10);},
            'collapsable':'yes', 'collapse':'all',
            },
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1, 'aside':'yes', 'selected':'__',
            'visible':function() { return M.modFlagSet('ciniki.courses', 0x4000);},
            'collapsable':'yes', 'collapse':'all',
            },
        'mediums':{'label':'Mediums', 'type':'simplegrid', 'num_cols':1, 'aside':'yes', 'selected':'__',
            'visible':function() { return M.modFlagSet('ciniki.courses', 0x1000);},
            'collapsable':'yes', 'collapse':'all',
            },
        'ages':{'label':'Ages', 'type':'simplegrid', 'num_cols':1, 'aside':'yes', 'selected':'__',
            'visible':function() { return M.modFlagSet('ciniki.courses', 0x2000);},
            'collapsable':'yes', 'collapse':'all',
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
            'cellClasses':[''],
            'hint':'Search programs',
            'noData':'No programs found',
            },
        'courses':{'label':'Program', 'type':'simplegrid', 'num_cols':1,
            'headerValues':[],
            'noData':'No program',
            'sortable':'yes',
            'sortTypes':[],
            'dataMaps':[],
            'addTxt':'Add Program',
            'addFn':'M.ciniki_courses_main.course.open(\'M.ciniki_courses_main.courses.open();\',0,null,0);'
            },
    }
    this.courses.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.courses.courseSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_courses_main.courses.liveSearchShow('search',null,M.gE(M.ciniki_courses_main.courses.panelUID + '_' + s), rsp.courses);
                });
        }
    }
    this.courses.liveSearchResultValue = function(s, f, i, j, d) {
        return this.cellValue(s, i, j, d);
    }
    this.courses.liveSearchResultRowFn = function(s, f, i, j, d) {
        return this.rowFn(s, i, d);
    }
    this.courses.cellValue = function(s, i, j, d) {
        if( s == 'statuses' || s == 'levels' || s == 'types' || s == 'categories' || s == 'mediums' || s == 'ages' ) {
            return M.textCount(d.label, d.num_courses);
        }
        if( s == 'search' || s == 'courses' ) {
            if( this.sections.courses.dataMaps[j] == 'start_date' && (d.flags&0x10) == 0x10 ) {
                return 'n/a';
            }
            if( this.sections.courses.dataMaps[j] == 'end_date' && (d.flags&0x10) == 0x10 ) {
                return 'n/a';
            }
            if( this.sections.courses.dataMaps[j] == 'course_name' ) {
                if( d.course_code != '' ) {
                    return d.course_name + ' - ' + d.course_code;
                }
                return d.course_name;
            }
            return d[this.sections.courses.dataMaps[j]];
        }
    }
    this.courses.rowClass = function(s, i, d) {
        if( s == 'statuses' || s == 'levels' || s == 'types' || s == 'categories' || s == 'mediums' || s == 'ages' ) {
            if( this.sections[s].selected == d.value ) {
                return 'highlight';
            }
        }
        return '';
    }
    this.courses.rowFn = function(s, i, d) {
        if( s == 'statuses' || s == 'levels' || s == 'types' || s == 'categories' || s == 'mediums' || s == 'ages' ) {
            return 'M.ciniki_courses_main.courses.setFilter(\'' + s + '\',\'' + d.value + '\');'
        }
        if( s == 'search' || s == 'courses' ) {
            return 'M.ciniki_courses_main.course.open(\'M.ciniki_courses_main.courses.open();\',\'' + d.id + '\',M.ciniki_courses_main.course.nplist,0);';
        }
    }
    this.courses.setFilter = function(s, v) {
        if( this.sections[s].selected == v && this.sections[s].collapsed == 'yes' ) {
            this.toggleSection(null, s);
            return false;
        } else if( this.sections[s].selected == v && this.sections[s].collapsed == 'no' ) {
            this.toggleSection(null, s);
            return false;
        }
        this.sections[s].selected = v;
        if( s != 'statuses' ) {
            if( s != 'levels' ) { this.sections['levels'].selected = '__'; }
            if( s != 'types' ) { this.sections['types'].selected = '__'; }
            if( s != 'categories' ) { this.sections['categories'].selected = '__'; }
            if( s != 'mediums' ) { this.sections['mediums'].selected = '__'; }
            if( s != 'ages' ) { this.sections['ages'].selected = '__'; }
        }
        this.lastY = 0;
        this.open();
    }
    this.courses.open = function(cb) {
        var args = {
            'tnid':M.curTenantID, 
            'stats':'yes',
            'status':this.sections.statuses.selected,
            'level':this.sections.levels.selected,
            }; 
        if( M.modFlagOn('ciniki.courses', 0x10) ) {
            args['type'] = this.sections.types.selected;
        }
        if( M.modFlagOn('ciniki.courses', 0x4000) ) {
            args['category'] = this.sections.categories.selected;
        }
        if( M.modFlagOn('ciniki.courses', 0x1000) ) {
            args['medium'] = this.sections.mediums.selected;
        }
        if( M.modFlagOn('ciniki.courses', 0x2000) ) {
            args['ages'] = this.sections.ages.selected;
        }
        M.api.getJSONCb('ciniki.courses.courseList', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.courses;
            p.data = rsp;
            var fields = ['statuses', 'levels', 'types', 'categories', 'mediums', 'ages'];
            // Check if selected fields exist and reset if not.
            for(var i in fields) {
                var found = 0;
                for(var j in rsp[fields[i]]) {
                    if( rsp[fields[i]][j].value == p.sections[fields[i]].selected ) {
                        found = 1;
                        break;
                    }
                }
                if( found == 0 ) {
                    p.sections[fields[i]].selected = rsp[fields[i]][0].value;
                }
            }
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.courses.addClose('Back');

    //
    // The panel to edit course
    //
    this.course = new M.panel('Program', 'ciniki_courses_main', 'course', 'mc', 'large mediumaside', 'sectioned', 'ciniki.courses.main.course');
    this.course.data = null;
    this.course.course_id = 0;
    this.course.form_submission_id = 0;
    this.course.nplist = [];
    this.course.sections = {
        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_courses_main.course.setFieldValue('primary_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'Program', 'aside':'yes', 'fields':{
            'code':{'label':'Code', 'type':'text', 'size':'small',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x01);},
                },
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Draft', '30':'Active', '70':'Private', '90':'Archived'}},
            'level':{'label':'Level', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'type':{'label':'Type', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x10);},
                },
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x4000);},
                },
            'medium':{'label':'Medium', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x1000);},
                },
            'ages':{'label':'Ages', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x2000);},
                },
//            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Children'}}},
            }},
        '_options':{'label':'Options', 'aside':'yes', 'fields':{   
            'flags1':{'label':'Children', 'type':'flagtoggle', 'field':'flags', 'bit':0x01, 'default':'off'},
            'flags5':{'label':'Timeless', 'type':'flagtoggle', 'field':'flags', 'bit':0x10, 'default':'off',
                'onchange':'M.ciniki_courses_main.course.timelessChange',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x010000); },
                },
            'flags6':{'label':'Session Details', 'type':'flagtoggle', 'field':'flags', 'bit':0x20, 'default':'off',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x020000); },
                },
            'flags7':{'label':'Paid Content', 'type':'flagtoggle', 'field':'flags', 'bit':0x40, 'default':'off',
                'onchange':'M.ciniki_courses_main.course.paidChange',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x040000); },
                },
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'info', 'tabs':{
            'info':{'label':'Info', 'fn':'M.ciniki_courses_main.course.switchTab("info");'},
            'paid':{'label':'Paid Content', 
                'visible':function() { return (M.ciniki_courses_main.course.data.flags&0x40) == 0x40 ? 'yes' : 'no';},
                'fn':'M.ciniki_courses_main.course.switchTab("paid");',
                },
            'files':{'label':'Files', 'fn':'M.ciniki_courses_main.course.switchTab("files");',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x08); },
                },
            'images':{'label':'Gallery', 'fn':'M.ciniki_courses_main.course.switchTab("images");',
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x0200); },
                },
            'offerings':{'label':'Sessions', 'fn':'M.ciniki_courses_main.course.switchTab("offerings");'},
            }},
        '_short_description':{'label':'Synopsis', 
            'visible':function() { return M.ciniki_courses_main.course.sections._tabs.selected == 'info' ? 'yes' : 'hidden'; },
            'fields':{
                'short_description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_long_description':{'label':'Description', 
            'visible':function() { return M.ciniki_courses_main.course.sections._tabs.selected == 'info' ? 'yes' : 'hidden'; },
            'fields':{
                'long_description':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_materials':{'label':'Materials List', 
            'visible':function() { return M.ciniki_courses_main.course.sections._tabs.selected == 'info' ? 'yes' : 'hidden'; },
            'fields':{
                'materials_list':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_paid_content':{'label':'Paid Content', 
            'visible':function() { return M.ciniki_courses_main.course.sections._tabs.selected == 'paid' ? 'yes' : 'hidden'; },
            'fields':{
                'paid_content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'xlarge'},
            }},
        'files':{'label':'Program Files', 'type':'simplegrid', 'num_cols':3,
            'visible':function() { return M.ciniki_courses_main.course.sections._tabs.selected == 'files' ? 'yes' : 'hidden'; },
            'headerValues':['File', 'Visible', 'Paid'],
            'noData':'No files',
            'addTxt':'Add File',
            'addFn':'M.ciniki_courses_main.course.save("M.ciniki_courses_main.coursefile.open(\'M.ciniki_courses_main.course.open();\',0,M.ciniki_courses_main.course.course_id);");',
            },
        'images':{'label':'Gallery', 'type':'simplethumbs',
            'visible':function() { return M.ciniki_courses_main.course.sections._tabs.selected == 'images' ? 'yes' : 'hidden';},
            },
        '_images':{'label':'', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.ciniki_courses_main.course.sections._tabs.selected == 'images' ? 'yes' : 'hidden';},
            'addTxt':'Add Image',
            'addFn':'M.ciniki_courses_main.course.save("M.ciniki_courses_main.courseimage.open(\'M.ciniki_courses_main.course.open();\',0,M.ciniki_courses_main.course.course_id);");',
            },
        'offerings':{'label':'Active Sessions', 'type':'simplegrid', 'num_cols':7,
            'visible':function() { return M.ciniki_courses_main.course.sections._tabs.selected == 'offerings' ? 'yes' : 'hidden';},
            'headerValues':[],
            'noData':'No sessions',
            'sortable':'yes',
            'sortTypes':[],
            'dataMaps':[],
            'addTxt':'Add Session',
            'addFn':'M.ciniki_courses_main.course.save("M.ciniki_courses_main.offering.open(\'M.ciniki_courses_main.course.open();\',0,M.ciniki_courses_main.course.course_id);");',
            },
        'archived':{'label':'Archived Sessions', 'type':'simplegrid', 'num_cols':7,
            'visible':function() { return M.ciniki_courses_main.course.sections._tabs.selected == 'offerings' && M.ciniki_courses_main.course.data.archived != null ? 'yes' : 'hidden';},
            'headerValues':[],
            'noData':'No sessions',
            'sortable':'yes',
            'sortTypes':[],
            'dataMaps':[],
            'addTxt':'Add Session',
            'addFn':'M.ciniki_courses_main.course.save("M.ciniki_courses_main.offering.open(\'M.ciniki_courses_main.course.open();\',0,M.ciniki_courses_main.course.course_id);");',
            },
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.course.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_courses_main.course.course_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_courses_main.course.remove();'},
            }},
        };
    this.course.fieldValue = function(s, i, d) { return this.data[i]; }
    this.course.thumbFn = function(s, i, d) {
        return 'M.ciniki_courses_main.course.save("M.ciniki_courses_main.courseimage.open(\'M.ciniki_courses_main.course.open();\',' + d.id + ',M.ciniki_courses_main.course.course_id);");';
    }
    this.course.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.courseHistory', 'args':{'tnid':M.curTenantID, 'course_id':this.course_id, 'field':i}};
    }
    this.course.liveSearchCb = function(s, i, v) {
        if( i == 'type' || i == 'category' || i == 'level' || i == 'medium' || i == 'ages' ) {
            M.api.getJSONBgCb('ciniki.courses.courseSearchField', {'tnid':M.curTenantID, 'start_needle':v, 'field':i, 'limit':25},
                function(rsp) { 
                    M.ciniki_courses_main.course.liveSearchShow(s, i, M.gE(M.ciniki_courses_main.course.panelUID + '_' + i), rsp.results); 
                });
        }
    }
    this.course.liveSearchResultValue = function(s, f, i, j, d) {
        return d.result.name;
    }
    this.course.liveSearchResultRowFn = function(s, f, i, j, d) { 
        return 'M.ciniki_courses_main.course.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.result.name) + '\');';
    }
    this.course.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    }
    this.course.cellValue = function(s, i, j, d) {
        if( s == 'files' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.visible;
                case 2: return d.paid_content;
            }
        }
        if( s == 'offerings' || s == 'archived' ) {
            var dates = 'on';
            if( (this.data.flags&0x10) == 0x10 ) {
                dates = 'off';
            }
            if( this.sections.offerings.dataMaps[j] == 'actions' ) {
                return M.faBtn('&#xf24d;', 'Duplicate', 'M.ciniki_courses_main.course.duplicateOffering(' + d.id + ');');
            }
            else if( this.sections.offerings.dataMaps[j] == 'registrations' ) {
                return d.num_registrations + '/' + d.num_seats;
            }
            else if( dates == 'off' && this.sections[s].dataMaps[j] == 'start_date' ) {
                return 'n/a';
            }
            else if( dates == 'off' && this.sections[s].dataMaps[j] == 'end_date' ) {
                return 'n/a';
            }
            return d[this.sections[s].dataMaps[j]];
        }
    }
    this.course.rowFn = function(s, i, d) {
        if( s == 'files' ) {
            return 'M.ciniki_courses_main.course.save("M.ciniki_courses_main.coursefile.open(\'M.ciniki_courses_main.course.open();\',' + d.id + ',M.ciniki_courses_main.course.course_id);");';
        }
        if( s == 'offerings' || s == 'archived' ) {
            return 'M.ciniki_courses_main.course.save("M.ciniki_courses_main.offering.open(\'M.ciniki_courses_main.course.open();\',' + d.id + ',M.ciniki_courses_main.course.course_id);");';
        }
    }
    this.course.duplicateOffering = function(oid) {
        this.save("M.ciniki_courses_main.offering.open('M.ciniki_courses_main.course.open();',0,M.ciniki_courses_main.course.course_id,null,'" + oid + "');");
    }
    this.course.timelessChange = function() {
        this.save('M.ciniki_courses_main.course.refreshSessions();');
    }
    this.course.refreshSessions = function() {
        if( this.formValue('flags5') == 'on' ) {
            this.data.flags |= 0x10;
        } else {
            this.data.flags = this.data.flags&0xFFFFFFEF;
        }
        this.refreshSections(['offerings', 'archived']);
    }
    this.course.paidChange = function() {
        this.save('M.ciniki_courses_main.course.refreshTabs();');
    }
    this.course.refreshTabs = function() {
        if( this.formValue('flags7') == 'on' ) {
            this.data.flags |= 0x40;
        } else {
            this.data.flags = this.data.flags&0xFFFFFFBF;
            if( this.sections._tabs.selected == 'paid' ) {
                this.switchTab('info');
            }
        }
        this.refreshSections(['_tabs']);
    }
    this.course.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSection('_tabs');
        this.showHideSections(['_short_description', '_long_description', '_materials', '_paid_content', 'files', 'images', '_images', 'offerings', 'archived']);
    }
    this.course.open = function(cb, cid, list, form_sub_id) {
        if( cid != null ) { this.course_id = cid; }
        if( list != null ) { this.nplist = list; }
        if( form_sub_id != null ) { this.form_submission_id = form_sub_id; }
        M.api.getJSONCb('ciniki.courses.courseGet', {'tnid':M.curTenantID, 'course_id':this.course_id, 'form_submission_id':this.form_submission_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.course;
            p.data = rsp.course;
            if( (p.data.flags&0x40) == 0 && p.sections._tabs.selected == 'paid' ) {
                p.sections._tabs.selected = 'info';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.course.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_courses_main.course.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.course_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.courses.courseUpdate', {'tnid':M.curTenantID, 'course_id':this.course_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.courses.courseAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.course.course_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.course.remove = function() {
        M.confirm('Are you sure you want to remove \'' + this.data.name + '\' program?  All information about it will be removed and unrecoverable.',null,function() {
            M.api.getJSONCb('ciniki.courses.courseDelete', {'tnid':M.curTenantID, 'course_id':M.ciniki_courses_main.course.course_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.course.close();
            });
        });
    }
    this.course.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.course_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_courses_main.course.save(\'M.ciniki_courses_main.course.open(null,' + this.nplist[this.nplist.indexOf('' + this.course_id) + 1] + ');\');';
        }
        return null;
    }
    this.course.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.course_id) > 0 ) {
            return 'M.ciniki_courses_main.course.save(\'M.ciniki_courses_main.course.open(null,' + this.nplist[this.nplist.indexOf('' + this.course_id) - 1] + ');\');';
        }
        return null;
    }
    this.course.addButton('save', 'Save', 'M.ciniki_courses_main.course.save();');
    this.course.addClose('Cancel');
    this.course.addButton('next', 'Next');
    this.course.addLeftButton('prev', 'Prev');

    //
    // Add or edit a course file
    //
    this.coursefile = new M.panel('Program File', 'ciniki_courses_main', 'coursefile', 'mc', 'medium', 'sectioned', 'ciniki.courses.main.coursefile');
    this.coursefile.file_id = 0;
    this.coursefile.course_id = 0;
    this.coursefile.data = null;
    this.coursefile.sections = {
        '_file':{'label':'File', 
            'visible':function() { return M.ciniki_courses_main.coursefile.file_id > 0 ? 'no' : 'yes'; },
            'fields':{
                'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
        }},
        'info':{'label':'Details', 'type':'simpleform', 'fields':{
            'name':{'label':'Title', 'type':'text'},
            'webflags':{'label':'Options', 'type':'flags', 'default':1, 'flags':{'1':{'name':'Visible'}, '5':{'name':'Paid Content'}}},
        }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.coursefile.save();'},
            'download':{'label':'Download', 'fn':'M.ciniki_courses_main.coursefile.download();',
                'visible':function() { return M.ciniki_courses_main.coursefile.file_id > 0 ? 'yes' : 'no'; },
                },
            'delete':{'label':'Delete', 'fn':'M.ciniki_courses_main.coursefile.remove();',
                'visible':function() { return M.ciniki_courses_main.coursefile.file_id > 0 ? 'yes' : 'no'; },
                },
        }},
    }
    this.coursefile.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.fileHistory', 'args':{'tnid':M.curTenantID, 
            'file_id':this.file_id, 'field':i}};
    }
    this.coursefile.download = function() {
        M.api.openFile('ciniki.courses.fileDownload', {'tnid':M.curTenantID, 'file_id':this.file_id});
    }
    this.coursefile.open = function(cb, fid, cid) {
        if( fid != null ) { this.file_id = fid; }
        if( cid != null ) { this.course_id = cid; }
        M.api.getJSONCb('ciniki.courses.fileGet', {'tnid':M.curTenantID, 'file_id':this.file_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.coursefile;
            p.data = rsp.file;
            p.refresh();
            p.show(cb);
        });
    }
    this.coursefile.save = function() {
        if( this.file_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.courses.fileUpdate', {'tnid':M.curTenantID, 'file_id':this.file_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_courses_main.coursefile.close();
                    });
            }
        } else {
            var c = this.serializeFormData('yes');
            M.api.postJSONFormData('ciniki.courses.fileAdd', {'tnid':M.curTenantID, 'course_id':this.course_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_courses_main.coursefile.close();
                });
        }
    }
    this.coursefile.remove = function() {
        M.confirm('Are you sure you want to delete \'' + this.data.name + '\'?  All information about it will be removed and unrecoverable.',null,function() {
            M.api.getJSONCb('ciniki.courses.fileDelete', {'tnid':M.curTenantID, 
                'file_id':M.ciniki_courses_main.coursefile.file_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_courses_main.coursefile.close();
                });
        });
    }
    this.coursefile.addButton('save', 'Save', 'M.ciniki_courses_main.coursefile.save();');
    this.coursefile.addClose('Cancel');

    //
    // The panel to edit Image
    //
    this.courseimage = new M.panel('Program Image', 'ciniki_courses_main', 'courseimage', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.courses.main.courseimage');
    this.courseimage.data = null;
    this.courseimage.course_id = 0;
    this.courseimage.course_image_id = 0;
    this.courseimage.sections = {
        '_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_courses_main.courseimage.setFieldValue('image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
             },
        }},
        'general':{'label':'', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Visible'}}},
            }},
        '_description':{'label':'Description', 'fields':{
            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.courseimage.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_courses_main.courseimage.course_image_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_courses_main.courseimage.remove();'},
            }},
        };
    this.courseimage.fieldValue = function(s, i, d) { return this.data[i]; }
    this.courseimage.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.imageHistory', 'args':{'tnid':M.curTenantID, 'course_image_id':this.course_image_id, 'field':i}};
    }
    this.courseimage.open = function(cb, cid, course_id) {
        if( cid != null ) { this.course_image_id = cid; }
        if( course_id != null ) { this.course_id = course_id; }
        M.api.getJSONCb('ciniki.courses.imageGet', {'tnid':M.curTenantID, 'course_image_id':this.course_image_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.courseimage;
            p.data = rsp.image;
            p.refresh();
            p.show(cb);
        });
    }
    this.courseimage.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_courses_main.courseimage.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.course_image_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.courses.imageUpdate', {'tnid':M.curTenantID, 'course_image_id':this.course_image_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.courses.imageAdd', {'tnid':M.curTenantID, 'course_id':this.course_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.courseimage.course_image_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.courseimage.remove = function() {
        M.confirm('Are you sure you want to remove image?',null,function() {
            M.api.getJSONCb('ciniki.courses.imageDelete', {'tnid':M.curTenantID, 'course_image_id':M.ciniki_courses_main.courseimage.course_image_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.courseimage.close();
            });
        });
    }
    this.courseimage.addButton('save', 'Save', 'M.ciniki_courses_main.courseimage.save();');
    this.courseimage.addClose('Cancel');

    //
    // The panel to display the complete list of instructors
    //
    this.instructors = new M.panel('Instructors', 'ciniki_courses_main', 'instructors', 'mc', 'large narrowaside', 'sectioned', 'ciniki.courses.main.instructors');
    this.instructors.sections = {
        '_tabs':this.menutabs,
        'statuses':{'label':'Status', 'type':'simplegrid', 'num_cols':1, 'aside':'yes', 'selected':'10',
            },
        'instructors':{'label':'Instructors', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Name', 'Status', '# of Sessions', 'Last Session'],
            'headerClasses':['', '', 'aligncenter', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'number', 'date'],
            'cellClasses':['', '', 'aligncenter', 'alignright'],
            'addTxt':'Add Instructor',
            'addFn':'M.ciniki_courses_main.instructor.open(\'M.ciniki_courses_main.instructors.open();\',0,0);',
            },
    };
    this.instructors.sectionData = function(s) {
        return this.data[s];
    };
    this.instructors.cellValue = function(s, i, j, d) {
        if( s == 'statuses' ) {
            return M.textCount(d.label, d.num_instructors);
        }
        if( s == 'instructors' ) {
            switch(j) {
                case 0: return d.name;
                case 1: return d.status_text;
                case 2: return d.num_offerings;
                case 3: return d.last_offering;
            }
        }
    }
    this.instructors.rowClass = function(s, i, d) {
        if( s == 'statuses' && this.sections[s].selected == d.value ) {
            return 'highlight';
        }
        return '';
    }
    this.instructors.rowFn = function(s, i, d) {
        if( s == 'statuses' ) {
            return 'M.ciniki_courses_main.instructors.setFilter(\'' + s + '\',\'' + d.value + '\');'
        } 
        if( s == 'instructors' ) {
            return 'M.ciniki_courses_main.instructor.open(\'M.ciniki_courses_main.instructors.open();\',\'' + d.id + '\',0);';
        }
    };
    this.instructors.setFilter = function(s, v) {
        this.sections[s].selected = v;
        this.lastY = 0;
        this.open();
    }
    this.instructors.open = function(cb) {
        M.api.getJSONCb('ciniki.courses.instructorList', {'tnid':M.curTenantID, 'status':this.sections.statuses.selected, 'stats':'yes'}, 
            function (rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_courses_main.instructors;
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
    };
    this.instructors.addClose('Back');

    //
    // The panel to display the add/instructor form
    //
    this.instructor = new M.panel('Edit Instructor',
        'ciniki_courses_main', 'instructor',
        'mc', 'large mediumaside', 'sectioned', 'ciniki.courses.main.instructor');
    this.instructor.default_data = {'first':'', 'last':'', 'webflags':0};
    this.instructor.data = {};
    this.instructor.course_id = 0;
    this.instructor.offering_id = 0;
    this.instructor.offering_instructor_id = 0;
    this.instructor.instructor_id = 0;
    this.instructor.form_submission_id = 0;
    this.instructor.customer_id = 0;
    this.instructor.sections = {
        '_image':{'label':'', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
        }},
        'customer_details':{'label':'Instructor', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'cellClasses':['label', ''],
            'addTxt':'Edit',
            'addFn':'M.ciniki_courses_main.instructor.editCustomer();',
            'changeTxt':'Change Customer',
            'changeFn':'M.ciniki_courses_main.instructor.changeCustomer();',
            },
        'instructor':{'label':'', 'aside':'yes', 'fields':{
            'first':{'label':'First', 'type':'text',
                'visible':function() { return M.ciniki_courses_main.instructor.data.customer_id > 0 ? 'no' : 'yes'; },
                },
            'last':{'label':'Last', 'type':'text',
                'visible':function() { return M.ciniki_courses_main.instructor.data.customer_id > 0 ? 'no' : 'yes'; },
                },
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'10':'Active', '90':'Archived'}},
            'webflags':{'label':'Web', 'type':'flags', 'join':'yes', 'toggle':'no', 'flags':{'1':{'name':'Hidden'}}},
            'url':{'label':'URL', 'type':'text'},
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'bio', 'tabs':{
            'bio':{'label':'Bio', 'fn':'M.ciniki_courses_main.instructor.switchTab("bio");'},
            'offerings':{'label':'Programs', 'fn':'M.ciniki_courses_main.instructor.switchTab("offerings");'},
            }},
        '_short_bio':{'label':'Short Bio', 'type':'simpleform', 
            'visible':function() { return M.ciniki_courses_main.instructor.sections._tabs.selected == 'bio' ? 'yes' : 'hidden';},
            'fields':{
                'short_bio':{'label':'', 'type':'textarea', 'size':'smallmedium', 'hidelabel':'yes'},
            }},
        '_full_bio':{'label':'Full Bio', 'type':'simpleform', 
            'visible':function() { return M.ciniki_courses_main.instructor.sections._tabs.selected == 'bio' ? 'yes' : 'hidden';},
            'fields':{
                'full_bio':{'label':'', 'type':'textarea', 'size':'xlarge', 'hidelabel':'yes'},
            }},
        'offerings':{'label':'Programs', 'type':'simplegrid', 'num_cols':7,
            'visible':function() { return M.ciniki_courses_main.instructor.sections._tabs.selected == 'offerings' ? 'yes' : 'hidden';},
            'headerValues':[],
            'noData':'No sessions',
            'sortable':'yes',
            'sortTypes':[],
            'dataMaps':[],
            },
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.instructor.save();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.ciniki_courses_main.instructor.instructor_id > 0 ? 'yes' : 'no';},
                'fn':'M.ciniki_courses_main.instructor.remove();',
                },
        }},
    }
    this.instructor.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.instructorHistory', 'args':{'tnid':M.curTenantID, 
            'instructor_id':this.instructor_id, 'field':i}};
    }
    this.instructor.addDropImage = function(iid) {
        M.ciniki_courses_main.instructor.setFieldValue('primary_image_id', iid, null, null);
        return true;
    }
    this.instructor.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    }
    this.instructor.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: return d.value;
            }
        }
        if( s == 'offerings' ) {
            if( this.sections.offerings.dataMaps[j] == 'registrations' ) {
                return d.num_registrations + '/' + d.num_seats;
            }
            return d[this.sections.offerings.dataMaps[j]];
        }
    }
    this.instructor.rowFn = function() {
        return '';
    }
    this.instructor.editCustomer = function() {
        this.save("M.startApp('ciniki.customers.edit',null,'M.ciniki_courses_main.instructor.open();','mc',{'next':'M.ciniki_courses_main.instructor.updateCustomer', 'action':'edit', 'customer_id':M.ciniki_courses_main.instructor.data.customer_id});");
    }
    this.instructor.changeCustomer = function() {
        this.save("M.startApp('ciniki.customers.edit',null,'M.ciniki_courses_main.instructor.open();','mc',{'next':'M.ciniki_courses_main.instructor.updateCustomer', 'action':'change', 'current_id':M.ciniki_courses_main.instructor.data.customer_id,'customer_id':0});");
    }
    this.instructor.updateCustomer = function(cid) {
        M.api.getJSONCb('ciniki.courses.instructorUpdate', {'tnid':M.curTenantID, 'instructor_id':this.instructor_id, 'customer_id':cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } 
                M.ciniki_courses_main.instructor.open();
            });
    }
    this.instructor.switchTab = function(t) {
        this.sections._tabs.selected = t;
        this.refreshSection('_tabs');
        this.showHideSections(['_short_bio', '_full_bio', 'offerings']);
    }
    this.instructor.open = function(cb, iid, form_sub_id) {
        if( iid != null ) { this.instructor_id = iid; }
        if( form_sub_id != null ) { this.form_submission_id = form_sub_id; }
        M.api.getJSONCb('ciniki.courses.instructorGet', {'tnid':M.curTenantID, 'instructor_id':this.instructor_id, 'form_submission_id':this.form_submission_id}, function (rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.instructor;
            p.data = rsp.instructor;
            if( rsp.instructor.customer_id > 0 ) {
                p.customer_id = rsp.instructor.customer_id;
                p.sections.customer_details.addTxt = 'Edit Customer';
                p.sections.customer_details.changeTxt = 'Change Customer';
            } else {
                p.customer_id = 0;
                p.sections.customer_details.addTxt = 'Add Customer';
                p.sections.customer_details.changeTxt = '';
            }
            p.refresh();
            p.show(cb);
            if( rsp.form_instructor != null ) {
                if( rsp.form_instructor.primary_image_id != null && rsp.form_instructor.primary_image_id > 0 ) {
                    p.setFieldValue('primary_image_id', rsp.form_instructor.primary_image_id);
                }
                if( rsp.form_instructor.short_bio != null && rsp.form_instructor.short_bio != '' ) {
                    p.setFieldValue('short_bio', rsp.form_instructor.short_bio);
                }
                if( rsp.form_instructor.full_bio != null && rsp.form_instructor.full_bio != '' ) {
                    p.setFieldValue('full_bio', rsp.form_instructor.full_bio);
                }
                if( rsp.form_instructor.url != null && rsp.form_instructor.url != '' ) {
                    p.setFieldValue('url', rsp.form_instructor.url);
                }
            }
        });
    }
    this.instructor.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_courses_main.instructor.close();'; }
        if( this.instructor_id > 0 ) {
            var c = this.serializeFormData('no');
            if( c != '' ) {
                M.api.postJSONFormData('ciniki.courses.instructorUpdate', 
                    {'tnid':M.curTenantID, 'instructor_id':this.instructor_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } 
                            eval(cb);
                        });
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONFormData('ciniki.courses.instructorAdd', {'tnid':M.curTenantID, 'customer_id':this.customer_id}, c,
                function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_courses_main.instructor.instructor_id = rsp.id;
                    eval(cb);
                });
        }
    };
    this.instructor.remove = function() {
        M.confirm('Are you sure you want to delete this instructor?',null,function() {
            M.api.getJSONCb('ciniki.courses.instructorDelete', {'tnid':M.curTenantID, 
                'instructor_id':M.ciniki_courses_main.instructor.instructor_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.instructor.close();
            });
        });
    };
    this.instructor.addButton('save', 'Save', 'M.ciniki_courses_main.instructor.save();');
    this.instructor.addClose('Cancel');

    //
    // The panel to display the course form
    //
    this.students = new M.panel('', 'ciniki_courses_main', 'students', 'mc', 'xlarge narrowaside', 'sectioned', 'ciniki.courses.main.students');
    this.students.data = {};  
    this.students.start_date = '';
    this.students.end_date = '';
    this.students.sections = {
        '_tabs':this.menutabs,
        'dates':{'label':'Date Range', 'aside':'yes', 'fields':{
            'start_date':{'label':'Start Date', 'type':'date', 'onchangeFn':'M.ciniki_courses_main.students.updateDate();'},
            'end_date':{'label':'End Date', 'type':'date', 'onchangeFn':'M.ciniki_courses_main.students.updateDate();'},
            }},
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'refresh':{'label':'Refresh', 'fn':'M.ciniki_courses_main.students.open();'},
            'download':{'label':'Download Excel', 'fn':'M.ciniki_courses_main.students.downloadExcel();'},
            }},
        'customers':{'label':'Students',
            'type':'simplegrid', 'num_cols':4,
            'headerValues':['Student', 'Program', 'Date', 'Price'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'number'],
            'cellClasses':['', '', '', ''],
            }
    }
    this.students.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.display_name;
            case 1: return d.course_name;
            case 2: return d.condensed_date;
            case 3: return d.price_name;
        }
    }
    this.students.fieldValue = function(s, i, d) {
        if( i == 'start_date' ) { return this.start_date; }
        if( i == 'end_date' ) { return this.end_date; }
    }
    this.students.updateDate = function() {
        this.start_date = this.formValue('start_date');
        this.end_date = this.formValue('end_date');
    }
    this.students.open = function(cb) {
        if( this.start_date != '' ) {
            M.api.getJSONCb('ciniki.courses.reportStudents', {'tnid':M.curTenantID, 'start_date':this.start_date, 'end_date':this.end_date}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_courses_main.students;
                p.data = rsp;
                p.refresh();
                p.show(cb);
            });
        } else {
            this.data = {};
            this.refresh();
            this.show(cb);
        }
    }
    this.students.downloadExcel = function() {
        M.api.openFile('ciniki.courses.reportStudents', {'tnid':M.curTenantID, 'start_date':this.start_date, 'end_date':this.end_date, 'output':'excel'});
    }
    this.students.addClose('Back');

    //
    // The panel to edit Session Notification
    //
    this.notification = new M.panel('Session Notification', 'ciniki_courses_main', 'notification', 'mc', 'medium mediumaside', 'sectioned', 'ciniki.courses.main.notification');
    this.notification.data = null;
    this.notification.notification_id = 0;
    this.notification.offering_id = 0;
    this.notification.nplist = [];
    this.notification.sections = {
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'ntrigger':{'label':'Trigger', 'required':'yes', 'type':'select', 'options':{
                '20':'Payment Received',
                '60':'Session Start',
                '80':'Session End',
                '90':'Each Class',
                '94':'First Class',
                '95':'Last Class',
                '96':'Other Classes (not first or last)',
                },
                'onchange':'M.ciniki_courses_main.notification.updateForm',
                },
//            'ntype':{'label':'Notification Type', 'type':'toggle', 'toggles':{'10':'Email'}},
            'offset_days':{'label':'Offset Days', 'type':'text', 'visible':'yes', 'size':'small'},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'0':'Inactive', '10':'Require Approval', '20':'Auto Send'}},
            'time_of_day':{'label':'Time of Day', 'visible':'yes', 'type':'text', 'size':'small'},
            }},
//        '_form':{'label':'Attach Form', 'fields':{
//            'form_id':{'label':'Form', 'hidelabel':'yes', 'type':'select', 'options':{}},
//            }},
        '_subject':{'label':'Subject', 'fields':{
            'subject':{'label':'Subject', 'hidelabel':'yes', 'type':'text'},
            }},
        '_content':{'label':'Content', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_main.notification.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_courses_main.notification.notification_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_courses_main.notification.remove();'},
            }},
        };
    this.notification.fieldValue = function(s, i, d) { return this.data[i]; }
    this.notification.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.offeringNotificationHistory', 'args':{'tnid':M.curTenantID, 'notification_id':this.notification_id, 'field':i}};
    }
    this.notification.updateForm = function() {
        if( this.formValue('ntrigger') == '20' ) {
            this.sections.general.fields.offset_days.visible = 'no';
            this.sections.general.fields.time_of_day.visible = 'no';
        } else {
            this.sections.general.fields.offset_days.visible = 'yes';
            this.sections.general.fields.time_of_day.visible = 'yes';
        }
        this.showHideFormField('general', 'offset_days');
        this.showHideFormField('general', 'time_of_day');
    }
    this.notification.open = function(cb, nid, oid, list) {
        if( nid != null ) { this.notification_id = nid; }
        if( oid != null ) { this.offering_id = oid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.courses.offeringNotificationGet', {'tnid':M.curTenantID, 'notification_id':this.notification_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_main.notification;
            p.data = rsp.notification;
            p.refresh();
            p.show(cb);
            p.updateForm();
        });
    }
    this.notification.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_courses_main.notification.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.notification_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.courses.offeringNotificationUpdate', {'tnid':M.curTenantID, 'notification_id':this.notification_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.courses.offeringNotificationAdd', {'tnid':M.curTenantID, 'offering_id':this.offering_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.notification.notification_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.notification.remove = function() {
        if( confirm('Are you sure you want to remove offering notification?') ) {
            M.api.getJSONCb('ciniki.courses.offeringNotificationDelete', {'tnid':M.curTenantID, 'notification_id':this.notification_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_main.notification.close();
            });
        }
    }
    this.notification.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.notification_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_courses_main.notification.save(\'M.ciniki_courses_main.notification.open(null,' + this.nplist[this.nplist.indexOf('' + this.notification_id) + 1] + ');\');';
        }
        return null;
    }
    this.notification.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.notification_id) > 0 ) {
            return 'M.ciniki_courses_main.notification.save(\'M.ciniki_courses_main.notification.open(null,' + this.nplist[this.nplist.indexOf('' + this.notification_id) - 1] + ');\');';
        }
        return null;
    }
    this.notification.helpSections = function() {
        return {
            'help':{'label':'Substitutions', 'type':'htmlcontent',
                'html':'The following substitutions are available in the subject and content:<br/><br/>'
                    + '{_firstname_} = Customer or Student first name<br/>'
                    + '{_lastname_} = Customer or Student last name<br/>'
//                    + '{_formlink_} = Link to Form below<br/>'
                    },
            };
    }
    this.notification.addButton('save', 'Save', 'M.ciniki_courses_main.notification.save();');
    this.notification.addClose('Cancel');
    this.notification.addButton('next', 'Next');
    this.notification.addLeftButton('prev', 'Prev');

    //
    // Start the app
    // cb - The callback to run when the user leaves the main panel in the app.
    // ap - The application prefix.
    // ag - The app arguments.
    //
    this.start = function(cb, ap, ag) {
        args = {};
        if( ag != null ) {
            args = eval(ag);
        }
        
        //
        // Create the app container
        //
        var ac = M.createContainer(ap, 'ciniki_courses_main', 'yes');
        if( ac == null ) {
            alert('App Error');
            return false;
        }

        //
        // Initialize for tenant
        //
        if( this.curTenantID == null || this.curTenantID != M.curTenantID ) {
            this.tenantInit();
            this.curTenantID = M.curTenantID;
        }

        //
        // Set callbacks for each panel so back goes back to main menu
        //
        this.offerings.cb = cb;
        this.courses.cb = cb;
        this.instructors.cb = cb;
        this.students.cb = cb;

        if( args.instructor_id != null && args.form_submission_id != null && args.form_submission_id > 0 
            ) {
            this.instructor.open(cb,args.instructor_id,args.form_submission_id);
        } else if( args.form_submission_id != null && args.form_submission_id > 0 ) {
            this.course.open(cb,0,null,args.form_submission_id);
        } else if( this[this.menutabs.selected] == null ) {
            this.courses.open(cb);
        } else {
            this[this.menutabs.selected].open(cb);
        }
    }

    this.tenantInit = function() {
        //
        // Reset filters
        //
        this.offerings.sections.statuses.selected = '10';
        this.offerings.sections.years.selected = 'all';
        this.offering.sections._tabs.selected = 'registrations';
        this.courses.sections.statuses.selected = '30';
        this.courses.sections.levels.selected = '__';
        this.courses.sections.types.selected = '__';
        this.courses.sections.categories.selected = '__';
        this.courses.sections.mediums.selected = '__';
        this.courses.sections.ages.selected = '__';

        //
        // Check if tabs need reset after switch business
        //
        M.ciniki_courses_main.course.sections._tabs.selected = 'info';
        M.ciniki_courses_main.course.sections._tabs.selected = 'info';

        //
        // Setup the course and offering lists based on fields enabled
        //
        this.courses.sections.courses.dataMaps = [];
        this.courses.sections.courses.headerValues = [];
        this.courses.sections.courses.sortTypes = [];
        this.course.sections.offerings.dataMaps = [];
        this.course.sections.offerings.headerValues = [];
        this.course.sections.offerings.sortTypes = [];
        this.course.sections.offerings.cellClasses = [];
        this.offerings.sections.offerings.dataMaps = [];
        this.offerings.sections.offerings.headerValues = [];
        this.offerings.sections.offerings.sortTypes = [];
        this.instructor.sections.offerings.dataMaps = [];
        this.instructor.sections.offerings.headerValues = [];
        this.instructor.sections.offerings.sortTypes = [];
/*        if( M.modFlagOn('ciniki.courses', 0x01) ) {
            this.courses.sections.courses.dataMaps.push('course_code');
            this.courses.sections.courses.headerValues.push('Code');
            this.courses.sections.courses.sortTypes.push('text');
            this.instructor.sections.offerings.dataMaps.push('course_code');
            this.instructor.sections.offerings.headerValues.push('Code');
            this.instructor.sections.offerings.sortTypes.push('text');
        }
*/
        this.courses.sections.courses.dataMaps.push('course_name');
        this.courses.sections.courses.headerValues.push('Name');
        this.courses.sections.courses.sortTypes.push('text');
        this.offerings.sections.offerings.dataMaps.push('course_name');
        this.offerings.sections.offerings.headerValues.push('Name');
        this.offerings.sections.offerings.sortTypes.push('text');
        this.instructor.sections.offerings.dataMaps.push('course_name');
        this.instructor.sections.offerings.headerValues.push('Program');
        this.instructor.sections.offerings.sortTypes.push('text');
        if( M.modFlagOn('ciniki.courses', 0x20) ) {
            this.course.sections.offerings.dataMaps.push('offering_code');
            this.course.sections.offerings.headerValues.push('Code');
            this.course.sections.offerings.sortTypes.push('text');
            this.course.sections.offerings.cellClasses.push('');
            this.offerings.sections.offerings.dataMaps.push('offering_code');
            this.offerings.sections.offerings.headerValues.push('Code');
            this.offerings.sections.offerings.sortTypes.push('text');
            this.instructor.sections.offerings.dataMaps.push('offering_code');
            this.instructor.sections.offerings.headerValues.push('Code');
            this.instructor.sections.offerings.sortTypes.push('text');
        }
        this.course.sections.offerings.dataMaps.push('offering_name');
        this.course.sections.offerings.headerValues.push('Session');
        this.course.sections.offerings.sortTypes.push('text');
        this.course.sections.offerings.cellClasses.push('');
        this.offerings.sections.offerings.dataMaps.push('offering_name');
        this.offerings.sections.offerings.headerValues.push('Session');
        this.offerings.sections.offerings.sortTypes.push('text');
        this.instructor.sections.offerings.dataMaps.push('offering_name');
        this.instructor.sections.offerings.headerValues.push('Session');
        this.instructor.sections.offerings.sortTypes.push('text');
        this.courses.sections.courses.dataMaps.push('status_text');
        this.courses.sections.courses.headerValues.push('Status');
        this.courses.sections.courses.sortTypes.push('text');
        this.offerings.sections.offerings.dataMaps.push('status_text');
        this.offerings.sections.offerings.headerValues.push('Status');
        this.offerings.sections.offerings.sortTypes.push('text');
        this.courses.sections.courses.dataMaps.push('level');
        this.courses.sections.courses.headerValues.push('Level');
        this.courses.sections.courses.sortTypes.push('text');
        if( M.modFlagOn('ciniki.courses', 0x10) ) {
            this.courses.sections.courses.dataMaps.push('type');
            this.courses.sections.courses.headerValues.push('Type');
            this.courses.sections.courses.sortTypes.push('text');
        }
        this.courses.sections.courses.dataMaps.push('category');
        this.courses.sections.courses.headerValues.push('Category');
        this.courses.sections.courses.sortTypes.push('text');
        if( M.modFlagOn('ciniki.courses', 0x1000) ) {
            this.courses.sections.courses.dataMaps.push('medium');
            this.courses.sections.courses.headerValues.push('Medium');
            this.courses.sections.courses.sortTypes.push('text');
        }
        if( M.modFlagOn('ciniki.courses', 0x2000) ) {
            this.courses.sections.courses.dataMaps.push('ages');
            this.courses.sections.courses.headerValues.push('Ages');
            this.courses.sections.courses.sortTypes.push('text');
        }
        this.courses.sections.courses.dataMaps.push('start_date');
        this.courses.sections.courses.headerValues.push('Start');
        this.courses.sections.courses.sortTypes.push('date');
        this.course.sections.offerings.dataMaps.push('start_date');
        this.course.sections.offerings.headerValues.push('Start');
        this.course.sections.offerings.sortTypes.push('date');
        this.course.sections.offerings.cellClasses.push('');
        this.offerings.sections.offerings.dataMaps.push('start_date');
        this.offerings.sections.offerings.headerValues.push('Start');
        this.offerings.sections.offerings.sortTypes.push('date');
        this.instructor.sections.offerings.dataMaps.push('start_date');
        this.instructor.sections.offerings.headerValues.push('Start');
        this.instructor.sections.offerings.sortTypes.push('date');
        this.courses.sections.courses.dataMaps.push('end_date');
        this.courses.sections.courses.headerValues.push('End');
        this.courses.sections.courses.sortTypes.push('date');
        this.course.sections.offerings.dataMaps.push('end_date');
        this.course.sections.offerings.headerValues.push('End');
        this.course.sections.offerings.sortTypes.push('date');
        this.course.sections.offerings.cellClasses.push('');
        this.offerings.sections.offerings.dataMaps.push('end_date');
        this.offerings.sections.offerings.headerValues.push('End');
        this.offerings.sections.offerings.sortTypes.push('date');
        this.course.sections.offerings.dataMaps.push('registrations');
        this.course.sections.offerings.headerValues.push('Registrations');
        this.course.sections.offerings.sortTypes.push('number');
        this.course.sections.offerings.cellClasses.push('');
        this.course.sections.offerings.dataMaps.push('actions');
        this.course.sections.offerings.headerValues.push('');
        this.course.sections.offerings.sortTypes.push('');
        this.course.sections.offerings.cellClasses.push('fabuttons');

        this.offerings.sections.offerings.dataMaps.push('registrations');
        this.offerings.sections.offerings.headerValues.push('Registrations');
        this.offerings.sections.offerings.sortTypes.push('number');

        this.instructor.sections.offerings.dataMaps.push('end_date');
        this.instructor.sections.offerings.headerValues.push('End');
        this.instructor.sections.offerings.sortTypes.push('date');
        this.instructor.sections.offerings.dataMaps.push('registrations');
        this.instructor.sections.offerings.headerValues.push('Registrations');
        this.instructor.sections.offerings.sortTypes.push('number');

        this.courses.sections.courses.num_cols = this.courses.sections.courses.dataMaps.length;
        this.course.sections.offerings.num_cols = this.course.sections.offerings.dataMaps.length;
        this.offerings.sections.offerings.num_cols = this.offerings.sections.offerings.dataMaps.length;
        this.instructor.sections.offerings.num_cols = this.instructor.sections.offerings.dataMaps.length;

        this.course.sections.archived.headerValues = this.course.sections.offerings.headerValues;
        this.course.sections.archived.dataMaps = this.course.sections.offerings.dataMaps;
        this.course.sections.archived.cellClasses = this.course.sections.offerings.cellClasses;
        this.course.sections.archived.num_cols = this.course.sections.archived.dataMaps.length;

        this.courses.sections.search.livesearchcols = this.courses.sections.courses.num_cols;
        this.courses.sections.search.headerValues = this.courses.sections.courses.headerValues;
        this.courses.sections.search.cellClasses = this.courses.sections.courses.cellClasses;
        this.courses.sections.search.dataMaps = this.courses.sections.courses.dataMaps;

        this.offerings.sections.search.livesearchcols = this.offerings.sections.offerings.num_cols;
        this.offerings.sections.search.headerValues = this.offerings.sections.offerings.headerValues;
        this.offerings.sections.search.cellClasses = this.offerings.sections.offerings.cellClasses;
        this.offerings.sections.search.dataMaps = this.offerings.sections.offerings.dataMaps;

        //
        // Setup paid content flags for images and files
        //
        this.courseimage.sections.general.fields.flags.flags = {'1':{'name':'Visible'}};
        this.offeringimage.sections.general.fields.flags.flags = {'1':{'name':'Visible'}};
        this.coursefile.sections.info.fields.webflags.flags = {'1':{'name':'Visible'}};
        this.offeringfile.sections.info.fields.webflags.flags = {'1':{'name':'Visible'}};
        this.course.sections.files.num_cols = 2;
        this.offering.sections.files.num_cols = 2;
        if( M.modFlagOn('ciniki.courses', 0x040000) ) {
            this.course.sections.files.num_cols = 3;
            this.offering.sections.files.num_cols = 3;
            this.courseimage.sections.general.fields.flags.flags['5'] = {'name':'Paid Content'};
            this.offeringimage.sections.general.fields.flags.flags['5'] = {'name':'Paid Content'};
            this.coursefile.sections.info.fields.webflags.flags['5'] = {'name':'Paid Content'};
            this.offeringfile.sections.info.fields.webflags.flags['5'] = {'name':'Paid Content'};
        }

        //
        // Setup the tax types
        //
        if( M.curTenant.modules['ciniki.taxes'] != null ) {
            this.price.sections.price.fields.taxtype_id.active = 'yes';
            this.price.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
            if( M.curTenant.taxes != null && M.curTenant.taxes.settings.types != null ) {
                for(i in M.curTenant.taxes.settings.types) {
                    this.price.sections.price.fields.taxtype_id.options[M.curTenant.taxes.settings.types[i].type.id] = M.curTenant.taxes.settings.types[i].type.name;
                }
            }
        } else {
            this.price.sections.price.fields.taxtype_id.active = 'no';
            this.price.sections.price.fields.taxtype_id.options = {'0':'No Taxes'};
        }

        //
        // Setup the available_to flags and webflags
        //
        this.price.sections.price.fields.available_to.flags = {'1':{'name':'Public'}};
        this.price.sections.price.fields.webflags.flags = {'1':{'name':'Hidden'}};
        if( (M.curTenant.modules['ciniki.customers'].flags&0x02) > 0 ) {
            this.price.sections.price.fields.available_to.flags['6'] = {'name':'Members'};
            this.price.sections.price.fields.webflags.flags['6'] = {'name':'Show Members Price'};
        }
        //
        // Setup registrations list on offering
        //
        if( M.modOn('ciniki.sapos') ) {
            this.offering.sections.registrations.num_cols = 6;
            if( M.modOn('ciniki.forms') ) {
                this.offering.sections.registrations.num_cols = 7;
            }
        } else {
            this.offering.sections.registrations.num_cols = 3;
        }
    }
}










