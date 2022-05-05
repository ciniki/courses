//
// This file contains the UI panels to manage course information, instructors, certs, locations and messages
//
function ciniki_courses_settings() {
    this.courseForms = {
        '':'None',
        };
    this.toggleOptions = {'no':'Hide', 'yes':'Display'};
    this.positionOptions = {'left':'Left', 'center':'Center', 'right':'Right', 'off':'Off'};

    this.init = function() {
        //
        // The menu panel
        //
        this.menu = new M.panel('Settings',
            'ciniki_courses_settings', 'menu',
            'mc', 'narrow', 'sectioned', 'ciniki.courses.settings.menu');
        this.menu.sections = {  
            '_offerings':{'label':'', 'list':{
                'documents':{'label':'Documents', 'visible':'yes', 'fn':'M.ciniki_courses_settings.documentsShow(\'M.ciniki_courses_settings.showMenu();\');'},
                }},
        };
        this.menu.addClose('Back');

        //
        // The documents settings panel
        //
        this.documents = new M.panel('Documents',
            'ciniki_courses_settings', 'documents',
            'mc', 'medium', 'sectioned', 'ciniki.courses.settings.documents');
        this.documents.sections = {
            'image':{'label':'Header Image', 'fields':{
                'default-header-image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
                }},
/*          'header':{'label':'Header Address Options', 'fields':{
                'default-header-contact-position':{'label':'Position', 'type':'toggle', 'default':'center', 'toggles':this.positionOptions},
                'default-header-name':{'label':'Tenant Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'default-header-address':{'label':'Address', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'default-header-phone':{'label':'Phone', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'default-header-cell':{'label':'Cell', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'default-header-fax':{'label':'Fax', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'default-header-email':{'label':'Email', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                'default-header-website':{'label':'Website', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
                }}, */
            'attendance':{'label':'Attendance Reports', 'fields':{
                'templates-attendance-phones':{'label':'Show Phone Numbers', 'type':'toggle', 'toggles':{'no':'No', 'yes':'Yes'}},
                'templates-attendance-emails':{'label':'Show Emails', 'type':'toggle', 'toggles':{'no':'No', 'yes':'Yes'}},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_courses_settings.documentsSave();'},
                }},
        };
        this.documents.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.courses.settingsHistory', 
                'args':{'tnid':M.curTenantID, 'setting':i}};
        }
        this.documents.fieldValue = function(s, i, d) {
            if( this.data[i] == null && d.default != null ) { return d.default; }
            return this.data[i];
        };
        this.documents.addDropImage = function(iid) {
            M.ciniki_courses_settings.documents.setFieldValue('default-header-image', iid);
            return true;
        };
        this.documents.deleteImage = function(fid) {
            this.setFieldValue(fid, 0);
            return true;
        };
        this.documents.addButton('save', 'Save', 'M.ciniki_courses_settings.documentsSave();');
        this.documents.addClose('Cancel');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_settings', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.showMenu(cb);
    }

    //
    // Grab the stats for the tenant from the database and present the list of orders.
    //
    this.showMenu = function(cb) {
        this.menu.refresh();
        this.menu.show(cb);
    }

    //
    // Documents
    //
    this.documentsShow = function(cb) {
        M.api.getJSONCb('ciniki.courses.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_courses_settings.documents;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    };

    //
    // Save the Document settings
    //
    this.documentsSave = function() {
        var c = this.documents.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.courses.settingsUpdate', {'tnid':M.curTenantID}, 
                c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_settings.documents.close();
                });
        } else {
            this.documents.close();
        }
    };
}
