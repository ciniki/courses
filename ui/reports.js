//
// The courses app to manage an artists collection
//
function ciniki_courses_reports() {

    //
    // The panel to display the course form
    //
    this.students = new M.panel('', 'ciniki_courses_reports', 'students', 'mc', 'large narrowaside', 'sectioned', 'ciniki.courses.reports.students');
    this.students.data = {};  
    this.students.start_date = '';
    this.students.end_date = '';
    this.students.sections = {
        'dates':{'label':'Date Range', 'aside':'yes', 'fields':{
            'start_date':{'label':'Start Date', 'type':'date', 'onchangeFn':'M.ciniki_courses_reports.students.updateDate();'},
            'end_date':{'label':'End Date', 'type':'date', 'onchangeFn':'M.ciniki_courses_reports.students.updateDate();'},
            }},
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'refresh':{'label':'Refresh', 'fn':'M.ciniki_courses_reports.students.open();'},
            'download':{'label':'Download Excel', 'fn':'M.ciniki_courses_reports.students.downloadExcel();'},
            }},
        'customers':{'label':'Students',
            'type':'simplegrid', 'num_cols':3,
            'headerValues':['Student', 'Course', 'Date'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'text'],
            'cellClasses':['', '', ''],
            }
    }
    this.students.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.display_name;
            case 1: return d.course_name;
            case 2: return d.condensed_date;
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
                console.log('cb');
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_courses_reports.students;
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

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_reports', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }
        this.students.open(cb);
    }
}
