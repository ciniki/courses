//
// The course offerings app to manage courses offering by a tenant
//
function ciniki_courses_offerings() {
    this.statusToggles = {'10':'Active', '60':'Deleted'};
    this.webFlags = {'1':{'name':'Hidden'}};
    this.regFlags = {
        '1':{'name':'Track Registrations'},
        '2':{'name':'Online Registrations'},
        };
    //
    // Setup the main panel to list the offerings.  The current offerings
    // are between the first class start date and last class end date. 
    //
    this.menu = new M.panel('Course Offerings',
        'ciniki_courses_offerings', 'menu',
        'mc', 'medium narrowaside', 'sectioned', 'ciniki.courses.offerings.menu');
    this.menu.data = {};
    this.menu.sections = {
        'other':{'label':'Options', 'aside':'yes', 'list':{
            'instructors':{'label':'Instructors', 'visible':'no', 
                'fn':'M.startApp(\'ciniki.courses.instructors\',null,\'M.ciniki_courses_offerings.menu.open();\');',
                },
            'registration':{'label':'Registration Information', 'visible':'yes', 
                'fn':'M.startApp(\'ciniki.courses.info\',null,\'M.ciniki_courses_offerings.menu.open();\',\'mc\',{\'showregistration\':\'yes\'});',
                },
            'coursecalendar':{'label':'Course Calendar', 'visible':'yes', 
                'fn':'M.startApp(\'ciniki.courses.files\',null,\'M.ciniki_courses_offerings.menu.open();\',\'mc\',{\'add\':\'yes\',\'type\':2});',
                },
            'albums':{'label':'Photos', 
                'visible':function() { return M.modFlagSet('ciniki.courses', 0x0100); },
                'fn':'M.startApp(\'ciniki.courses.albums\',null,\'M.ciniki_courses_offerings.menu.open();\',\'mc\',{\'course_id\':0,\'offering_id\':0});',
                },
            }},
        '_tabs':{'label':'', 'type':'paneltabs', 'selected':'current', 'tabs':{
            'current':{'label':'Current', 'fn':'M.ciniki_courses_offerings.menu.switchTab("current");'},
            'upcoming':{'label':'Upcoming', 'fn':'M.ciniki_courses_offerings.menu.switchTab("upcoming");'},
            'past':{'label':'Past', 'fn':'M.ciniki_courses_offerings.menu.switchTab("past");'},
            }},
        'current':{'label':'Current Offerings', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_courses_offerings.menu.sections._tabs.selected == 'current' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['multiline', 'alignright'],
            'noData':'No current offerings',
            },
        'upcoming':{'label':'Upcoming Offerings', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_courses_offerings.menu.sections._tabs.selected == 'upcoming' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['multiline', 'alignright'],
            'noData':'No upcoming offerings',
            'addTxt':'Add Offering',
            'addFn':'M.ciniki_courses_offerings.showEdit(\'M.ciniki_courses_offerings.menu.open();\',0,0);',
            },
        '_pasttabs':{'label':'', 'type':'paneltabs', 'selected':'', 
            'visible':function() { return (M.ciniki_courses_offerings.menu.sections._tabs.selected == 'past' ? 'yes' : 'hidden'); },
            'tabs':{},
            },
        'past':{'label':'Previous Offerings', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return (M.ciniki_courses_offerings.menu.sections._tabs.selected == 'past' ? 'yes' : 'hidden'); },
            'headerValues':null,
            'cellClasses':['multiline', 'alignright'],
            'noData':'No previous offerings',
            },
        };
    this.menu.sectionData = function(s) { 
        if( s == 'other' ) { return this.sections[s].list; }
        if( s == 'past' ) { return this.data.pastyears[this.sections._pasttabs.selected]; }
        return this.data[s]; 
    };
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.cellValue = function(s, i, j, d) {
        if( j == 0 ) {
            var odate = '';
            if( d.start_date != null ) {
                if( d.end_date != null && d.start_date != d.end_date ) {
                    odate = d.start_date + ' - ' + d.end_date;
                } else {
                    odate = d.start_date;
                }
            }
            var name = d.course_name;
            if( d.code != '' && M.curTenant.modules['ciniki.courses'].flags != null && M.modFlagOn('ciniki.courses', 0x01) ) { name = d.code + ' - ' + name; }
            if( d.offering_code != '' && M.curTenant.modules['ciniki.courses'].flags != null && M.modFlagOn('ciniki.courses', 0x20) ) { name = d.offering_code + ' - ' + name; }
            if( d.offering_name != '' ) { name += ' <span class="subdue">' + d.offering_name + '</span>'; }
            return '<span class="maintext">' + name + '</span><span class="subtext">' + odate + '</span>';
        }
        if( j == 1 ) {
            return d.num_registrations + '/' + d.num_seats;
        }
    };
    this.menu.rowFn = function(s, i, d) { 
        if( s == 'other' && i == 'instructors' ) {
            return d.fn;
        }
        return 'M.ciniki_courses_offerings.showOffering(\'M.ciniki_courses_offerings.menu.open();\',\'' + d.id + '\');'; 
    };
    this.menu.switchTab = function(tab, stab) {
        this.sections._tabs.selected = tab;
        if( tab == 'past' && stab != null ) {
            this.sections._pasttabs.selected = stab;
        }
        this.refreshSection('_tabs');
        this.showHideSection('current');
        this.showHideSection('upcoming');
        this.refreshSection('_pasttabs');
        this.refreshSection('past');
    }
    this.menu.open = function(cb) {
        // Get the list of existing offerings
        M.api.getJSONCb('ciniki.courses.offeringList', {'tnid':M.curTenantID, 'upcoming':'yes', 'current':'yes', 'past':'yes', 'files':'yes', 'limit':'26'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_offerings.menu;
            p.data = rsp;
            if( M.curTenant.modules['ciniki.courses'].flags != null && (M.curTenant.modules['ciniki.courses'].flags&0x02) == 0x02) { 
                p.sections.other.list.instructors.visible = 'yes';
                p.sections.other.list.coursecalendar.visible = 'yes';
                // Check for a course catalog file
                if( rsp.files != null && rsp.files.length > 0 ) {
                    p.sections.other.list.coursecalendar.fn = 'M.startApp(\'ciniki.courses.files\',null,\'M.ciniki_courses_offerings.menu.open();\',\'mc\',{\'file_id\':\'' + rsp.files[0].id + '\'});';
                } else {
                    p.sections.other.list.coursecalendar.fn = 'M.startApp(\'ciniki.courses.files\',null,\'M.ciniki_courses_offerings.menu.open();\',\'mc\',{\'add\':\'yes\',\'type\':2});';
                }
            } else {
                p.sections.other.list.instructors.visible = 'no';
                p.sections.other.list.coursecalendar.visible = 'no';
            }
            p.sections._pasttabs.tabs = {};
            for(var i in rsp.pastyears) {
                p.sections._pasttabs.tabs[i] = {'label':i, 'fn':'M.ciniki_courses_offerings.menu.switchTab("past", "' + i + '");'};
                p.sections._pasttabs.selected = i;
            }
            p.refresh();
            p.show(cb);
        });
    };
    this.menu.addButton('add', 'Add', 'M.ciniki_courses_offerings.showEdit(\'M.ciniki_courses_offerings.menu.open();\',0,0);');
    this.menu.addClose('Back');

    //
    // The offering panel will show the information for a course offering.  Courses
    // can be offered multiple times.
    //
    this.offering = new M.panel('Course Offering',
        'ciniki_courses_offerings', 'offering',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.courses.offerings.offering');
    this.offering.data = {};
    this.offering.offering_id = 0;
    this.offering.course_id = 0;
    this.offering.sections = {
        '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
            }},
        'course':{'label':'Course', 'aside':'yes', 'list':{
            'course_name':{'label':'Name', 'visible':'yes'},
            'status_text':{'label':'Status'},
            'level':{'label':'Level', 'visible':'yes'},
            'type':{'label':'Type', 'visible':'yes'},
            'category':{'label':'Category', 'visible':'yes'},
            'web_visible':{'label':'Website'},
            }},
        '_registrations':{'label':'', 'aside':'yes', 'hidelabel':'yes', 'visible':'no', 'list':{
            'registrations':{'label':'Seats'},
            }},
        'short_description':{'label':'Synopsis', 'type':'htmlcontent'},
        'long_description':{'label':'Description', 'type':'htmlcontent'},
        'classes':{'label':'Classes', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':[''],
            'noData':'No classes added',
            'addTxt':'Add Class',
            'addFn':'M.startApp(\'ciniki.courses.classes\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'offering_id\':M.ciniki_courses_offerings.offering.offering_id,\'course_id\':M.ciniki_courses_offerings.offering.course_id,\'add\':\'yes\'});',
            },
        'prices':{'label':'Prices', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['',''],
            'noData':'No prices',
            'addTxt':'Add Price',
            'addFn':'M.startApp(\'ciniki.courses.prices\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'offering_id\':M.ciniki_courses_offerings.offering.offering_id,\'course_id\':M.ciniki_courses_offerings.offering.course_id,\'price_id\':\'0\'});',
            },
        'instructors':{'label':'Instructors', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline'],
            'noData':'No instructors added',
            'addTxt':'Add Instructor',
            'addFn':'M.startApp(\'ciniki.courses.instructors\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'offering_id\':M.ciniki_courses_offerings.offering.offering_id,\'price_id\':\'0\',\'add\':\'yes\'});',
            },
        'files':{'label':'Files', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
            'headerValues':null,
            'cellClasses':['multiline'],
            'noData':'No files added',
            'addTxt':'Add File',
            'addFn':'M.startApp(\'ciniki.courses.files\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'offering_id\':M.ciniki_courses_offerings.offering.offering_id,\'course_id\':M.ciniki_courses_offerings.offering.course_id,\'add\':\'yes\'});',
            },
//          'customers':{'label':'Students', 'visible':'no', 'type':'simplegrid', 'num_cols':1,
//              'headerValues':null,
//              'cellClasses':['multiline'],
//              'noData':'No students added',
//              'addTxt':'Add Student',
//              'addFn':'M.startApp(\'ciniki.courses.customers\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'offering_id\':M.ciniki_courses_offerings.offering.offering_id,\'course_id\':M.ciniki_courses_offerings.offering.course_id,\'add\':\'yes\'});',
//              },
        '_buttons':{'label':'', 'buttons':{
            'edit':{'label':'Edit', 'fn':'M.ciniki_courses_offerings.showEdit(\'M.ciniki_courses_offerings.showOffering();\',M.ciniki_courses_offerings.offering.offering_id,M.ciniki_courses_offerings.offering.course_id);'},
//              'delete':{'label':'Delete', 'fn':'M.ciniki_artcatalog_main.deletePiece();'},
            }},
    };
    this.offering.sectionData = function(s) {
        if( s == 'short_description' || s == 'long_description' ) { 
            return this.data[s].replace(/\n/g, '<br/>');
        }
        if( s == 'offering' || s == 'course' || s == '_registrations' ) { return this.sections[s].list; }
        return this.data[s];
        };
    this.offering.listLabel = function(s, i, d) {
        if( s == 'offering' || s == 'course' || s == '_registrations' ) { 
            return d.label; 
        }
        return null;
    };
    this.offering.listValue = function(s, i, d) {
        if( i == 'registrations' ) {
            return this.data['seats_sold'] + ' of ' + this.data['num_seats'] + ' sold';
        }
        if( i == 'course_name' ) {
            var name = this.data['course_name'];
            if( this.data['offering_code'] != null && this.data['offering_code'] != '' 
                && M.curTenant.modules['ciniki.courses'].flags != null 
                && (M.curTenant.modules['ciniki.courses'].flags&0x01) == 0x01) { 
                name = this.data['code'] + ' - ' + name;
            } else if( this.data['code'] != null && this.data['code'] != '' 
                && M.curTenant.modules['ciniki.courses'].flags != null 
                && (M.curTenant.modules['ciniki.courses'].flags&0x01) == 0x01) { 
                name = this.data['code'] + ' - ' + name;
            }   
            if( this.data['offering_name'] != null && this.data['offering_name'] != '' ) {
                return name + ' <span class="subdue">' + this.data['offering_name'] + '</span>';
            } 
            return name;
        }
        if( i == 'status' ) { return M.ciniki_courses_offerings.statusToggles[i]; }
        return this.data[i];
    };
    this.offering.listFn = function(s, i, d) {
        if( i == 'registrations' ) {
            return 'M.startApp(\'ciniki.courses.registrations\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'offering_id\':\'' + M.ciniki_courses_offerings.offering.offering_id + '\'});';
        }
        return null;
    };
    this.offering.fieldValue = function(s, i, d) {
        return this.data[i];
    };
    this.offering.cellValue = function(s, i, j, d) {
        if( s == 'classes' && j == 0 ) { 
            return '<span class="maintext">' + d.class.class_date + '</span><span class="subdue"> ' + d.class.start_time + ' - ' + d.class.end_time + '</span>';
        }
        if( s == 'prices' ) {
            switch(j) {
                case 0: return d.price.name + (d.price.available_to_text!=''?' <span class="subdue">(' + d.price.available_to_text + ')</span>':'');
                case 1: return d.price.unit_amount_display;
            }
        }
        if( s == 'instructors' && j == 0 ) { 
            return '<span class="maintext">' + d.instructor.name + '</span>';
        }
        if( s == 'files' && j == 0 ) { 
            return '<span class="maintext">' + d.file.name + '</span>';
        }
//          else if( s == 'customers' && j == 0 ) { 
//              return '<span class="maintext">' + d.customer.name + '</span>';
//          }
    };
    this.offering.rowFn = function(s, i, d) {
        if( s == 'classes' ) {
            return 'M.startApp(\'ciniki.courses.classes\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'class_id\':\'' + d.class.id + '\'});';
        }
        if( s == 'prices' ) {
            return 'M.startApp(\'ciniki.courses.prices\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'price_id\':\'' + d.price.id + '\'});';
        }
        if( s == 'instructors' ) {
            return 'M.startApp(\'ciniki.courses.instructors\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'offering_instructor_id\':\'' + d.instructor.id + '\'});';
        }
        if( s == 'files' ) {
            return 'M.startApp(\'ciniki.courses.files\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'file_id\':\'' + d.file.file_id + '\',\'offering_file_id\':\'' + d.file.id + '\'});';
        }
        if( s == 'customers' ) {
            return 'M.startApp(\'ciniki.courses.customers\',null,\'M.ciniki_courses_offerings.showOffering();\',\'mc\',{\'offering_customer_id\':\'' + d.customer.id + '\'});';
        }
    };
    this.offering.addButton('edit', 'Edit', 'M.ciniki_courses_offerings.showEdit(\'M.ciniki_courses_offerings.showOffering();\',M.ciniki_courses_offerings.offering.offering_id,0);');
    this.offering.addClose('Back');

    //
    // The edit panel for course offering
    //
    this.edit = new M.panel('Edit',
        'ciniki_courses_offerings', 'edit',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.courses.offerings.edit');
    this.edit.data = {};
    this.edit.offering_id = 0;
    this.edit.course_id = 0;
    this.edit.sections = {
        '_image':{'label':'', 'aside':'yes', 'type':'imageform', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
        }},
        'course':{'label':'Course', 'aside':'yes', 'fields':{
            'course_name':{'label':'Name', 'type':'text', 'livesearch':'yes'},
            'code':{'label':'Code', 'type':'text', 
                'active':function() { return M.modFlagSet('ciniki.courses', 0x20); },
                'livesearch':'yes'},
            'level':{'label':'Level', 'type':'text'},
            'type':{'label':'Type', 'active':'no', 'type':'text', 'livesearch':'yes', 'livesearchempty':'no'},
            'flags':{'label':'Options', 'type':'flags', 'flags':{'1':{'name':'Children'}}},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            }},
        'offering':{'label':'Session', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'code':{'label':'Code', 'type':'text', 
                'active':function() { return M.modFlagSet('ciniki.courses', 0x20); },
                'livesearch':'yes'},
            'status':{'label':'Status', 'type':'toggle', 'default':'10', 'toggles':this.statusToggles},
            'webflags':{'label':'Web', 'type':'flags', 'toggle':'no', 'join':'yes', 'flags':this.webFlags},
            'class_date':{'label':'Date', 'active':'no', 'type':'date', 'size':'small'},
            'start_time':{'label':'Start', 'active':'no', 'type':'text', 'size':'small'},
            'end_time':{'label':'End', 'active':'no', 'type':'text', 'size':'small'},
            'num_weeks':{'label':'Weeks', 'active':'no', 'type':'text', 'size':'small'},
            }},
        '_registrations':{'label':'Registrations', 'visible':'no', 'fields':{
            'reg_flags':{'label':'Options', 'active':'no', 'type':'flags', 'joined':'no', 'flags':this.regFlags},
            'num_seats':{'label':'Number of Seats', 'active':'no', 'type':'text', 'size':'small'},
            }},
        '_short_description':{'label':'Synopsis', 'fields':{
            'short_description':{'label':'', 'hidelabel':'yes', 'size':'small', 'type':'textarea'},
            }},
        '_long_description':{'label':'Description', 'fields':{
            'long_description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_offerings.saveCourse();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_courses_offerings.deleteOffering();'},
            }},
    };
    this.edit.fieldValue = function(s, i, d) {
        if( s == 'offering' && i == 'name' ) { return this.data['offering_name']; }
        if( s == 'offering' && i == 'code' ) { return this.data['offering_code']; }
        if( this.data[i] != null ) { return this.data[i]; }
        return '';
    };
    this.edit.liveSearchCb = function(s, i, value) {
        if( i == 'course_name' || (s == 'course' && i == 'code') ) {
            var rsp = M.api.getJSONBgCb('ciniki.courses.courseSearch', 
                {'tnid':M.curTenantID, 
                'offering_id':M.ciniki_courses_offerings.edit.offering_id, 
                'start_needle':value, 'limit':25},
                function(rsp) { 
                    M.ciniki_courses_offerings.edit.liveSearchShow(s, i, M.gE(M.ciniki_courses_offerings.edit.panelUID + '_' + i), rsp.courses); 
                });
        }
        if( i == 'type' || i == 'category' ) {
            var rsp = M.api.getJSONBgCb('ciniki.courses.courseSearchField', 
                {'tnid':M.curTenantID, 
                'start_needle':value, 'field':i, 'limit':25},
                function(rsp) { 
                    M.ciniki_courses_offerings.edit.liveSearchShow(s, i, M.gE(M.ciniki_courses_offerings.edit.panelUID + '_' + i), rsp.results); 
                });
        }
        if( i == 'name' ) {
            var rsp = M.api.getJSONBgCb('ciniki.courses.offeringSearchName', 
                {'tnid':M.curTenantID, 
                'start_needle':value, 'limit':25},
                function(rsp) { 
                    M.ciniki_courses_offerings.edit.liveSearchShow(s, i, M.gE(M.ciniki_courses_offerings.edit.panelUID + '_' + i), rsp.names); 
                });
        }
    };
    this.edit.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'course_name' || f == 'code' ) { 
            if( d.course.code != '' ) { return d.course.code + ' - ' + d.course.name; }
            return d.course.name;
        }
        if( f == 'type' || f == 'category' ) {
            return d.result.name;
        }
        if( f == 'name' ) {
            return d.name.name;
        }
        return '';
    };
    this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( f == 'course_name' || f == 'code' ) {
            return 'M.ciniki_courses_offerings.edit.updateCourse(\'' + s + '\',\'' + d.course.id + '\');';
        }
        if( f == 'type' || f == 'category' ) {
            return 'M.ciniki_courses_offerings.edit.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.result.name) + '\');';
        }
        if( f == 'name' ) {
            return 'M.ciniki_courses_offerings.edit.updateField(\'' + s + '\',\'name\',\'' + escape(d.name.name) + '\');';
        }
    };
    this.edit.updateCourse = function(s, cid) {
        M.ciniki_courses_offerings.showEdit(null, null, cid);
    };
    this.edit.updateField = function(s, fid, result) {
        M.gE(this.panelUID + '_' + fid).value = unescape(result);
        this.removeLiveSearch(s, fid);
    };
    this.edit.fieldHistoryArgs = function(s, i) {
        if( s == 'offering' ) {
            return {'method':'ciniki.courses.offeringHistory', 'args':{'tnid':M.curTenantID, 
                'offering_id':this.offering_id, 'field':i}};
        } else {
            if( i == 'course_name' ) { i = 'name'; }
            return {'method':'ciniki.courses.courseHistory', 'args':{'tnid':M.curTenantID, 
                'course_id':this.course_id, 'field':i}};
        }
    };
    this.edit.addDropImage = function(iid) {
        M.ciniki_courses_offerings.edit.setFieldValue('primary_image_id', iid, null, null);
        return true;
    };
    this.edit.deleteImage = function(fid) {
        this.setFieldValue(fid, 0, null, null);
        return true;
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_courses_offerings.saveCourse();');
    this.edit.addClose('Cancel');
    
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_offerings', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }
        
        //
        // Decide what is visible
        //
        this.edit.sections.course.fields.type.active = (M.modFlagOn('ciniki.courses', 0x10) ? 'yes' : 'no');

        //
        // Check if the registrations numbers should shown in menu
        //
        if( M.modFlagOn('ciniki.courses', 0x40) ) { 
            this.menu.sections.current.num_cols = 2;
            this.menu.sections.upcoming.num_cols = 2;
            this.menu.sections.past.num_cols = 2;
            this.menu.sections.current.headerValues = ['Course', 'Registrations'];
            this.menu.sections.upcoming.headerValues = ['Course', 'Registrations'];
            this.menu.sections.past.headerValues = ['Course', 'Registrations'];
        } else {
            this.menu.sections.current.num_cols = 1;
            this.menu.sections.upcoming.num_cols = 1;
            this.menu.sections.past.num_cols = 1;
            this.menu.sections.current.headerValues = null;
            this.menu.sections.upcoming.headerValues = null;
            this.menu.sections.past.headerValues = null;
        }

        if( args.course_id != null && args.course_id > 0 && args.add != null && args.add == 'yes' ) {
            this.showEdit(cb,0,args.course_id);
        } else {
            this.menu.open(cb);
        }
    }

    this.showOffering = function(cb, oid) {
        if( oid != null ) {
            this.offering.offering_id = oid;
        }
        var codes = 'no';
        if( M.curTenant.modules['ciniki.courses'].flags != null && (M.curTenant.modules['ciniki.courses'].flags&0x01) == 0x01 ) { codes = 'yes'; }
        var inst = 'no';
        if( M.curTenant.modules['ciniki.courses'].flags != null && (M.curTenant.modules['ciniki.courses'].flags&0x02) == 0x02 ) { inst = 'yes'; }
        var prices = 'no';
        if( M.curTenant.modules['ciniki.courses'].flags != null && (M.curTenant.modules['ciniki.courses'].flags&0x04) == 0x04 ) { prices = 'yes'; }
        var files = 'no';
        if( M.curTenant.modules['ciniki.courses'].flags != null && (M.curTenant.modules['ciniki.courses'].flags&0x08) == 0x08 ) { files = 'yes'; }
        var reg = 'no';
        if( M.curTenant.modules['ciniki.courses'].flags != null && (M.curTenant.modules['ciniki.courses'].flags&0xC0) > 0 ) { reg = 'yes'; }
        var rsp = M.api.getJSONCb('ciniki.courses.offeringGet',
            {'tnid':M.curTenantID, 'offering_id':this.offering.offering_id,
            'classes':'yes', 'instructors':inst, 'files':files, 'prices':'yes', 'registrations':reg}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_courses_offerings.offering;
                p.data = rsp.offering;
                p.course_id = rsp.offering.course_id;

                if( rsp.offering.short_description != null && rsp.offering.short_description != '' ) {
                    p.sections.short_description.visible = 'yes';
                } else {
                    p.sections.short_description.visible = 'no';
                }
                if( rsp.offering.long_description != null && rsp.offering.long_description != '' ) {
                    p.sections.long_description.visible = 'yes';
                } else {
                    p.sections.long_description.visible = 'no';
                }
        //      var fields = ['company','email','phone_home','phone_work','phone_cell','phone_fax','url'];
        //      for(i in fields) {
        //          if( rsp.offering[fields[i]] != null && rsp.offering[fields[i]] != '' ) {
        //              p.sections.info.list[fields[i]].visible = 'yes';
        //          } else {
        //              p.sections.info.list[fields[i]].visible = 'no';
        //          }
        //      }
                p.sections.instructors.visible=(inst=='yes'?'yes':'no');
                p.sections.prices.visible=(prices=='yes'?'yes':'no');
                p.sections.files.visible=(files=='yes'?'yes':'no');
                if( (rsp.offering.reg_flags&0x03) > 0 ) {
                    reg='yes';
                } else {
                    reg='no';
                }
                p.sections._registrations.visible=(reg=='yes'?'yes':'no');
                p.refresh();
                p.show(cb);
            });
    };

    //
    // This edit form takes care of offerings and courses in one form, along with add and edit
    //
    this.showEdit = function(cb, oid, cid) {
        if( oid != null ) { this.edit.offering_id = oid; }
        if( cid != null ) { this.edit.course_id = cid; }
        if( (M.curTenant.modules['ciniki.courses'].flags&0xC0) > 0 ) {
            this.edit.sections._registrations.visible = 'yes';
            this.edit.sections._registrations.fields.reg_flags.active = 'yes';
            this.edit.sections._registrations.fields.num_seats.active = 'yes';
        } else {
            this.edit.sections._registrations.visible = 'no';
            this.edit.sections._registrations.fields.reg_flags.active = 'no';
            this.edit.sections._registrations.fields.num_seats.active = 'no';
        }
        this.edit.sections.course.fields.code.active=((M.curTenant.modules['ciniki.courses'].flags&0x01) == 0x01)?'yes':'no';
        if( this.edit.offering_id > 0 ) {
            var formname = 'Edit';
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            var rsp = M.api.getJSONCb('ciniki.courses.offeringGet',
                {'tnid':M.curTenantID, 'offering_id':this.edit.offering_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_courses_offerings.edit;
                    p.data = rsp.offering;
                    p.course_id = rsp.offering.course_id;
                    p.sections.offering.fields.class_date.active = 'no';
                    p.sections.offering.fields.start_time.active = 'no';
                    p.sections.offering.fields.end_time.active = 'no';
                    p.sections.offering.fields.num_weeks.active = 'no';
                    p.refresh();
                    p.show(cb);
                });
        } else if( this.edit.course_id > 0 ) {
            var formname = 'Add';
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            var rsp = M.api.getJSONCb('ciniki.courses.courseGet',
                {'tnid':M.curTenantID, 'course_id':this.edit.course_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_courses_offerings.edit;
                    p.data = rsp.course;
                    p.data['course_name'] = rsp.course.name;
                    p.course_id = rsp.course.id;
                    p.sections.offering.fields.class_date.active = 'yes';
                    p.sections.offering.fields.start_time.active = 'yes';
                    p.sections.offering.fields.end_time.active = 'yes';
                    p.sections.offering.fields.num_weeks.active = 'yes';
                    p.refresh();
                    p.show(cb);
                });
        } else {
            var formname = 'Add';
            var p = M.ciniki_courses_offerings.edit;
            p.data = {};
            p.sections.offering.fields.class_date.active = 'yes';
            p.sections.offering.fields.start_time.active = 'yes';
            p.sections.offering.fields.end_time.active = 'yes';
            p.sections.offering.fields.num_weeks.active = 'yes';
            p.sections._buttons.buttons.delete.visible = 'no';
            p.refresh();
            p.show(cb);
        }
    };

    this.saveCourse = function() {
        if( this.edit.course_id > 0 ) {
            // Update course
            var c = this.edit.serializeFormSection('no', 'course')
                + this.edit.serializeFormSection('no', '_image')
                + this.edit.serializeFormSection('no', '_short_description')
                + this.edit.serializeFormSection('no', '_long_description');
            var nv = this.edit.formFieldValue(this.edit.sections.course.fields.course_name, 'course_name');
            if( nv != this.edit.data.course_name ) {
                c += '&name=' + encodeURIComponent(nv);
            }
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.courses.courseUpdate', 
                    {'tnid':M.curTenantID, 'course_id':this.edit.course_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_courses_offerings.saveOffering();
                    });
            } else {
                M.ciniki_courses_offerings.saveOffering();
            }
        } else {
            // Add course
            // FIXME: Check if name already exists, ask if they want to use that course
            var c = this.edit.serializeFormSection('yes', 'course')
                + this.edit.serializeFormSection('yes', '_image')
                + this.edit.serializeFormSection('no', '_short_description')
                + this.edit.serializeFormSection('no', '_long_description');
            c += '&name=' + encodeURIComponent(this.edit.formFieldValue(this.edit.sections.course.fields.course_name, 'course_name'));
            var rsp = M.api.postJSONCb('ciniki.courses.courseAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_courses_offerings.edit.course_id = rsp.id;
                    M.ciniki_courses_offerings.saveOffering();
                });
        }
    };

    this.saveOffering = function() {
        if( this.edit.offering_id > 0 ) {
            // Update offering details
            var c = this.edit.serializeFormSection('no', 'offering');
            if( this.edit.course_id != this.edit.data.course_id ) {
                c += '&course_id=' + encodeURIComponent(this.edit.course_id);
            }
            if( M.curTenant.modules['ciniki.courses'].flags != null && (M.curTenant.modules['ciniki.courses'].flags&0xC0) > 0) { 
                c += this.edit.serializeFormSection('no', '_registrations');
            }
            if( c != '' ) {
                var rsp = M.api.postJSONCb('ciniki.courses.offeringUpdate', 
                    {'tnid':M.curTenantID, 
                    'offering_id':this.edit.offering_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        M.ciniki_courses_offerings.edit.close();
                    });
            } else {
                this.edit.close();
            }
        } else {
            // Add offering
            var c = this.edit.serializeFormSection('yes', 'offering');
            if( M.curTenant.modules['ciniki.courses'].flags != null && (M.curTenant.modules['ciniki.courses'].flags&0xC0) > 0) { 
                c += this.edit.serializeFormSection('yes', '_registrations');
            }
            c += '&course_id=' + encodeURIComponent(this.edit.course_id);
//              c += '&name=' + encodeURIComponent(this.edit.offering_name);
            var rsp = M.api.postJSONCb('ciniki.courses.offeringAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    if( rsp.id > 0 ) {
                        M.ciniki_courses_offerings.showOffering(M.ciniki_courses_offerings.edit.cb, rsp.id);
                    } else {
                        M.ciniki_courses_offerings.edit.close();
                    }
                });
        }
    };

    this.deleteOffering = function() {
        if( confirm('Are you sure you want to delete this course offering?') ) {
            var rsp = M.api.getJSONCb('ciniki.courses.offeringDelete', {'tnid':M.curTenantID, 
                'offering_id':this.offering.offering_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_courses_offerings.offering.close();
                M.ciniki_courses_offerings.edit.reset();
            });
        }
    };
}
