//
// The following tools are for the programs and offerings
//
function ciniki_courses_tools() {
    //
    // The main menu panel
    //
    this.menu = new M.panel('Program Tools', 'ciniki_courses_tools', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.courses.tools.menu');
    this.menu.data = {};
    this.menu.sections = {
        'reports':{'label':'Reports', 'list':{
//            'studentsummary':{'label':'Attendee Summary', 'fn':'M.ciniki_courses_tools.students.open(\'M.ciniki_courses_tools.menu.show();\');'},
            'students':{'label':'Student Registrations', 'fn':'M.ciniki_courses_tools.students.open(\'M.ciniki_courses_tools.menu.show();\');'},
            'Sessions':{'label':'Program Sessions Summary', 'fn':'M.ciniki_courses_tools.sessions.open(\'M.ciniki_courses_tools.menu.show();\');'},
            }},
        };
    this.menu.open = function(cb) {
        this.show(cb);
    }
    this.menu.addClose('Back');

    //
    // The panel to display the student report
    //
    this.students = new M.panel('', 'ciniki_courses_tools', 'students', 'mc', 'full', 'sectioned', 'ciniki.courses.tools.students');
    this.students.data = {};  
    this.students.start_date = '';
    this.students.end_date = '';
    this.students.sections = {
        'dates':{'label':'Date Range', 'aside':'yes', 'fields':{
            'start_date':{'label':'Start Date', 'type':'date', 'onchangeFn':'M.ciniki_courses_tools.students.updateDate();'},
            'end_date':{'label':'End Date', 'type':'date', 'onchangeFn':'M.ciniki_courses_tools.students.updateDate();'},
            }},
        '_buttons':{'label':'', 'aside':'yes', 'size':'half', 'buttons':{
            'refresh':{'label':'Refresh', 'fn':'M.ciniki_courses_tools.students.open();'},
            'download':{'label':'Download Excel', 'fn':'M.ciniki_courses_tools.students.downloadExcel();'},
            }},
        'customers':{'label':'Students',
            'type':'simplegrid', 'num_cols':5,
            'headerValues':['Student', 'Program', 'Start', 'End', 'Price'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'date', 'date', 'number'],
            'cellClasses':['', '', '', '', ''],
            }
    }
    this.students.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.display_name;
            case 1: return d.course_name;
            case 2: return d.start_date;
            case 3: return d.end_date;
            case 4: return M.formatDollar(d.price_name);
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
                var p = M.ciniki_courses_tools.students;
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
    // The panel to display the program report
    //
    this.sessions = new M.panel('', 'ciniki_courses_tools', 'sessions', 'mc', 'full', 'sectioned', 'ciniki.courses.tools.sessions');
    this.sessions.data = {};  
    this.sessions.start_date = '';
    this.sessions.end_date = '';
    this.sessions.sections = {
        'dates':{'label':'Date Range', 'aside':'yes', 'fields':{
            'start_date':{'label':'Start Date', 'type':'date', 'onchangeFn':'M.ciniki_courses_tools.sessions.updateDate();'},
            'end_date':{'label':'End Date', 'type':'date', 'onchangeFn':'M.ciniki_courses_tools.sessions.updateDate();'},
            }},
        '_buttons':{'label':'', 'aside':'yes', 'size':'half', 'buttons':{
            'refresh':{'label':'Refresh', 'fn':'M.ciniki_courses_tools.sessions.open();'},
            'download':{'label':'Download Excel', 'fn':'M.ciniki_courses_tools.sessions.downloadExcel();'},
            }},
        'offerings':{'label':'Sessions',
            'type':'simplegrid', 'num_cols':15,
            'headerValues':['Program', 'Code', 'Session', 'Level', 'Type', 'Category', 'Medium', 'Ages', 'Instructor', 'Start', 'End', 'Attendance', 'Spots', 'Cost', 'Revenue'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'text', 'date', 'date', 'number', 'number', 'number', 'number'],
            'cellClasses':['', '', '', '', '', '', '', '', '', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright', 'alignright'],
            }
    }
    this.sessions.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.course_name;
            case 1: return d.offering_code;
            case 2: return d.offering_name;
            case 3: return d.level;
            case 4: return d.type;
            case 5: return d.category;
            case 6: return d.medium;
            case 7: return d.ages;
            case 8: return d.instructors;
            case 9: return d.start_date;
            case 10: return d.end_date;
            case 11: return d.num_registrations;
            case 12: return d.num_seats;
            case 13: return M.formatDollar(d.max_price);
            case 14: return M.formatDollar(d.total_revenue);
        }
    }
    this.sessions.fieldValue = function(s, i, d) {
        if( i == 'start_date' ) { return this.start_date; }
        if( i == 'end_date' ) { return this.end_date; }
    }
    this.sessions.updateDate = function() {
        this.start_date = this.formValue('start_date');
        this.end_date = this.formValue('end_date');
    }
    this.sessions.open = function(cb) {
        if( this.start_date != '' ) {
            M.api.getJSONCb('ciniki.courses.reportCourses', {'tnid':M.curTenantID, 'start_date':this.start_date, 'end_date':this.end_date}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_courses_tools.sessions;
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
    this.sessions.downloadExcel = function() {
        M.api.openFile('ciniki.courses.reportCourses', {'tnid':M.curTenantID, 'start_date':this.start_date, 'end_date':this.end_date, 'output':'excel'});
    }
    this.sessions.addClose('Back');

    //
    // The main start function
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_tools', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.menu.open(cb);
    }
}
