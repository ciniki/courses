//
// This app will display and update registrations for an course offering
//
function ciniki_courses_registrations() {
    //
    // registrations panel
    //
    this.menu = new M.panel('Registrations',
        'ciniki_courses_registrations', 'menu',
        'mc', 'large narrowaside', 'sectioned', 'ciniki.courses.registrations.menu');
    this.menu.offering_id = 0;
    this.menu.sections = {
//          'search':{'label':'', 'type':'livesearch'},
        'prices':{'label':'Add Registration', 'type':'simplegrid', 'num_cols':2 , 'aside':'yes'},
        '_buttons':{'label':'', 'aside':'yes', 'buttons':{
            'registrationspdf':{'label':'Class List (PDF)', 'fn':'M.ciniki_courses_registrations.menu.registrationsPDF(M.ciniki_courses_registrations.menu.offering_id);'},
            'attendancepdf':{'label':'Attendance List (PDF)', 'fn':'M.ciniki_courses_registrations.menu.attendancePDF(M.ciniki_courses_registrations.menu.offering_id);'},
            'registrationsexcel':{'label':'Class List (Excel)', 'fn':'M.ciniki_courses_registrations.menu.registrationsExcel(M.ciniki_courses_registrations.menu.offering_id);'},
            'email':{'label':'Email Class', 'fn':'M.ciniki_courses_registrations.menu.emailShow();'},
            }},
        'registrations':{'label':'Registrations', 'type':'simplegrid', 'num_cols':4,
            'noData':'No registrations',
            'sortable':'yes',
            'sortTypes':['text', 'text'],
            'cellClasses':['multiline', 'multiline', ''],
            },
        'messages':{'label':'Emails', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['multiline', 'multiline'],
            'headerValues':['Name/Date', 'Email/Subject'],
            'sortable':'yes',
            'sortTypes':['text','text'],
            'noData':'No Emails Sent',
            },
        };
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'registrations' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.registration.customer_name + '</span>';
                case 1: return '<span class="maintext">' + d.registration.student_name + '</span>';
                case 2: return '<span class="maintext">' + d.registration.yearsold + '</span>';
                case 3: return '<span class="maintext">' + d.registration.invoice_status_text + '</span>';
                case 4: return '<span class="maintext">' + d.registration.registration_amount_display + '</span>';
            }
        } 
        if( s == 'prices' ) {
            switch(j) {
                case 0: return d.price.name;
                case 1: return d.price.unit_amount_display;
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
    }
    this.menu.sectionData = function(s) {
        return this.data[s];
    }
    this.menu.noData = function(s) { return this.sections[s].noData; }
    this.menu.rowFn = function(s, i, d) {
        if( s == 'registrations' ) {
            return 'M.startApp(\'ciniki.courses.sapos\',null,\'M.ciniki_courses_registrations.menu.open();\',\'mc\',{\'registration_id\':\'' + d.registration.id + '\',\'source\':\'offering\'});';
        }
        if( s == 'prices' ) {
            return 'M.startApp(\'ciniki.courses.sapos\',null,\'M.ciniki_courses_registrations.menu.open();\',\'mc\',{\'offering_id\':M.ciniki_courses_registrations.menu.offering_id,\'price_id\':\'' + d.price.id + '\',\'source\':\'offering\'});';
        }
        if( s == 'messages' ) {
            return 'M.startApp(\'ciniki.mail.main\',null,\'M.ciniki_courses_registrations.menu.open();\',\'mc\',{\'message_id\':\'' + d.id + '\'});';
        }
    }
    this.menu.registrationsPDF = function(oid) {
        M.api.openFile('ciniki.courses.offeringRegistrations', {'tnid':M.curTenantID, 'output':'pdf', 'offering_id':oid});
    }
    this.menu.attendancePDF = function(oid) {
        M.api.openFile('ciniki.courses.offeringRegistrations', {'tnid':M.curTenantID, 'template':'attendance', 'output':'pdf', 'offering_id':oid});
    }
    this.menu.emailShow = function() {
        var customers = [];
        for(var i in this.data.registrations) {
            customers[i] = {
                'id':this.data.registrations[i].registration.customer_id,
                'name':this.data.registrations[i].registration.customer_name,
                };
        }
        M.startApp('ciniki.mail.omessage',
            null,
            'M.ciniki_courses_registrations.menu.open();',
            'mc',
            {'subject':'Re: ' + this.data.offering.name + ' (' + this.data.offering.condensed_date + ')', 
                'list':customers, 
                'object':'ciniki.courses.offering',
                'object_id':this.offering_id,
            });
    }
    this.menu.registrationsExcel = function(oid) {
        M.api.openFile('ciniki.courses.offeringRegistrations', {'tnid':M.curTenantID, 'output':'excel', 'offering_id':oid});
    }
    this.menu.open = function(cb, oid) {
        this.data = {};
        if( oid != null ) { this.offering_id = oid; }
            M.api.getJSONCb('ciniki.courses.offeringRegistrationList', 
                {'tnid':M.curTenantID, 'offering_id':this.offering_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_courses_registrations.menu;
                    p.data = rsp;
                    p.sections.registrations.headerValues = ['Name', 'Student', 'Age', 'Paid', 'Amount'];
                    p.sections.registrations.num_cols = 5;
                    p.refresh();
                    p.show(cb);
                });
    }
    this.menu.addClose('Back');

    //
    // The panel for editing a registrant
    //
    this.edit = new M.panel('Registrant',
        'ciniki_courses_registrations', 'edit',
        'mc', 'medium', 'sectioned', 'ciniki.courses.registrations.edit');
    this.edit.data = null;
    this.edit.offering_id = 0;
    this.edit.registration_id = 0;
    this.edit.sections = { 
        'customer_details':{'label':'Customer', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_courses_registrations.updateEditCustomer(null);\',\'mc\',{\'next\':\'M.ciniki_courses_registrations.updateEditCustomer\',\'customer_id\':M.ciniki_courses_registrations.edit.customer_id});',
            },
        'invoice':{'label':'Invoice', 'visible':'no', 'type':'simplegrid', 'num_cols':5,
            'headerValues':['Invoice #', 'Date', 'Customer', 'Amount', 'Status'],
            'cellClasses':['',''],
            },
        '_customer_notes':{'label':'Customer Notes', 'fields':{
            'customer_notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_notes':{'label':'Private Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'hint':'', 'size':'small', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_courses_registrations.saveRegistration();'},
            'saveandinvoice':{'label':'Save and Invoice', 'fn':'M.ciniki_courses_registrations.saveRegistration(\'yes\');'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_courses_registrations.deleteRegistration();'},
            }},
        };  
    this.edit.fieldValue = function(s, i, d) { return this.data[i]; }
    this.edit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.courses.offeringRegistrationHistory', 'args':{'tnid':M.curTenantID, 
            'registration_id':this.registration_id, 'offering_id':this.offering_id, 'field':i}};
    }
    this.edit.sectionData = function(s) {
        if( s == 'invoice' ) { return this.data[s]!=null?{'invoice':this.data[s]}:{}; }
        return this.data[s];
    }
    this.edit.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch(j) {
                case 0: return d.detail.label;
                case 1: return d.detail.value.replace(/\n/, '<br/>');
            }
        } 
        if( s == 'invoice' ) {
            switch(j) {
                case 0: return d.invoice_number;
                case 1: return d.invoice_date;
                case 2: return (d.customer!=null&&d.customer.display_name!=null)?d.customer.display_name:'';
                case 3: return d.total_amount_display;
                case 4: return d.payment_status_text;
            }
        }
    };
    this.edit.rowFn = function(s, i, d) { 
        if( s == 'invoice' ) { return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_courses_registrations.showEdit();\',\'mc\',{\'invoice_id\':\'' + d.id + '\'});'; }
        return ''; 
    };
    this.edit.addButton('save', 'Save', 'M.ciniki_courses_registrations.saveRegistration();');
    this.edit.addClose('Cancel');

    //
    // The add invoice panel, which display the price list for quantity
    //
    this.newinvoice = new M.panel('Create Invoice',
        'ciniki_courses_registrations', 'newinvoice',
        'mc', 'medium', 'sectioned', 'ciniki.courses.registrations.newinvoice');
    this.newinvoice.data = null;
    this.newinvoice.customer_id = 0;
    this.newinvoice.offering_id = 0;
    this.newinvoice.registration_id = 0;
    this.newinvoice.quantity = 1;
    this.newinvoice.sections = {
        'prices':{'label':'Price List', 'fields':{
            'price_id':{'label':'Price', 'type':'select', 'options':{}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Create Invoice', 'fn':'M.ciniki_courses_registrations.createInvoice();'},
            }},
        };
    this.newinvoice.fieldValue = function(s, i, d) { return this.data[i]; }
    this.newinvoice.addClose('Cancel');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_courses_registrations', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.menu.open(cb, args.offering_id);
    }

    this.showAdd = function(cb, oid) {
        // Setup the edit panel for when the customer edit returns
        if( cb != null ) { this.edit.cb = cb; }
        if( oid != null ) { this.edit.offering_id = oid; }
        M.startApp('ciniki.customers.edit',null,cb,'mc',{'next':'M.ciniki_courses_registrations.showFromCustomer','customer_id':0});
    };

    this.showFromCustomer = function(cid) {
        
        this.showEdit(this.edit.cb, cid, this.edit.offering_id, 0);
    };

    this.showEdit = function(cb, cid, oid, rid) {
        this.edit.reset();
        if( cid != null ) { this.edit.customer_id = cid; }
        if( oid != null ) { this.edit.offering_id = oid; }
        if( rid != null ) { this.edit.registration_id = rid; }

        // Check if this is editing a existing registration or adding a new one
        if( this.edit.registration_id > 0 ) {
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.courses.offeringRegistrationGet', {'tnid':M.curTenantID, 
                'registration_id':this.edit.registration_id, 'customer':'yes', 'invoice':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_courses_registrations.edit;
                    p.data = rsp.registration;
                    p.offering_id = rsp.registration.offering_id;
                    p.customer_id = rsp.registration.customer_id;
                    p.sections.invoice.visible=(M.curTenant.modules['ciniki.sapos']!=null)?'yes':'no';
//                  p.sections.invoice.addTxt=(rsp.registration.invoice_id==0)?'Invoice Customer':'';
//                  p.sections._buttons.buttons.saveandinvoice.visible='no';
                    p.sections._buttons.buttons.saveandinvoice.visible=(M.curTenant.modules['ciniki.sapos']!=null&&rsp.registration.invoice_id==0)?'yes':'no';
                    p.refresh();
                    p.show(cb);
                });
        } else if( this.edit.customer_id > 0 ) {
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 
                'customer_id':this.edit.customer_id, 'phones':'yes', 'emails':'yes'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_courses_registrations.edit;
                    p.data = {'customer_details':rsp.details};
//                  p.sections.invoice.addTxt = '';
                    p.sections._buttons.buttons.saveandinvoice.visible = (M.curTenant.modules['ciniki.sapos']!=null)?'yes':'no';
                    p.refresh();
                    p.show(cb);
                });
        }
    };

    this.editCustomer = function(cb, cid) {
        M.startApp('ciniki.customers.edit',null,cb,'mc',{'customer_id':cid});
    };

    this.updateEditCustomer = function(cid) {
        if( cid != null && this.edit.customer_id != cid ) {
            this.edit.customer_id = cid;
        }
        if( this.edit.customer_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.customers.customerDetails', {'tnid':M.curTenantID, 
                'customer_id':this.edit.customer_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_courses_registrations.edit;
                    p.data.customer = rsp.details;
                    p.refreshSection('customer_details');
                    p.show();
                });
        }   
    };

    this.saveRegistration = function(inv) {
//      var quantity = this.edit.formFieldValue(this.edit.sections.registration.fields.num_seats, 'num_seats');
        var quantity = 1;
        if( this.edit.registration_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( this.edit.data.customer_id != this.edit.customer_id ) {
                c += 'customer_id=' + this.edit.customer_id + '&';
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.courses.offeringRegistrationUpdate', 
                    {'tnid':M.curTenantID, 
                    'registration_id':M.ciniki_courses_registrations.edit.registration_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } 
                        var p = M.ciniki_courses_registrations.edit;
                        if( inv != null && inv == 'yes' ) {
                            M.ciniki_courses_registrations.newInvoice('M.ciniki_courses_registrations.showEdit(null,null,'+p.registration_id+',null);', p.offering_id, p.customer_id, p.registration_id, quantity);
                        } else {
                            p.close();
                        }
                    });
            } else {
                if( inv != null && inv == 'yes' ) {
                    M.ciniki_courses_registrations.newInvoice('M.ciniki_courses_registrations.showEdit(null,null,'+this.edit.registration_id+',null);', this.edit.offering_id, this.edit.customer_id, this.edit.registration_id, quantity);
                } else {
                    this.edit.close();
                }
            }
        } else {
            var c = this.edit.serializeForm('yes');
            M.api.postJSONCb('ciniki.courses.offeringRegistrationAdd', 
                {'tnid':M.curTenantID, 'offering_id':this.edit.offering_id,
                    'customer_id':this.edit.customer_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    if( inv != null && inv == 'yes' ) {
                        M.ciniki_courses_registrations.newInvoice(M.ciniki_courses_registrations.edit.cb, M.ciniki_courses_registrations.edit.offering_id, M.ciniki_courses_registrations.edit.customer_id, rsp.id, quantity);
                    } else {
                        M.ciniki_courses_registrations.edit.close();
                    }
                });
        }
    };

    this.deleteRegistration = function() {
        M.confirm("Are you sure you want to remove this registration?",null,function() {
            var rsp = M.api.getJSONCb('ciniki.courses.offeringRegistrationDelete', 
                {'tnid':M.curTenantID, 
                'registration_id':M.ciniki_courses_registrations.edit.registration_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_courses_registrations.edit.close();    
                });
        });
    };

    this.newInvoice = function(cb, oid, cid, rid, quantity) {
        if( oid != null ) { this.newinvoice.offering_id = oid; }
        if( cid != null ) { this.newinvoice.customer_id = cid; }
        if( rid != null ) { this.newinvoice.registration_id = rid; }
        if( quantity != null ) { this.newinvoice.quantity = quantity; }
        M.api.getJSONCb('ciniki.courses.offeringPriceList', {'tnid':M.curTenantID,
            'offering_id':this.newinvoice.offering_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_courses_registrations.newinvoice;
                p.prices = rsp.prices;
                p.data = {'price_id':0};
                // Setup the price list
                p.sections.prices.fields.price_id.options = {};
                for(i in rsp.prices) {
                    p.sections.prices.fields.price_id.options[rsp.prices[i].price.id] = rsp.prices[i].price.name + ' ' + rsp.prices[i].price.unit_amount_display;
                    if( i == 0 ) {
                        p.data.price_id = rsp.prices[i].price.id;
                    }
                }
                p.refresh();
                p.show(cb);
            });
    };

    this.createInvoice = function() {
        var items = [];
        items[0] = {
            'status':0,
            'object':'ciniki.courses.offering_registration',
            'object_id':this.newinvoice.registration_id,
            'description':'',
            'quantity':this.newinvoice.quantity,
            'unit_amount':0,
            'unit_discount_amount':0,
            'unit_discount_percentage':0,
            'taxtype_id':0,
            'notes':'',
            };
        var price_id = this.newinvoice.formFieldValue(this.newinvoice.sections.prices.fields.price_id, 'price_id');
        var prices = this.newinvoice.prices;
        // Find the price selected
        for(i in prices) {
            if( prices[i].price.id == price_id ) {
                items[0].price_id = prices[i].price.id;
                items[0].code = '';
                items[0].description = prices[i].price.course_name + (prices[i].price.name!=''?' - '+prices[i].price.name:'');
                items[0].unit_amount = prices[i].price.unit_amount;
                items[0].unit_discount_amount = prices[i].price.unit_discount_amount;
                items[0].unit_discount_percentage = prices[i].price.unit_discount_percentage;
                items[0].taxtype_id = prices[i].price.taxtype_id;
                items[0].flags = 0x20;
            }
        }
        M.startApp('ciniki.sapos.invoice',null,this.newinvoice.cb,'mc',{'customer_id':this.newinvoice.customer_id,'items':items});
    };
}
