// PURPOSE: Handle functions for Edit Project admin page
//          This file loaded by add_dhp_project_admin_scripts() in dhp-project-functions.php
// ASSUMES: dhpDataLib is used to pass parameters to this function via wp_localize_script()
//          Hidden data in DIV ID map-layers for info about map layers
//          Initial project settings embedded in HTML in DIV ID project_settings by show_dhp_project_settings_box()
// USES:    Libraries jQuery, Underscore, Knockout, jQuery UI, jQuery.nestable, jquery-colorpicker
// NOTES:   Relies on some HTML data generated in show_dhp_project_settings_box() of dhp-project-functions.php
//          Data that relies on WP queries cannot be passed in via "localization" pf dhpDataLib because that
//            causes WP globals (like $post) to be overwritten
//          Problems with jQueryUI in WordPress mean that dialogs must be set to 'draggable: false'

jQuery(document).ready(function($) {

      // Constants
  var _blankSettings = {
        general: {
          version: 4,
          homeLabel: '',
          homeURL: '',
          mTitle: 'the_title',
          mKey: 'disable'
        },
        motes: [],
        eps: [],
        views: {
          select: {
            title: '',
            width: 'medium',
            link: 'disable',  linkLabel: '',  linkNewTab: true,
            link2: 'disable', link2Label: '', link2NewTab: true,
            widgets: [],
            content: []
          },
          post: {
            title: '',
            content: []
          },
          transcript: {
            audio: 'disable',
            video: 'disable',
            transcript: 'disable',
            transcript2: 'disable',
            timecode: 'disable',
            source: 'disable',
            content: []
          }
        }
    };

    // Parameters passed by WordPress via localization
  var ajax_url     = dhpDataLib.ajax_url;
  var projectID    = dhpDataLib.projectID;
  var pngImages    = dhpDataLib.pngImages;
  var localized    = $.parseJSON(dhpDataLib.localized);


    // Parameters passed via HTML elements
    // Need to ensure encoded as arrays (not Object properties) and handle empty params
  var customFieldsParam = $('#custom-fields').text();
  if (customFieldsParam.length > 2) {
    customFieldsParam = JSON.parse(customFieldsParam);
    customFieldsParam = normalizeArray(customFieldsParam);
  } else {
    customFieldsParam = [];
  }

  var mapLayersParam = $('#map-layers').text();
  if (mapLayersParam.length > 2) {
      // Get overlay maps from embedded HTML and pass to initialize map services
    mapLayersParam = JSON.parse(mapLayersParam);
    dhpMapServices.init(mapLayersParam);
  } else {
    mapLayersParam = [];
  }

    // Get initial project settings -- make blank settings if new project, or if settings not version 4
  var savedSettings = $('#project_settings').val();
  if (savedSettings.length < 2) {
    savedSettings = _blankSettings;
  } else {
    savedSettings = JSON.parse(savedSettings);
    if (savedSettings == undefined || savedSettings.general == undefined) {
      savedSettings = _blankSettings;
    }
  }

    // Prevents users from exiting page if there are unsaved changes
  window.beforeunload = window.onbeforeunload = function (e) {
    if (projObj.settingsDirty()) {
      e.returnValue = 'You have unsaved changes. If you leave the page, these changes will be lost.';
      return 'You have unsaved changes. If you leave the page, these changes will be lost.';
    }
  }


//===================================== UTILITIES ===================================

    // PURPOSE: Ensure that data is returned as array
    // NOTES:   This was necessary because of irregular JSON encoding in DH Press 1.X:
    //            sometimes array was encoded as Object properties; shouldn't be needed now.
  function normalizeArray(data) {
    if (_.isArray(data)) {
      return data;
    }
    return _.values(data);
  };

    // PURPOSE: Change empty setting string to 'disable' as default
  function disableByDefault(setting) {
    if (setting === null || setting === '' || setting === 'no-link') {
      return 'disable';
    }
    return setting;
  };

//===================================== OBJECT CLASSES ===================================

    // Class constructor for a String element in an array (such as settings.legends)
    // NOTES: This is necessary because KO needs to bind to an object in an observable array, rather than just
    //          a string. See the following for explanation:
    //          http://stackoverflow.com/questions/15749572/how-can-i-bind-an-editable-ko-observablearray-of-observable-strings?lq=1
    //          https://github.com/knockout/knockout/issues/708
  var ArrayString = function(theString) {
    this.name = ko.observable(theString);
  }
  ArrayString.prototype.toJSON = function() {
    return this.name;
  };

    // Class constructor for Mote
    // NOTES: Once constructed, these are only displayed, so we don't need to make them observable
  var Mote = function(name, type, customField, delim) {
    var self = this;

    self.name = name;
    self.type = type;
    self.cf   = customField;
    self.delim= delim;
  } // Mote()


    // Class constructor for Map Entry Point
    // NOTES: Since user can always change these, we need to make them observable
  var MapEntryPoint = function(epSettings) {
    var self = this;

    self.type = 'map';
    self.label= ko.observable(epSettings.label || localized['name_me']);
    self.settings = { };
    self.settings.lat = ko.observable(epSettings.settings.lat);
    self.settings.lon = ko.observable(epSettings.settings.lon);
    self.settings.zoom = ko.observable(epSettings.settings.zoom);
    self.settings.cluster = ko.observable(epSettings.settings.cluster);
    self.settings.size = ko.observable(epSettings.settings.size);
    self.settings.layers = ko.observableArray();
    ko.utils.arrayForEach(normalizeArray(epSettings.settings.layers), function(theLayer) {
      self.settings.layers.push(new MapLayer(theLayer));
    });
    self.settings.coordMote = ko.observable(epSettings.settings.coordMote);
    self.settings.legends = ko.observableArray();
    ko.utils.arrayForEach(normalizeArray(epSettings.settings.legends), function(theLegend) {
      self.settings.legends.push(new ArrayString(theLegend));
    });
  } // MapEntryPoint()


    // Class constructor for Cards Entry Point
  var CardsEntryPoint = function(epSettings) {
    var self = this;

    self.type = 'cards';
    self.label= ko.observable(epSettings.label || localized['name_me']);
    self.settings = { };
    self.settings.titleOn = ko.observable(epSettings.settings.titleOn);
    self.settings.color = ko.observable(epSettings.settings.color);
    self.settings.defColor = ko.observable(epSettings.settings.defColor);
    self.settings.bckGrd = ko.observable(epSettings.settings.bckGrd);
    self.settings.width = ko.observable(epSettings.settings.width);
    self.settings.height = ko.observable(epSettings.settings.height);

    self.settings.content = ko.observableArray();
    ko.utils.arrayForEach(normalizeArray(epSettings.settings.content), function(cMote) {
      self.settings.content.push(new ArrayString(cMote));
    });

    self.settings.filterMotes = ko.observableArray();
    ko.utils.arrayForEach(normalizeArray(epSettings.settings.filterMotes), function(fMote) {
      self.settings.filterMotes.push(new ArrayString(fMote));
    });

    self.settings.sortMotes = ko.observableArray();
    ko.utils.arrayForEach(normalizeArray(epSettings.settings.sortMotes), function(sMote) {
      self.settings.sortMotes.push(new ArrayString(sMote));
    });
  } // CardsEntryPoint()


    // Class constructor for Pinboard Entry Point
  var PinboardEntryPoint = function(epSettings) {
    var self = this;

    self.type = 'pinboard';
    self.label= ko.observable(epSettings.label || localized['name_me']);
    self.settings = { };
    self.settings.bckGrd = ko.observable(epSettings.settings.bckGrd);
    self.settings.imageURL = ko.observable(epSettings.settings.imageURL);
    self.settings.dw = ko.observable(epSettings.settings.dw);
    self.settings.dh = ko.observable(epSettings.settings.dh);
    self.settings.iw = ko.observable(epSettings.settings.iw);
    self.settings.ih = ko.observable(epSettings.settings.ih);
    self.settings.size = ko.observable(epSettings.settings.size);
    self.settings.icon = ko.observable(epSettings.settings.icon);
    self.settings.coordMote = ko.observable(epSettings.settings.coordMote);
    self.settings.animscript = ko.observable(epSettings.settings.animscript);
    self.settings.animSVG = ko.observable(epSettings.settings.animSVG);
    self.settings.ytvcode = ko.observable(epSettings.settings.ytvcode);

    self.settings.legends = ko.observableArray();
    ko.utils.arrayForEach(normalizeArray(epSettings.settings.legends), function(theLegend) {
      self.settings.legends.push(new ArrayString(theLegend));
    });
    self.settings.layers = ko.observableArray();
    ko.utils.arrayForEach(normalizeArray(epSettings.settings.layers), function(theLayer) {
      self.settings.layers.push(new PinLayer(theLayer));
    });
  } // PinboardEntryPoint()


    // Class constructor for Tree Entry Point
  var TreeEntryPoint = function(epSettings) {
    var self = this;

    self.type = 'tree';
    self.label= ko.observable(epSettings.label || localized['name_me']);
    self.settings = { };
    self.settings.form = ko.observable(epSettings.settings.form);
    self.settings.width = ko.observable(epSettings.settings.width);
    self.settings.height = ko.observable(epSettings.settings.height);
    self.settings.head = ko.observable(epSettings.settings.head);
    self.settings.children = ko.observable(epSettings.settings.children);
    self.settings.fSize = ko.observable(epSettings.settings.fSize);
    self.settings.radius = ko.observable(epSettings.settings.radius);
    self.settings.padding = ko.observable(epSettings.settings.padding);
    self.settings.color = ko.observable(epSettings.settings.color);
  } // TreeEntryPoint()


    // Class constructor for Timeline Entry Point
  var TimeEntryPoint = function(epSettings) {
    var self = this;

    self.type = 'time';
    self.label= ko.observable(epSettings.label || localized['name_me']);
    self.settings = { };
    self.settings.date = ko.observable(epSettings.settings.date);
    self.settings.color = ko.observable(epSettings.settings.color);
    self.settings.bandHt = ko.observable(epSettings.settings.bandHt);
    self.settings.wAxisLbl = ko.observable(epSettings.settings.wAxisLbl);
    self.settings.from = ko.observable(epSettings.settings.from);
    self.settings.to = ko.observable(epSettings.settings.to);
    self.settings.openFrom = ko.observable(epSettings.settings.openFrom);
    self.settings.openTo = ko.observable(epSettings.settings.openTo);
  } // TimeEntryPoint()

    // Class constructor for Facet Flow Entry Point
  var FlowEntryPoint = function(epSettings) {
    var self = this;

    self.type = 'flow';
    self.label= ko.observable(epSettings.label || localized['name_me']);
    self.settings = { };
    self.settings.width = ko.observable(epSettings.settings.width);
    self.settings.height = ko.observable(epSettings.settings.height);

    self.settings.motes = ko.observableArray();
    ko.utils.arrayForEach(normalizeArray(epSettings.settings.motes), function(mote) {
      self.settings.motes.push(new ArrayString(mote));
    });
  } // FlowEntryPoint()

    // Class constructor for Facet Browser Entry Point
  var BrowserEntryPoint = function(epSettings) {
    var self = this;

    self.type = 'browser';
    self.label= ko.observable(epSettings.label || localized['name_me']);
    self.settings = { };
    self.settings.dateGrp = ko.observable(epSettings.settings.dateGrp);

    self.settings.motes = ko.observableArray();
    ko.utils.arrayForEach(normalizeArray(epSettings.settings.motes), function(mote) {
      self.settings.motes.push(new ArrayString(mote));
    });
  } // BrowserEntryPoint()


    // Create new "blank" layer to store in Map entry point
    // NOTES: opacity is the only property that needs double binding
  var MapLayer = function(theLayer) {
    var self = this;

    self.id        = theLayer.id;
    self.opacity   = ko.observable(theLayer.opacity).extend({ onedigit: false });
  } // MapLayer()

    // Create new "blank" layer to store in Pinboard entry point
  var PinLayer = function(theLayer) {
    var self = this;

    self.label = ko.observable(theLayer.label);
    self.file  = ko.observable(theLayer.file);
  } // PinLayer()



//=================================== MAIN OBJECT ===================================

    // PURPOSE: "Controller" Object that coordinates between Knockout and business layer
    // INPUT:   allCustomFields = array of custom fields used by Project
  var ProjectSettings = function(allCustomFields) {
    var self = this;

      // Need to copy map layers into separate arrays according to Base and Overlay
    self.baseLayers = dhpMapServices.getBaseLayers();
    self.overLayers = dhpMapServices.getOverlays();

      // PURPOSE: For debug -- spit out all of the editable data
    self.showSettings = function() {
      var currentSettings = self.bundleSettings();
      console.log("Current settings are: "+ JSON.stringify(currentSettings));
    };

      // PURPOSE: Handle user selection to save Project Settings to WP
    self.saveSettings = function() {
      $('#btnSaveSettings').button('disable');
      var currentSettings = self.bundleSettings();
      var settingsData = JSON.stringify(currentSettings);

        // Must save them in custom metabox in case user hits "Update" button in WP!
      $('#project_settings').val(settingsData);
      saveSettingsInWP(settingsData);
    };

    self.cleanSettings = function() {
      self.settingsDirty(false);
    };

      // PURPOSE: Read all of the settings and package into an object
      // RETURNS: The settings object
      // NOTES:   Need to copy some data from original settings object
    self.bundleSettings = function() {
      var projSettings = {};

      projSettings.general = {};
      projSettings.general.id = projectID;
      projSettings.general.name = savedSettings.general.name;
      projSettings.general.version = 4;

      projSettings.general.homeLabel = self.edHomeBtnLbl();
      projSettings.general.homeURL = self.edHomeURL();
      projSettings.general.mTitle = self.edMTitle();
      projSettings.general.mKey = self.edMKey();

      projSettings.motes = [];
      ko.utils.arrayForEach(self.allMotes(), function(theMote) {
        var savedMote = {};
        savedMote.name    = theMote.name;
        savedMote.type    = theMote.type;
        savedMote.delim   = theMote.delim;
        savedMote.cf      = theMote.cf;
        projSettings.motes.push(savedMote);
      } );

      projSettings.eps = [];
      ko.utils.arrayForEach(self.entryPoints(), function(theEP) {
        var savedEP = {};
        savedEP.type      = theEP.type;
        savedEP.label     = theEP.label();
        savedEP.settings  = {};
        switch(theEP.type) {
        case 'map':
          savedEP.settings.lat    = theEP.settings.lat();
          savedEP.settings.lon    = theEP.settings.lon();
          savedEP.settings.zoom   = theEP.settings.zoom();
          savedEP.settings.cluster= theEP.settings.cluster();
          savedEP.settings.size   = theEP.settings.size();
          savedEP.settings.layers = [];

          ko.utils.arrayForEach(theEP.settings.layers(), function(theLayer) {
            var savedLayer = {};

            savedLayer.opacity   = theLayer.opacity();
            savedLayer.id        = theLayer.id;
            savedEP.settings.layers.push(savedLayer);
          } );
          savedEP.settings.coordMote = theEP.settings.coordMote();
          savedEP.settings.legends = [];
          ko.utils.arrayForEach(theEP.settings.legends(), function(theLegend) {
            savedEP.settings.legends.push(theLegend.name());
          });
          break;

        case 'cards':
          savedEP.settings.titleOn = theEP.settings.titleOn();
          savedEP.settings.color = theEP.settings.color();
          savedEP.settings.defColor = theEP.settings.defColor();
          savedEP.settings.bckGrd = theEP.settings.bckGrd();
          savedEP.settings.width = theEP.settings.width();
          savedEP.settings.height = theEP.settings.height();

          savedEP.settings.content = [];
          ko.utils.arrayForEach(theEP.settings.content(), function(cMote) {
            savedEP.settings.content.push(cMote.name());
          });

          savedEP.settings.filterMotes = [];
          ko.utils.arrayForEach(theEP.settings.filterMotes(), function(fMote) {
            savedEP.settings.filterMotes.push(fMote.name());
          });

          savedEP.settings.sortMotes = [];
          ko.utils.arrayForEach(theEP.settings.sortMotes(), function(sMote) {
            savedEP.settings.sortMotes.push(sMote.name());
          });
          break;

        case 'pinboard':
          savedEP.settings.dw = theEP.settings.dw();
          savedEP.settings.dh = theEP.settings.dh();
          savedEP.settings.bckGrd = theEP.settings.bckGrd();
          savedEP.settings.imageURL = theEP.settings.imageURL();
          savedEP.settings.iw = theEP.settings.iw();
          savedEP.settings.ih = theEP.settings.ih();
          savedEP.settings.size = theEP.settings.size();
          savedEP.settings.icon = theEP.settings.icon();
          savedEP.settings.coordMote = theEP.settings.coordMote();
          savedEP.settings.animscript = theEP.settings.animscript();
          savedEP.settings.animSVG = theEP.settings.animSVG();
          savedEP.settings.ytvcode = theEP.settings.ytvcode();

          savedEP.settings.legends = [];
          ko.utils.arrayForEach(theEP.settings.legends(), function(theLegend) {
            savedEP.settings.legends.push(theLegend.name());
          });
          savedEP.settings.layers = [];
            // Create a layer object for each layer to save
          ko.utils.arrayForEach(theEP.settings.layers(), function(theLayer) {
            var savedLayer = {};
            savedLayer.label = theLayer.label();
            savedLayer.file  = theLayer.file();
            savedEP.settings.layers.push(savedLayer);
          });
          break;

        case 'tree':
          savedEP.settings.form = theEP.settings.form();
          savedEP.settings.width = theEP.settings.width();
          savedEP.settings.height = theEP.settings.height();
          savedEP.settings.head = theEP.settings.head();
          savedEP.settings.children = theEP.settings.children();
          savedEP.settings.fSize = theEP.settings.fSize();
          savedEP.settings.radius = theEP.settings.radius();
          savedEP.settings.padding = theEP.settings.padding();
          savedEP.settings.color = theEP.settings.color();
          break;

        case 'time':
          savedEP.settings.date  = theEP.settings.date();
          savedEP.settings.color = theEP.settings.color();
          savedEP.settings.bandHt = theEP.settings.bandHt();
          savedEP.settings.wAxisLbl = theEP.settings.wAxisLbl();
          savedEP.settings.from = theEP.settings.from();
          savedEP.settings.to = theEP.settings.to();
          savedEP.settings.openFrom = theEP.settings.openFrom();
          savedEP.settings.openTo = theEP.settings.openTo();
          break;

        case 'flow':
          savedEP.settings.width = theEP.settings.width();
          savedEP.settings.height = theEP.settings.height();

          savedEP.settings.motes = [];
          ko.utils.arrayForEach(theEP.settings.motes(), function(mote) {
            savedEP.settings.motes.push(mote.name());
          });
          break;

        case 'browser':
          savedEP.settings.dateGrp = theEP.settings.dateGrp();

          savedEP.settings.motes = [];
          ko.utils.arrayForEach(theEP.settings.motes(), function(mote) {
            savedEP.settings.motes.push(mote.name());
          });
          break;
        } // switch ep type
        projSettings.eps.push(savedEP);
      }); // for each EP

      projSettings.views = {};
      projSettings.views.post = {};
      projSettings.views.post.title = self.edPostTitle();
      projSettings.views.post.content = [];
      ko.utils.arrayForEach(self.postMoteList(), function (theMote) {
        projSettings.views.post.content.push(theMote.name());
      });

      projSettings.views.select = {};
      projSettings.views.select.width = self.edSelWidth();
      projSettings.views.select.widgets = [];
      ko.utils.arrayForEach(self.widgetList(), function (theWidget) {
        projSettings.views.select.widgets.push(theWidget.name());
      });
      projSettings.views.select.content = [];
      ko.utils.arrayForEach(self.selMoteList(), function (theMote) {
        projSettings.views.select.content.push(theMote.name());
      });

      projSettings.views.select.link = self.edSelLinkMt();
      projSettings.views.select.linkLabel = self.edSelLinkLbl();
      projSettings.views.select.linkNewTab = self.edSelLinkNewTab();
      projSettings.views.select.link2 = self.edSelLink2Mt();
      projSettings.views.select.link2Label = self.edSelLink2Lbl();
      projSettings.views.select.link2NewTab = self.edSelLink2NewTab();

      projSettings.views.transcript = {};
      projSettings.views.transcript.audio = self.edTrnsAudio();
      projSettings.views.transcript.video = self.edTrnsVideo();
      projSettings.views.transcript.transcript = self.edTrnsTransc();
      projSettings.views.transcript.transcript2 = self.edTrnsTransc2();
      projSettings.views.transcript.timecode = self.edTrnsTime();
      projSettings.views.transcript.source = self.edTrnsSrc();
      projSettings.views.transcript.content = [];
      ko.utils.arrayForEach(self.taxMoteList(), function (theMote) {
        projSettings.views.transcript.content.push(theMote.name());
      });

      return projSettings;
    }; // bundleSettings()

//----------------------------------- Project Info ----------------------------------

    self.settingsDirty = ko.observable(false);

    self.keyNames = ['disable'].concat(allCustomFields);

      // User-editable values
    self.edHomeBtnLbl = ko.observable('');
    self.edHomeURL = ko.observable('');
    self.edMTitle = ko.observable('the_title');
    self.edMKey = ko.observable('disable');

      // Methods
    self.setDetails = function(theDetails) {
      self.edHomeBtnLbl(theDetails.homeLabel);
      self.edHomeURL(theDetails.homeURL);
      self.edMTitle(theDetails.mTitle);
      self.edMKey(theDetails.mKey);
    };

//-------------------------------------- Motes --------------------------------------

      // Internal Methods

      // RETURNS: Array of moteNames
      // INPUT:   typeList = array of mote types for filter, or else null (to enable all types)
    function doGetMoteNames(typeList, addDisable, addTitle, addContent) {
      var moteNameArray = [];
        // Any special cases to start the mote list?
      if (addDisable) {
        moteNameArray.push('disable');
      }
      if (addTitle) {
        moteNameArray.push('the_title');
      }
      if (addContent) {
        moteNameArray.push('the_content');
      }
      ko.utils.arrayForEach(self.allMotes(), function(theMote) {
        if (typeList == null || (typeList.indexOf(theMote.type) != -1)) {
          moteNameArray.push(theMote.name);
        }
      } );
      return moteNameArray;
    };


      // User-editable values
    self.edMoteType = ko.observable(localized['choose']);
    self.edMoteName = ko.observable('');
    self.edMoteCF = ko.observable(localized['choose']);
    self.edMoteDelim = ko.observable('');

      // Insert empty custom field to front of list
    self.optionsCF = [localized['choose']].concat(allCustomFields);

      // Configurable data
    self.allMotes = ko.observableArray([]);

      // Computed data
    self.allMoteNames = ko.computed(function() {
      return doGetMoteNames(null);
    }, self);
    self.coordMoteNames = ko.computed(function() {
      return doGetMoteNames(['Lat/Lon Coordinates']);
    }, self);
    self.xyMoteNames = ko.computed(function() {
      return doGetMoteNames(['X-Y Coordinates']);
    }, self);
    self.pointerMoteNames = ko.computed(function() {
      return doGetMoteNames(['Pointer']);
    }, self);
    self.dateMoteNames = ko.computed(function() {
      return doGetMoteNames(['Date']);
    }, self);
    self.stMoteNames = ko.computed(function() {
      return doGetMoteNames(['Short Text']);
    }, self);
    self.stdMoteNames = ko.computed(function() {
      return doGetMoteNames(['Short Text'], true);
    }, self);
    self.transcMoteNames = ko.computed(function() {
      return doGetMoteNames(['Transcript'], true);
    }, self);
    self.ytMoteNames = ko.computed(function() {
      return doGetMoteNames(['YouTube'], true);
    }, self);    
    self.tstMoteNames = ko.computed(function() {
      return doGetMoteNames(['Timestamp'], true);
    }, self);
    self.imageMoteNames = ko.computed(function() {
      return doGetMoteNames(['Image'], true);
    }, self);
    self.soundMoteNames = ko.computed(function() {
      return doGetMoteNames(['SoundCloud'], true);
    }, self);
      // for Titles of Markers
    self.mTitleMoteNames = ko.computed(function() {
      return doGetMoteNames(['Short Text', 'Long Text'], false, true);
    }, self);
      // Any textual value, incl the_title and 'disable'
    self.anyTxtDMoteNames = ko.computed(function() {
      return doGetMoteNames(['Short Text', 'Long Text'], true, true, false);
    }, self);
      // Any value that can be displayed as card content
    self.cardContentMoteNames = ko.computed(function() {
      return doGetMoteNames(['Short Text', 'Long Text', 'Image', 'Date'], false, true, true);
    }, self);
      // Any mote value that can be processed via sorting and filtering
    self.compMoteNames = ko.computed(function() {
      return doGetMoteNames(['Short Text', 'Long Text', 'Date'], false, false, false);
    }, self);

      // Methods

      // PURPOSE: Create new mote definition via user interface
    self.createMote = function() {
        // Make sure valid CF and mote type chosen
      if (self.edMoteType() === localized['choose'] || self.edMoteCF()  === localized['choose']) {
        $("#mdl-def-mote").dialog({
          modal: true,
          buttons: {
            OK: function() {
              $(this).dialog('close');
            }
          }
        });
        return;        
      }

        // Abort everything if there are no custom motes
      if (self.optionsCF.length == 0) {
        $("#mdl-no-cfs").dialog({
          modal: true,
          buttons: {
            OK: function() {
              $(this).dialog('close');
            }
          }
        });
        return;
      }

      var newName = self.edMoteName();

        // Make sure no illegal characters used
      if (/[^\d\w\- ]/.test(newName)) {
        $("#mdl-mote-name-badchars").dialog({
          modal: true,
          buttons: {
            OK: function() {
              $(this).dialog('close');
            }
          }
        });
        return;
      }

        // Only allow if a name has been provided
      if (newName !== '') {
          // Don't allow mote names over 32 characters
        if (newName.length > 32) {
            $("#mdl-mote-name-too-long").dialog({
              modal: true,
              buttons: {
                OK: function() {
                  $(this).dialog( "close" );
                }
              }
            });

        } else {
            // Only add if name is unique
          var found = ko.utils.arrayFirst(self.allMotes(), function(mote) {
            return mote.name == newName;
          });
          if (found == null) {
            self.allMotes.push(new Mote(newName, self.edMoteType(), self.edMoteCF(), self.edMoteDelim()));
              // reset GUI default values
            self.edMoteName('');
            self.edMoteType(localized['choose']);
            self.edMoteCF(localized['choose']);
            self.edMoteDelim('');

            self.settingsDirty(true);
          }
        }
      }
    }; // createMote()

      // PURPOSE: Remove any reference of theMote from Project definitions
    self.extractMote = function(theMote) {
      var moteName = theMote.name;

      if (self.edMTitle() === moteName) { self.edMTitle('the_title'); }

        // Remove all occurrences of mote name from all Entry Point
      ko.utils.arrayForEach(self.entryPoints(), function(theEP) {
        switch (theEP.type) {
        case 'map':
          if (theEP.settings.coordMote() == moteName) {
            theEP.settings.coordMote('');
          }
          theEP.settings.legends.remove(function(mote) { return mote.name() === moteName; });
          break;
        case 'cards':
          if (theEP.settings.color() == moteName) { theEP.settings.color(''); }

          theEP.settings.content.remove(function(mote) { return mote.name() === moteName; });
          theEP.settings.filterMotes.remove(function(mote) { return mote.name() === moteName; });
          theEP.settings.sortMotes.remove(function(mote) { return mote.name() === moteName; });
          break;
        case 'pinboard':
          if (theEP.settings.coordMote() == moteName) { theEP.settings.coordMote(''); }
          theEP.settings.legends.remove(function(mote) { return mote.name() === moteName; });
          break;
        case 'tree':
          if (theEP.settings.children() == moteName) {  theEP.settings.children(''); }
          if (theEP.settings.color() == moteName) { theEP.settings.color(''); }
          break;
        case 'time':
          if (theEP.settings.date() == moteName) {  theEP.settings.date(''); }
          if (theEP.settings.color() == moteName) { theEP.settings.color(''); }
          break;
        case 'flow':
        case 'browser':
          theEP.settings.motes.remove(function(mote) { return mote.name() === moteName; });
          break;
        }
      });

      if (self.edSelLinkMt() === moteName) { self.edSelLinkMt('disable'); }
      if (self.edSelLink2Mt()=== moteName) { self.edSelLink2Mt('disable'); }
      self.selMoteList.remove(function(mote) { return mote.name() === moteName; });

      if (self.edPostTitle() === moteName) { self.edPostTitle(''); }
      self.postMoteList.remove(function(mote) { return mote.name() === moteName; });

      self.taxMoteList.remove(function(mote) { return mote.name() === moteName; });

      if (self.edTrnsAudio()  === moteName)  { self.edTrnsAudio('disable'); }
      if (self.edTrnsVideo()  === moteName)  { self.edTrnsVideo('disable'); }
      if (self.edTrnsTransc() === moteName)  { self.edTrnsTransc('disable'); }
      if (self.edTrnsTransc2() === moteName) { self.edTrnsTransc2('disable'); }
      if (self.edTrnsTime()  === moteName)   { self.edTrnsTime('disable'); }
      if (self.edTrnsSrc()  === moteName)    { self.edTrnsSrc('disable'); }
    }; // extractMote()


      // PURPOSE: Handle deleting a mote definition (and all references to it)
    self.delMote = function(theMote) {
      var moteName = theMote.name;

      $( "#mdl-del-mote" ).dialog({
        resizable: false,
        height:'auto',
        width: 'auto',
        modal: true,
        dialogClass: 'wp-dialog',
        draggable: false,
        buttons: [
          {
            text: localized['delete'],
            click: function() {
              self.extractMote(theMote);

                // Delete Taxonomy/Legend if it exists
              if (theMote.type == 'Short Text') {
                deleteHeadTermInWP(moteName);
              }

              self.allMotes.remove(theMote);

              self.settingsDirty(true);
              $(this).dialog('close');
            }
          },
          {
            text: localized['cancel'],
            click: function() {
              $(this).dialog('close');
            }
          } 
        ]
      });
    }; // delMote()

      // PURPOSE: Create new mote definition programmatically (not via user interface)
    self.setMote = function(name, type, customField, delim) {
      self.allMotes.push(new Mote(name, type, customField, delim));
    }; // setMote()

      // PURPOSE: Handle user selection to edit a Mote definition
      // NOTE:    If the user has configured a Legend for a Short Text mote, changing the name
      //            of the Mote will cause it to get lost. This code does not handle that case at present.
    self.editMote = function(theMote, event) {
      $('#mdl-edit-mote-title').text('Edit definition for '+theMote.name);
      $('#mdl-edit-mote #edMoteModalName').val(theMote.name);
      $('#mdl-edit-mote #edMoteModalType').val(theMote.type);
      $('#mdl-edit-mote #edMoteModalCF').val(theMote.cf);
      $('#mdl-edit-mote #edMoteModalDelim').val(theMote.delim);

      if (theMote.type == 'Short Text') {
        $('#mdl-edit-mote #edMoteModalSTWarn').show();
      } else {
        $('#mdl-edit-mote #edMoteModalSTWarn').hide();
      }

      var newModal = $('#mdl-edit-mote');
      newModal.dialog({
          width: 370,
          height: 350,
          modal : true,
          autoOpen: false,
          dialogClass: 'wp-dialog',
          draggable: false,
          buttons: [
            {
              text: localized['cancel'],
              click: function() {
                $(this).dialog('close');
              }
            },
            {
              text: localized['save'],
              click: function() {
                self.extractMote(theMote);
                self.allMotes.remove(theMote);
                var newMote = new Mote($('#mdl-edit-mote #edMoteModalName').val(),
                                  $('#mdl-edit-mote #edMoteModalType').val(),
                                  $('#mdl-edit-mote #edMoteModalCF').val(),
                                  $('#mdl-edit-mote #edMoteModalDelim').val());
                self.allMotes.push(newMote);
                self.settingsDirty(true);
                $(this).dialog('close');
              }
            }
          ]
      });
      newModal.dialog('open');
    }; // editMote()


      // PURPOSE: Handle user selection to configure associations of legend category: color, Maki icon, or PNG icon
      // INPUT:   theMote = Mote data structure
      //          event = JS event for button
      // NOTES:   Need to have large fixed width to accommodate category legend data, loaded asynchronously
      //          Hierarchical sortable is from http://dbushell.com/2012/06/17/nestable-jquery-plugin/
      //          Category data is array in standard WP format (http://codex.wordpress.org/Function_Reference/get_terms#Return_Values_2)
      //            term_id   = ID of this term
      //            name      = label for this term
      //            parent    = ID of parent term, or 0
      //            count     = # times value/tag used
      //            icon_url  = visual metadata (either #number for color or .maki- for icon)
      //          Send back a subset to dhpCreateTaxTerms (adding term_order: see below)
      //          The HTML data-id attribute will contain id of category field
      //          If "As Icons" is selected, li with .maki-icon are inserted
      //          If "As Colors" is selected, .color-box div's are inserted and style="background-color" is set
      //          Do not store visual data in data- attributes since they cannot be rewritten dynamically:
      //            http://www.learningjquery.com/2011/09/using-jquerys-data-apis/
    self.configCat = function(theMote, event) {
        // Default visualization type for this Legend #=color, .=maki-icon, @=PNGImage
      var defVizType='colors';
        // The taxonomic ID of the head term of the Legend
      var headTermID;

        // Remove previous legend data from modal
      $('#mdl-config-cat #category-tree .dd-list').empty();
        // Make sure wait message is visible
      $('#mdl-config-cat .wait-message').removeClass('hide');
      $('#mdl-config-cat-title').text('Legend configuration for '+theMote.name);

        // Make sure it is initially enabled
      $('#add-new-term').button({ disabled: false });

        // Are there any user-defined PNG image icons?
      if (pngImages.length > 0) {
        $('#mdl-config-cat #use-png').prop('disabled', false);
      } else {
        $('#mdl-config-cat #use-png').prop('disabled', true);
      }

      var newModal = $('#mdl-config-cat');
      newModal.dialog({
          width: 500,
          height: 500,
          modal : true,
          autoOpen: false,
          dialogClass: 'wp-dialog',
          draggable: false,
          buttons: [
            {
              text: localized['cancel'],
              click: function() {
                $(this).dialog('close');
              }
            },
            {
              text: localized['save'],
              click: function() {
                  // Save reorganized data: only need to gather term_id, parent, term_order, icon_url
                  // Need to convert from nestable's format to flat format used by WP:
                  //    Disregard old parent field, as data- attributes not updated and user may have changed hierarchy
                var savedTree = $('#category-tree').nestable('serialize');
                var flatArray = [], termOrder=1;
                ko.utils.arrayForEach(savedTree, function(treeItem) {
                  var newItem = {};
                  var domItem;
                  newItem.term_id = treeItem.id;
                  newItem.parent = headTermID;
                  newItem.term_order = termOrder++;
                    // Extract data from the visual div for this item
                  domItem = $('li[data-id="'+treeItem.id+'"] .viz-div');
                  newItem.icon_url = getVizData(domItem);
                    // Save this (potential) parent before doing any children
                  flatArray.push(newItem);
                    // Go through any children of this item
                  if (treeItem.children) {
                    ko.utils.arrayForEach(treeItem.children, function(child) {
                      var childItem = {};
                      childItem.term_id = child.id;
                      childItem.parent = treeItem.id;
                      childItem.term_order = termOrder++;
                        // Extract data from the visual div for this item
                      domItem = $('li[data-id="'+child.id+'"] .viz-div');
                      childItem.icon_url = getVizData(domItem);
                      flatArray.push(childItem);
                    }); // arrayForEach
                  } // if treeItem.children
                }); // arrayForEach
                
                //console.log(JSON.stringify(flatArray));
                saveLegendValuesInWP(theMote.name, flatArray);

                  // Close modal on assumption that save works
                $(this).dialog('close');
              }
            }
          ]
      }); // newModal.dialog

        // RETURNS: Color in hex format
        // NOTES:   jQuery converts color values to rgb() even if given as hex
        //          DH Press display code assumes in hex format beginning with '#'
      function formatColor(colorStr) {
        if (colorStr.charAt(0)=='#') {
          return colorStr;
        }
          // Error in format -- return black
        if (colorStr.substring(0,3) != 'rgb') {
          return '#000000';
        }
        var digits;

        if (colorStr.substring(0,4) == 'rgba') {
          digits = /rgba\((\d+), (\d+), (\d+), (\d+)\)/.exec(colorStr);
        } else {
          digits = /rgb\((\d+), (\d+), (\d+)\)/.exec(colorStr);
        }

        var red = componentToHex(digits[1]);
        var green = componentToHex(digits[2]);
        var blue = componentToHex(digits[3]);

        return '#' + red + green + blue;
      } // formatColor()

        // PURPOSE: Converts r/g/b component to hex value
        // NOTES:   Adapted from example by Tim Down (http://stackoverflow.com/questions/5623838/rgb-to-hex-and-hex-to-rgb)
        //          Fixes missing zero bug
        // RETURNS  Hex value of color component
      function componentToHex (c) {
        var hex = parseInt(c).toString(16);
        return hex.length == 1 ? "0" + hex : hex;
      }

        // PURPOSE: Given a DOM element, parse its maki-icon class
        // RETURN:  Maki-icon class prefixed by '.'
      function getIconClass(domElement) {
        var cssClasses = $(domElement).attr("class");
        var nameArray = cssClasses.split(' ');
        var iconClass = '';
        ko.utils.arrayForEach(nameArray, function(name) {
          if (name !== 'maki-icon' && name !== 'selected') {
            iconClass = '.'+name;
          }
        });
        return iconClass;
      } // getIconClass

        // PURPOSE: Given a DOM element, parse its viz-data for the icon_url value
        // NOTE:    domItem is DIV of class viz-div
      function getVizData(domItem) {
          if ($(domItem).hasClass('color-box')) {
            //console.log('Item: '+$(domItem).closest('.dd3-content').text()+', Color: '+formatColor($(domItem).css('background-color')));
            return formatColor($(domItem).css('background-color'));
          } else if ($(domItem).hasClass('maki-icon')) {
            return getIconClass(domItem);
          } else {
            return '@'+$(domItem).attr('alt');
          }
      } // getVizData()

        // PURPOSE: Find the URL for the icon
      function getPNGSrc(iconName) {
        var pngItem;
        if (iconName === 'null') {
          return '#';
        }
        pngItem = ko.utils.arrayFirst(pngImages, function(thePNG) {
          return iconName === thePNG.title;
        });
        return (pngItem === null) ? '#' : pngItem.url;
      } // getPNGSrc()

          // PURPOSE: Create HTML string for visual data according to format
          // NOTES:   If setDefault, set defVizType default according to data we're parsing
      function getVizHTML(vizData, setDefault) {
          // Color patch is default
        if (vizData == null || vizData=='') {
          if (setDefault) { defVizType='colors'; }
          //console.log('null: ' + vizData);
          return '<div class="viz-div color-box" style="background-color: #888888"></div>';
        }

        switch (vizData.charAt(0)) {
        case '#':
          if (setDefault) { defVizType='colors'; }
          return '<div class="viz-div color-box" style="background-color:'+vizData+'"></div>';
        case '.':
          if (setDefault) { defVizType='icons'; }
            // We need to ignore the leading '.' of classname
          return '<div class="viz-div maki-icon '+vizData.substring(1)+'"></div>';
        case '@':
          if (setDefault) { defVizType='pngs'; }
          return '<img class="viz-div" alt="'+vizData.substring(1)+'" src="'+getPNGSrc(vizData.substring(1))+'"/>';

          // Need to handle no data or incorrectly formatted data -- just make it an empty div
        default:
          if (setDefault) { defVizType='colors'; }
          return '<div class="viz-div"></div>';
        }
      } // getVizHTML()

        // FUNCTION: Create default visualization data based on current setting of icons-vs-color radio buttons
        // RETURNS:  Object with properties 
      function getDefaultViz() {
        var vizObj = {};
        var vizType = $('input[name="viz-type-setting"]:checked').val();

        switch (vizType) {
        case 'icons':
          vizObj.data = '.circle';
          break;
        case 'colors':
          vizObj.data = '#888888';
          break;
        case 'pngs':
          var pngName;
          if (pngImages.length) {
            pngName = pngImages[0]['title'];
          } else {
            pngName = 'null';
          }
          vizObj.data = '@'+pngName;
          break;
        }
        vizObj.html=getVizHTML(vizObj.data, false);
        return vizObj;
      } // getDefaultViz()

        // PURPOSE: Handle user selecting Assign div
      function handleAssign(e) {
        e.stopPropagation();

          // "this" will point to select-legend div -- need to go up 2 levels to get li element
        var selLegDiv = this;
        var liElement = $(selLegDiv).parent().parent();
        var moteName = $(liElement).data('name');

          // which modal to use depends on setting of "viz-type-setting" radio button
        var useSetting = $('input[name="viz-type-setting"]:checked').val();

        switch (useSetting) {
        case 'icons':
            // Replace current viz type with icon if necessary
          var iconListDiv = $('.maki-icon:first', selLegDiv);
          if (iconListDiv.length == 0) {
            $('.viz-div:first', liElement).remove();
            $('.select-legend:first', liElement).append(getVizHTML('.circle', false)); // insert default icon
            iconListDiv = $('.maki-icon:first', selLegDiv);
          }
          var selIcon = getIconClass(iconListDiv);

          $('#mdl-select-icon-title').text('Select icon for '+moteName);
          var newModal = $('#mdl-select-icon');
          newModal.dialog({
              width: 342,
              height: 300,
              modal : true,
              autoOpen: false,
              dialogClass: 'wp-dialog',
              draggable: false,
              buttons: [
                {
                  text: localized['cancel'],
                  click: function() { $(this).dialog('close'); }
                },
                {
                  text: localized['save'],
                  click: function() {
                      // Determine selected icon
                    selIcon = $('#mdl-select-icon #select-icon-list .selected');
                    selIcon = getIconClass(selIcon);
                      // Create new HTML indicating selection and replace old
                    $('.viz-div:first', liElement).remove();
                    $('.select-legend:first', liElement).append(getVizHTML(selIcon, false));
                    $(this).dialog('close');
                  }
                }
              ]
          });

            // Remove any previous selection, highlight current selection
          $('#mdl-select-icon #select-icon-list li').removeClass('selected');
          $('#select-icon-list '+selIcon).addClass('selected');

            // Remove any old binding for handling selection, bind this
          $('#mdl-select-icon #select-icon-list').off('click');
          $('#mdl-select-icon #select-icon-list').click(function(evt) {
              // Did user select an icon?
            var targetIcon = $(evt.target).closest(".maki-icon");
                // If none found (selected outside one), abort
            if (targetIcon == null || targetIcon == undefined) {
                return;
            }
            targetIcon = $(targetIcon).get(0);
            if (targetIcon == null || targetIcon == undefined) {
                return;
            }
              // Remove selected class from previous selection, add to new one
            $('#mdl-select-icon #select-icon-list li').removeClass('selected');
            $(targetIcon).addClass('selected');
          });

          newModal.dialog('open');
          break;

        case 'colors':
            // Replace with color-box if necessary
          var colorBoxDiv = $('.color-box', selLegDiv);
          if (colorBoxDiv.length == 0) {
            $('.viz-div:first', liElement).remove();
            $('.select-legend:first', liElement).append(getVizHTML(null, false));
            colorBoxDiv = $('.color-box:first', selLegDiv);
          }
          var initColor = $(colorBoxDiv).css('background-color');

          // Initialize Iris color picker
          // NOTE: Requires Wordpress 3.5+ (uses built-in Iris library)
        var colorPicker = $('#color-picker');
        colorPicker.iris({
            change: function(event, ui) {
              $(colorBoxDiv).css('background-color', ui.color.toString());
            },
            hide: false,
            width: 350,
            palettes: true,
            target: '#mdl-select-color'
        });
        colorPicker.iris('color', initColor);
        colorPicker.iris('show');

        var newModal = $('#mdl-select-color');
          newModal.dialog({
              width: 400,
              height: 480,
              modal : true,
              autoOpen: false,
              dialogClass: 'wp-dialog',
              draggable: false,
              buttons: [
                {
                  text: localized['cancel'],
                  click: function() { 
                    $(colorBoxDiv).css('background-color', initColor);
                    colorPicker.iris('hide');
                    newModal.dialog('close');
                  }
                },
                {
                  text: localized['save'],
                  click: function() {
                    colorPicker.iris('hide');
                    newModal.dialog('close');
                  }
                }
              ]
          });
          newModal.dialog('open');

          break;

        case 'pngs':
            // Replace with png image if necessary
          var pngBoxDiv = $('img', selLegDiv);
          if (pngBoxDiv.length == 0) {
            $('.viz-div:first', liElement).remove();
            $('.select-legend:first', liElement).append(getVizHTML(null, false));
            pngBoxDiv = $('img', selLegDiv);
          }
          var initTitle = $(pngBoxDiv).attr('alt');

          $('#mdl-select-png-title').text('Select PNG image for '+moteName);
          var newModal = $('#mdl-select-png');
          newModal.dialog({
              width: 342,
              height: 300,
              modal : true,
              autoOpen: false,
              dialogClass: 'wp-dialog',
              draggable: false,
              buttons: [
                {
                  text: localized['cancel'],
                  click: function() { newModal.dialog('close'); }
                },
                {
                  text: localized['save'],
                  click: function() {
                      // Determine selected icon
                    var pngTitle = '@'+$('#mdl-select-png #select-png-list .selected').attr('alt');
                      // Create new HTML indicating selection and replace old
                    $('.viz-div:first', liElement).remove();
                    $('.select-legend:first', liElement).append(getVizHTML(pngTitle, false));
                    newModal.dialog('close');
                  }
                }
              ]
          });

            // Build list of PNG images and insert into modal
          $('#mdl-select-png #select-png-list').empty();
          ko.utils.arrayForEach(pngImages, function(thePNG) {
            var newHTML = '<li><img alt="'+thePNG.title+'" class="'+thePNG.title+'" src="'+thePNG.url+'"/></li>';
            $('#select-png-list').append(newHTML);
          });

          $('#select-png-list .'+initTitle).addClass('selected');

            // Remove any old binding for handling selection, bind this
          $('#mdl-select-png #select-png-list').off('click');
          $('#mdl-select-png #select-png-list').click(function(evt) {
              // Did user select a PNG image?
            var targetPNG = $(evt.target).closest("img");
                // If none found (selected outside one), abort
            if (targetPNG == null || targetPNG == undefined) {
                return;
            }
            targetPNG = $(targetPNG).get(0);
            if (targetPNG == null || targetPNG == undefined) {
                return;
            }
              // Remove selected class from previous selection, add to new one
            $('#select-png-list img').removeClass('selected');
            $(targetPNG).addClass('selected');
          });

          newModal.dialog('open');

          break;
        } // switch assign type
      } // handleAssign()


        // INPUT:  termArray is JSON object returned by getLegendValuesInWP
        // NOTES:  termArray contains entry for head term, which we don't wish to display
        //          (or cause extra level in hierarchy), so we must find it and exclude from
        //          building of nested
      function unpackLegendData(termArray) {
          // Ensure IDs are integers (not strings) and find head term's ID
        ko.utils.arrayForEach(termArray, function(thisTerm) {
          if (typeof(thisTerm.term_id) === 'string') {
            thisTerm.term_id = parseInt(thisTerm.term_id);
          }
          if (typeof(thisTerm.parent) === 'string') {
            thisTerm.parent = parseInt(thisTerm.parent);
          }
          if (thisTerm.parent == 0) {
            headTermID = thisTerm.term_id;
          }
        });

          // Create data attributes for most term data in the HTML
        ko.utils.arrayForEach(termArray, function(thisTerm) {
            // Don't include the head term (Legend parent) itself
          if (thisTerm.term_id != headTermID) {
            var termViz = getVizHTML(thisTerm.icon_url, true);
            var termElement = $('<li class="dd-item dd3-item" data-id="'+thisTerm.term_id+'" data-name="'+
                  thisTerm.name+'" data-parent="'+thisTerm.parent+'"> <div class="dd-handle dd3-handle"></div><div class="dd3-content">'+
                  thisTerm.name+' ('+thisTerm.count+') '+'&nbsp;&nbsp;<div class="select-legend"><span class="assign">Assign</span> '+termViz+'</div></div></li>');

              // If parent is the head term (Legend parent), check to see if any items exist which are children of this parent
            if (thisTerm.parent == headTermID) {
                // Are there any pre-existing children that can be detached?
              var childElements = $('li[data-parent="'+thisTerm.term_id+'"]').detach();
                // Put at the end of this item (their parent)
              if (childElements.length > 0) {
                  // Create new nesting for children, append saved children to it, and append to parent
                var sublist = $('<ol class="dd-list"></ol>');
                $(sublist).append(childElements);
                $(termElement).append(sublist);
              }
                // Append this item and any "rescued" children to top level of hierarchy
              $('#category-tree > .dd-list').append(termElement);

              // Term has a parent (it may or may not have been created already)
            } else {
                // Search to see if parent HTML already exists
              var parentElement = $('li[data-id="'+thisTerm.parent+'"]');
                // Append under if so
              if (parentElement.length > 0) {
                  // Check to see if parent already has a nested sublist yet; create if not
                var sublist = $('.dd-list', parentElement);
                if (sublist.length == 0) {
                  $(parentElement).append('<ol class="dd-list"></ol>');
                }
                $('.dd-list', parentElement).append(termElement);

                // Otherwise, just append for now to main list -- parent will arrive later
              } else {
                $('#category-tree > .dd-list').append(termElement);
              }
            }
          }
        }); // arrayForEach

          // Bind code for all assignment buttons
        $('#category-tree .select-legend').click(handleAssign);

          // Bind code to handle adding a new term (only once!)
        $('#add-new-term').off('click');
        $('#add-new-term').click(function() {
          var newTerm = $('#ed-new-term').val();
            // Only attempt if a term is given
          if (newTerm != null && newTerm != '') {
            var defaultViz = getDefaultViz();
            function insertNewTerm(newTermID) {
              $('#add-new-term').button({ disabled: false });
                // termID 0 is special error code
              if (newTermID) {
                  // Insert new item (without parent) at top of list, binding Assign code to section
                var totalElement = $('<li class="dd-item dd3-item" data-id="'+newTermID+'" data-name="'+
                    newTerm+'" data-parent="0"> <div class="dd-handle dd3-handle"></div><div class="dd3-content">'+
                    newTerm+' (0) '+'&nbsp;&nbsp;<div class="select-legend">Assign '+defaultViz.html+'</div></div></li>');
                $('.select-legend', totalElement).click(handleAssign);
                $('#category-tree > .dd-list').prepend(totalElement);
                  // Clear out new term field
                $('#ed-new-term').val('');
              } else {
                $("#mdl-server-err").dialog({
                  modal: true,
                  buttons: {
                    OK: function() {
                      $(this).dialog("close");
                    }
                  }
                });
              }
            } // insertNewTerm()
              // abort if the name already exists -- double adds sometimes happen: server hiccups?
            var candidates=$('.dd-item[data-name="'+newTerm+'"]');
            if (candidates.length < 1) {
              $('#add-new-term').button({ disabled: true });
              dhpCreateTermInTax(newTerm, theMote.name, insertNewTerm);
            }
          } // if new term
        });

          // Bind code to reset viz data
        $('#viz-type-reset').off('click');
        $('#viz-type-reset').click(function() {

            // Display random/gradient color reset options if type is set to colors
          if ($('input:radio[value="colors"]').prop('checked')) {
              // construct new default visualization data
            var defaultViz = getDefaultViz();

              // Defines initial gradient color range using random colors
            var gradientRange = [randomColor(), randomColor()];

              // Loops through each legend item and updates color according to type (clear, random, or gradient)
            function updateColors (type) {
              if (type == 'gradient') {
                var rainbow = new Rainbow();
                rainbow.setSpectrum(gradientRange[0], gradientRange[1]);

                var gradientSize = $('#category-tree .dd3-item').length - 1;
                rainbow.setNumberRange(0, gradientSize);
              }
              
              $('#category-tree .dd3-item').each( function(index) {
                  // Remove and replace all viz-div elements (don't alter child nodes!)
                $('.viz-div:first', this).remove();
                $('.select-legend:first', this).append(defaultViz.html);

                switch (type) {
                  case 'random' :
                    var color = randomColor();
                    break;
                  case 'gradient' :
                    var color = '#' + rainbow.colourAt(index);
                    break;
                  default :
                    var color = defaultViz.data;
                    break;
                }

                $('.dd3-content .select-legend .color-box', this).css('background-color', color);
              });
                      
              newModal.dialog('close');
            }

            var newModal = $('#mdl-reset-color-options');
            newModal.dialog({
                width: 450,
                height: 210,
                modal : true,
                autoOpen: false,
                dialogClass: 'wp-dialog',
                draggable: false,
                buttons: [
                  {
                    text: localized['cancel'],
                    click: function() { newModal.dialog('close'); }
                  },
                  {
                    text: localized['clear_all'],
                    click: function() { updateColors(); }
                  },
                  {
                    text: localized['random_colors'],
                    click: function() { updateColors('random'); }
                  },
                  {
                    text: localized['gradient'],
                    click: function() { updateColors('gradient'); }
                  }
                ]
            });
            newModal.dialog('open');


            var colorBoxes = $('#mdl-reset-color-options #gradient-colors .color-box');

              // Sets initial colors for each color box
            colorBoxes.each(function(index) {
              $(this).css('background-color', gradientRange[index]);
            });

            colorBoxes.click(function () {
              var colorBox = this;
              var initColor = $(this).css('background-color');

                // Finds index of selected color box to correspond with gradientRange array
              var boxIndex = colorBoxes.index(this);

              var colorRange = $('#color-range');
              colorRange.iris({
                change: function(event, ui) {
                  $(colorBox).css('background-color', ui.color.toString());
                  gradientRange[boxIndex] = ui.color.toString();
                },
                hide: false,
                width: 350,
                palettes: true,
                target: '#mdl-select-color'
              });
              colorRange.iris('color', initColor);
              colorRange.iris('show');

              var colorModal = $('#mdl-select-color');
              colorModal.dialog({
                  width: 400,
                  height: 480,
                  modal : true,
                  autoOpen: false,
                  dialogClass: 'wp-dialog',
                  draggable: false,
                  buttons: [
                    {
                      text: localized['cancel'],
                      click: function() { 
                        $(colorBox).css('background-color', initColor);
                        colorRange.iris('hide');
                        colorModal.dialog('close');
                      }
                    },
                    {
                      text: localized['save'],
                      click: function() {
                        colorRange.iris('hide');
                        colorModal.dialog('close');
                      }
                    }
                  ]
              });
              colorModal.dialog('open');
            });

          }
            // Otherwise, simply reset values
          else {
              // construct new default visualization data
            var defaultViz = getDefaultViz();
  
            $('#category-tree .dd3-item').each( function() {
                // Remove and replace all viz-div elements (don't alter child nodes!)
              $('.viz-div:first', this).remove();
              $('.select-legend:first', this).append(defaultViz.html);
            });
          }
        }); // click()

          // After all material inserted, activate nestable-sortable GUI
        $('#category-tree').nestable( { maxDepth: 2 } );

          // Set default for icons / color
        $('input:radio[value="icons"]').prop('checked', defVizType === 'icons');
        $('input:radio[value="colors"]').prop('checked', defVizType === 'colors');
        $('input:radio[value="pngs"]').prop('checked', defVizType === 'pngs');

          // Remove wait message
        $('#mdl-config-cat .wait-message').addClass('hide');
      } // unpackLegendData()

        // Show the modal with wait message while loading happening
      $('#mdl-config-cat .wait-message').removeClass('hide');
      newModal.dialog('open');

        // Asynchronous AJAX load which needs to modify HTML
      getLegendValuesInWP(theMote.name, theMote.cf, theMote.delim, unpackLegendData);
    }; // configCat()

      // PURPOSE: Handle selection to rebuild the Legend/Category for the mote
      // INPUT:   theMote = Mote data structure
      //          event = JS event for button
    self.rebuildCat = function(theMote, event) {
      $( "#mdl-rebuild-cat" ).dialog({
        resizable: false,
        height:'auto',
        width: 'auto',
        modal: true,
        dialogClass: 'wp-dialog',
        draggable: false,
        buttons: [
          {
            text: localized['rebuild'],
            click: function() {
                // Disable button until AJAX call returns
              $('#btnRebuildMote').button('disable');
              rebuildLegendValuesInWP(theMote.name, theMote.cf, theMote.delim);

              $(this).dialog('close');
            }
          },
          {
            text: localized['cancel'],
            click: function() {
              $(this).dialog('close');
            }
          }
        ]
      });
    };


//-------------------------------------- Entry Points --------------------------------------

      // User-editable values
    self.entryPoints = ko.observableArray([]);

      // Methods

      // PURPOSE: Handle user selection to create new blank map entry point
    self.createMapEP = function() {
      var _blankMapEP = {
        type: 'map',
        label: localized['name_me'],
        settings: {
            lat: 0, lon: 0, zoom: 10, cluster: false, size: 'm',
            layers: [ { id: 0, name: '', opacity: 1, mapType: '', mapTypeId: '' } ],
            coordMote: '',
            legends: [ ]
        }
      };
      self.setEP(_blankMapEP);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new blank cards entry point
    self.createCardsEP = function() {
      var _blankCardsEP = {
        type: 'cards',
        label: localized['name_me'],
        settings: {
          titleOn: true,
          color: 'disable',
          defColor: '#00BFFF',
          bckGrd: '',
          width: 'med-width',
          height: 'med-height',
          content: [],
          filterMotes: [],
          sortMotes: []
        }
      };
      self.setEP(_blankCardsEP);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new pinboard entry point
    self.createPinEP = function() {
      var _blankPinEP = {
        type: 'pinboard',
        label: localized['name_me'],
        settings: {
          dw: 500,
          dh: 500,
          bckGrd: '',
          imageURL: '',
          iw: 500,
          ih: 500,
          icon: 'circle',
          size: 'm',
          coordMote: '',
          animscript: '',
          animSVG: '',
          ytvcode: '',
          legends: [ ],
          layers: [ ]
        }
      };
      self.setEP(_blankPinEP);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new Tree entry point
    self.createTreeEP = function() {
      var _blankTreeEP = {
        type: 'tree',
        label: localized['name_me'],
        settings: {
          form: '',
          width: 1000,
          height: 1000,
          head: '',
          children: '',
          fSize: '10',
          radius: '4',
          padding: '120',
          color: ''
        }
      };
      self.setEP(_blankTreeEP);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new Timeline entry point
    self.createTimeEP = function() {
      var _blankTimeEP = {
        type: 'time',
        label: localized['name_me'],
        settings: {
          date: '',
          color: '',
          bandHt: '13',
          wAxisLbl: '32',
          from: '',
          to: '',
          openFrom: '',
          openTo: ''
        }
      };
      self.setEP(_blankTimeEP);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new pinboard entry point
    self.createFlowEP = function() {
      var _blankFlowEP = {
        type: 'flow',
        label: localized['name_me'],
        settings: {
          width: 500,
          height: 400,
          motes: []
        }
      };
      self.setEP(_blankFlowEP);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new pinboard entry point
    self.createBrowserEP = function() {
      var _blankBrowserEP = {
        type: 'browser',
        label: localized['name_me'],
        settings: {
          dateGrp: 'year',
          motes: []
        }
      };
      self.setEP(_blankBrowserEP);
      self.settingsDirty(true);
    };

      // PURPOSE: Programmatically add an entry point to the settings (not via user interface)
    self.setEP = function(theEP) {
      var newEP;
      switch (theEP.type) {
      case 'map':
        newEP = new MapEntryPoint(theEP);
        break;
      case 'cards':
        newEP = new CardsEntryPoint(theEP);
        break;
      case 'pinboard':
        newEP = new PinboardEntryPoint(theEP);
        break;
      case 'tree':
        newEP = new TreeEntryPoint(theEP);
        break;
      case 'time':
        newEP = new TimeEntryPoint(theEP);
        break;
      case 'flow':
        newEP = new FlowEntryPoint(theEP);
        break;
      case 'browser':
        newEP = new BrowserEntryPoint(theEP);
        break;
      }
      self.entryPoints.push(newEP);
    };

      // PURPOSE: Handle user selection to delete this entry point
    self.delEP = function(theEP) {
      $('#mdl-del-ep').dialog({
        resizable: false,
        height:160,
        modal: true,
        dialogClass: 'wp-dialog',
        draggable: false,
        buttons: [
          {
            text: localized['delete'],
            click: function() {
              self.entryPoints.remove(theEP);
              $(this).dialog('close');
              self.settingsDirty(true);
            }
          },
          {
            text: localized['cancel'],
            click: function() {
              $(this).dialog('close');
            }
          }
        ]
      });
    }; // delEP()

      // PURPOSE: Direct Knockout to which template to use to display this entry point
    self.calcEPTemplate = function(theEP) {
      switch (theEP.type) {
      case 'map':
        return 'ep-map-template';
      case 'cards':
        return 'ep-cards-template';
      case 'pinboard':
        return 'ep-pin-template';
      case 'tree':
        return 'ep-tree-template';
      case 'time':
        return 'ep-time-template';
      case 'flow':
        return 'ep-flow-template';
      case 'browser':
        return 'ep-browser-template';
      }
    }; // calcEPTemplate()

      // No longer used?
    self.maxEPindex = function() {
      return self.entryPoints().length - 1;
    };

      // PURPOSE: Handle user selection to add map overlay
    self.addMapLayer = function(theEP) {
      var _blankLayer = {
        id: 0, name: '', opacity: 1, mapType: 'type-DHP', mapTypeId: 0
      };
      theEP.settings.layers.push(new MapLayer(_blankLayer));
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to remove map overlay
    self.delMapLayer = function(theLayer, theEP, index) {
      theEP.settings.layers.splice(index, 1);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new map legend
    self.addMapLegend = function(theEP) {
      theEP.settings.legends.push(new ArrayString(''));
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new map legend
    self.delMapLegend = function(theLegend, theEP, index) {
      theEP.settings.legends.splice(index, 1);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new content mote in Topic Cards
    self.addCardContent =  function(theEP) {
      theEP.settings.content.push(new ArrayString(''));
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new map legend
    self.delCardContent = function(theContent, theEP, index) {
      theEP.settings.content.splice(index, 1);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new filter mote in Topic Cards
    self.addCardFilter =  function(theEP) {
      theEP.settings.filterMotes.push(new ArrayString(''));
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to delete filter mote on cards
    self.delCardFilter = function(theFilter, theEP, index) {
      theEP.settings.filterMotes.splice(index, 1);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new sort mote in Topic Cards
    self.addCardSort =  function(theEP) {
      theEP.settings.sortMotes.push(new ArrayString(''));
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to delete cards sort mote
    self.delCardSort = function(theSort, theEP, index) {
      theEP.settings.sortMotes.splice(index, 1);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new pinboard legend
    self.addPinLegend = function(theEP) {
      theEP.settings.legends.push(new ArrayString(''));
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to delete pinboard legend
    self.delPinLegend = function(theLegend, theEP, index) {
      theEP.settings.legends.splice(index, 1);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to add map overlay
    self.addPinLayer = function(theEP) {
      var _blankLayer = {
        label: '', file: ''
      };
      theEP.settings.layers.push(new PinLayer(_blankLayer));
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to remove map overlay
    self.delPinLayer = function(theLayer, theEP, index) {
      theEP.settings.layers.splice(index, 1);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new mote in Facet Flow list
    self.addFlowMote =  function(theEP) {
      theEP.settings.motes.push(new ArrayString(''));
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new map legend
    self.delFlowMote = function(theSort, theEP, index) {
      theEP.settings.motes.splice(index, 1);
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new mote in Facet Browser list
    self.addBrowserMote =  function(theEP) {
      theEP.settings.motes.push(new ArrayString(''));
      self.settingsDirty(true);
    };

      // PURPOSE: Handle user selection to create new map legend
    self.delBrowserMote = function(theSort, theEP, index) {
      theEP.settings.motes.splice(index, 1);
      self.settingsDirty(true);
    };

//------------------------------------------ Views -----------------------------------------

      // User-editable values
    self.edSelWidth = ko.observable('medium');
    self.edSelLinkMt  = ko.observable('');
    self.edSelLinkLbl = ko.observable('');
    self.edSelLinkNewTab = ko.observable(true);
    self.edSelLink2Mt  = ko.observable('');
    self.edSelLink2Lbl = ko.observable('');
    self.edSelLink2NewTab = ko.observable(true);
    self.widgetList = ko.observableArray([]);
    self.selMoteList = ko.observableArray([]);

    self.edPostTitle = ko.observable('');
    self.postMoteList = ko.observableArray([]);

    self.taxMoteList = ko.observableArray([]);

    self.edTrnsAudio = ko.observable('');
    self.edTrnsVideo = ko.observable('');
    self.edTrnsTransc = ko.observable('');
    self.edTrnsTransc2 = ko.observable('');
    self.edTrnsTime = ko.observable('');
    self.edTrnsSrc = ko.observable('');

      // PURPOSE: Set all variables related to views programmatically (not from user interface)
    self.setViews = function(viewSettings) {
      self.edSelWidth(viewSettings.select.width);
      self.edSelLinkMt(disableByDefault(viewSettings.select.link));
      self.edSelLinkLbl(viewSettings.select.linkLabel);
      self.edSelLinkNewTab(viewSettings.select.linkNewTab);
      self.edSelLink2Mt(disableByDefault(viewSettings.select.link2));
      self.edSelLink2Lbl(viewSettings.select.link2Label);
      self.edSelLink2NewTab(viewSettings.select.link2NewTab);
      ko.utils.arrayForEach(viewSettings.select.widgets, function(theWidget) {
        self.widgetList.push(new ArrayString(theWidget));
      });
      ko.utils.arrayForEach(viewSettings.select.content, function(theContent) {
        self.selMoteList.push(new ArrayString(theContent));
      });

      self.edPostTitle(viewSettings.post.title);
      ko.utils.arrayForEach(viewSettings.post.content, function(theContent) {
        self.postMoteList.push(new ArrayString(theContent));
      });

      ko.utils.arrayForEach(viewSettings.transcript.content, function(theContent) {
        self.taxMoteList.push(new ArrayString(theContent));
      });

      self.edTrnsAudio(disableByDefault(viewSettings.transcript.audio));
      self.edTrnsVideo(disableByDefault(viewSettings.transcript.video));
      self.edTrnsTransc(disableByDefault(viewSettings.transcript.transcript));
      self.edTrnsTransc2(disableByDefault(viewSettings.transcript.transcript2));
      self.edTrnsTime(viewSettings.transcript.timecode);
      self.edTrnsSrc(disableByDefault(viewSettings.transcript.source));
    }; // setViews()


      // PURPOSE: Return list of possible modal links for select modal
      // NOTES:   List contains all URL types and all Text motes that appear in EP legends
    self.getModalLinkNames = ko.computed(function() {
      var linkList = ['disable', 'marker'];

        // If there is a Source setting for transcriptions, add it (if not already there)
      var transSrcMote = self.edTrnsSrc();
      if (transSrcMote && transSrcMote != '') {
            if (linkList.indexOf(transSrcMote) == -1) {
              linkList.push(transSrcMote);
            }
      }

        // Go through visualizations for defined Legend/Categories
        // Don't add category/legend mote unless not already there
        // NOTE: Could alternatively loop through text motes to see which have been made into categories
      ko.utils.arrayForEach(self.entryPoints(), function(theEP) {
        switch(theEP.type) {
        case 'map':
        case 'pinboard':
          ko.utils.arrayForEach(theEP.settings.legends(), function (filterMote) {
            var moteName = filterMote.name();
              // Don't add if it already exists
            if (linkList.indexOf(moteName) == -1) {
              linkList.push(moteName);
            }
          });
          break;
        case 'cards':
        case 'tree':
        case 'time':
          var colorName = theEP.settings.color();
          if (colorName && colorName !== '' && colorName !== 'disable') {
            if (linkList.indexOf(colorName) == -1) {
              linkList.push(colorName);
            }
          }
          break;
        case 'flow':
        case 'browser':
          ko.utils.arrayForEach(theEP.settings.motes(), function (mote) {
            var moteName = mote.name();
              // Don't add if it already exists
            if (linkList.indexOf(moteName) == -1) {
              linkList.push(moteName);
            }
          });
          break;
        }
      });

        // Only URL mote types can have values usable as links
        // This is done last because adding '(Mote)' to name means we can't easily check for duplicates
      ko.utils.arrayForEach(self.allMotes(), function(theMote) {
        if (theMote.type === 'Link To') {
          linkList.push(theMote.name+' (Mote)');
        }
      });

      return linkList;
    }, self); // getModalLinkNames


      // PURPOSE: Handle user selection to add widget
    self.addWidget = function() {
      var widgetSelection;

      widgetSelection = $("#selModalWidget").val();
      if (widgetSelection) {
          // Don't add if already exists
        if (ko.utils.arrayFirst(self.widgetList(), function (widget) 
                                { return widget.name() == widgetSelection}) == null) 
        {
          self.widgetList.push(new ArrayString(widgetSelection));
          self.settingsDirty(true);
        }
      }
    };

    self.delWidget = function(index) {
      self.widgetList.splice(index, 1);
      self.settingsDirty(true);
    };


      // PURPOSE: Handle user selection to add widget
    self.addSelMote = function() {
        self.selMoteList.push(new ArrayString(''));
        self.settingsDirty(true);
    };

    self.delSelMote = function(index) {
      self.selMoteList.splice(index, 1);
      self.settingsDirty(true);
    };


      // PURPOSE: Handle user selection to add widget
    self.addPostMote = function() {
      self.postMoteList.push(new ArrayString(''));
      self.settingsDirty(true);
    };

    self.delPostMote = function(index) {
      self.postMoteList.splice(index, 1);
      self.settingsDirty(true);
    };


      // PURPOSE: Handle user selection to add widget
    self.addTaxMote = function() {
        self.taxMoteList.push(new ArrayString(''));
        self.settingsDirty(true);
    };

    self.delTaxMote = function(index) {
      self.taxMoteList.splice(index, 1);
      self.settingsDirty(true);
    };

//------------------------------------------ Utilities -----------------------------------------

      // PURPOSE: Handle user button to create new custom field
    self.createNewCF = function() {
      var cfName = $('#newCFName').val();
      var cfDefValue = $('#newCFDefault').val();
      if (cfName && cfName !== '' && cfDefValue && cfDefValue != '') {
          // Disable button until code returns
        $('#btnNewCF').button('disable');
        createCustomField(cfName, cfDefValue);
      }
    }; // createNewCF()


      // Delete button is disabled by default (enabled by getDelCurrentCFs)
    $('#btnDelOldCF').button({ disabled: true });

      // PURPOSE: Handle user button to retrieve list of custom fields for deletion
    self.getDelCurrentCFs = function() {
      function loadCurrentCFs(cfArray) {
          // Empty out options selection
        $('#selDelCFList').empty();
          // Load select options with custom fields
        var theOption;
          // Need to call underscore because cfArray will be hash/assoc array, not indexed array!
        _.each(cfArray, function(theCF) {
          theOption = '<option value="'+theCF+'">'+theCF+'</option>';
          $('#selDelCFList').append(theOption);
        });
        $("#btnDelOldCF").button('enable');
      }
      dhpGetCustomFields(loadCurrentCFs);
    }; // getDelCurrentCFs()

      // PURPOSE: Handle user button to delete currently selected custom field
    self.delOldCF = function() {
      var cfToDelete = $('#selDelCFList').val();
      if (cfToDelete && cfToDelete != '') {
        $('#mdl-del-cf').dialog({
          resizable: false,
          width: 300,
          height: 180,
          modal: true,
          dialogClass: 'wp-dialog',
          draggable: false,
          buttons: [
            {
              text: localized['delete'],
              click: function() {
                $('#btnDelOldCF').button('disable');
                deleteCustomField(this, cfToDelete);
              }
            },
            {
              text: localized['cancel'],
              click: function() {
                $(this).dialog('close');
              }
            }
          ]
        });
      }
    }; // delOldCF()


    self.frFilterValuesLoading = false;           // prevent race conditions

      // Execute Find/Replace button is disabled by default (enabled by getFRCurrentCFs)
    $("#btnDoFR").button({ disabled: true });

      // PURPOSE: Handle user button to retrieve list of custom fields for find/replace
      // NOTES:   Must populate frCustomFields
    self.getFRCurrentCFs = function() {
      function loadCurrentCFs(cfArray) {
          // Empty out options selection
        $('#selFRCFSelect').empty();
        $('#selFRFilterCF').empty();

        // $('#selFRCFSelect').append('<option value="the_content">Marker content text</option>');

          // Load select options with custom fields
        var theOption;
          // Need to call underscore because cfArray will be hash/assoc array, not indexed array!
        _.each(cfArray, function(theCF) {
          theOption = '<option value="'+theCF+'">'+theCF+'</option>';
          $('#selFRCFSelect').append(theOption);
          $('#selFRFilterCF').append(theOption);
        });

        function loadFilterValues(valArray) {
            // Empty out options selection
          $('#selFRFilterValue').empty();
          var vOption;
            // Need to call underscore because cfArray will be hash/assoc array, not indexed array!
          _.each(valArray, function(theVal) {
            vOption = '<option value="'+theVal+'">'+theVal+'</option>';
            $('#selFRFilterValue').append(vOption);
          });
          self.frFilterValuesLoading = false;
        } // loadFilterValues()

          // Activate menu value selections
        $('#selFRFilterCF').change(function() {
          if (!self.frFilterValuesLoading) {
            self.frFilterValuesLoading = true;
            var cfSelection = $('#selFRFilterCF').val();
            dhpGetFieldValues(cfSelection, loadFilterValues);
          }
        });

          // If Must Filter is false, then Must Match Value is always true
        function setMatchBox() {
          var filterOn = $('#getFRFilterCF:checked').val();
          if (filterOn) {
            $('#getFRMustMatch').prop('disabled', false);
          } else {
            $('#getFRMustMatch').prop('disabled', true);
            $('#getFRMustMatch').prop('checked', true);
          }
        }

        setMatchBox();
        $('#getFRFilterCF').change(setMatchBox);

          // Enable button now that selections are meaningfully populated
        $('#btnDoFR').button('enable');
      } // loadCurrentCFs()

      dhpGetCustomFields(loadCurrentCFs);
    }; // getFRCurrentCFs()

      // PURPOSE: Handle user button to execute find/replace
    self.doFRCF = function() {
      var frCF = $('#selFRCFSelect').val();       // the custom field we are changing
      var newValue = $('#edFRCFvalue').val();     // new value to put into field
      var matchValue = $('#edFRMatchValue').val();  // old value must be this

      if (frCF && frCF != '') {
        $('#mdl-fr-cf').dialog({
          resizable: false,
          width: 320,
          height: 200,
          modal: true,
          dialogClass: 'wp-dialog',
          draggable: false,
          buttons: [
            {
              text: localized['execute'],
              click: function() {
                $('#btnDoFR').button('disable');
                $(this).dialog('close');
                  // Which ajax function to call depends on checkboxes checked
                var filterCF = $('#selFRFilterCF').val();
                var filterVal = $('#selFRFilterValue').val();
                var mustMatchVal = $('#getFRMustMatch').prop('checked');
                var mustFilter = $('#getFRFilterCF').prop('checked');
                if (mustFilter) {
                  if (mustMatchVal) {
                    updateCustomFieldFilter(frCF, matchValue, newValue, filterCF, filterVal);
                  } else {
                    replaceCustomFieldFilter(frCF, newValue, filterCF, filterVal);
                  }
                } else {
                  findReplaceCustomField(frCF, matchValue, newValue);
                }
              }
            },
            {
              text: localized['cancel'],
              click: function() {
                $(this).dialog('close');
              }
            }
          ]
        });
      }
    }; // doFRCF()


//------------------------------------------ Test Panel -----------------------------------------

      // PURPOSE: Handle user selection of test button
      // NOTES:   Append all results to testResults DIV
    self.runTests = function() {
      $('.runTests').button('disable');
      $('#testResults').empty();

      $('#accordion').accordion('option', 'active', 5);
      $('html, body').animate({scrollTop: $("#accordion").offset().top}, 500);

      var isErrors = false;
        // Check global-level settings --------------
      $('#testResults').append('<strong id="general_settings">'+localized['general_settings']+'</strong>');
      $('#general_settings').hide();

        // Home URL but no label, or vice-versa?
      if ((self.edHomeBtnLbl() && self.edHomeBtnLbl() != '') || (self.edHomeURL() && self.edHomeURL() != '')) {
        if (self.edHomeBtnLbl() == '' || self.edHomeURL() == '') {
          $('#testResults').append(localized['home_button']);
          isErrors = true;
        }
          // ensure a well-formed URL
        var testURL = /^(http[s]?:\/\/){0,1}(www\.){0,1}[a-zA-Z0-9\.\-]+\.[a-zA-Z]{2,5}[\.]{0,1}/;
        if (!testURL.test(self.edHomeURL())) {
          $('#testResults').append(localized['home_address']);
          isErrors = true;
        }
      }

      if (self.optionsCF.length == 0) {
          $('#testResults').append(localized['import_markers']);
          isErrors = true;
      }

      if (isErrors) {
        $('#general_settings').show();
      }

        // Check the settings of Mote definitions ---------

      isErrors = false;
      $('#testResults').append('<strong id="motes">'+localized['motes']+'</strong>');
      $('#motes').hide();

      if (self.allMotes().length == 0) {
          $('#testResults').append(localized['define_motes']);
          isErrors = true;
      }

      ko.utils.arrayForEach(self.allMotes(), function(theMote) {
        switch(theMote.type) {
        case 'Pointer':
          if (theMote.delim == '') {
            $('#testResults').append(sprintf(localized['pointer_delimiter'], theMote.name));
            isErrors = true;
          }
          break;
        case 'Lat/Lon Coordinates':
          if (theMote.delim == ',') {
            $('#testResults').append(sprintf(localized['comma_delimiter'], theMote.name));
            isErrors = true;
          }
          break;
        } // switch()
      }); // forEach(motes)


      if (isErrors) {
        $('#motes').show();
      }

        // Check the settings of Entry Points -----------
      isErrors = false;
      $('#testResults').append('<strong id="entry_points">'+localized['entry_points']+'</strong>');
      $('#entry_points').hide();

      if (self.entryPoints().length == 0) {
          $('#testResults').append(localized['no_entry_points']);
          isErrors = true;
      }

      ko.utils.arrayForEach(self.entryPoints(), function(theEP) {
          // Report errors with help of this utility function
        function epErrorMessage(errString) {
          $('#testResults').append(sprintf(localized['ep_error'], errString, theEP.label()));
          isErrors = true;
        }
          // Ensure that all EPs have labels if multiple EPs
        if (theEP.label() == '' && self.entryPoints().length > 1) {
          $('#testResults').append(localized['unlabeled_entry_point']);
          isErrors = true;
        }
        switch(theEP.type) {
        case 'map':
            // Do maps have at least one legend?
          if (theEP.settings.legends().length == 0) {
            epErrorMessage(localized['map_legend']);
            isErrors = true;
          }
          if (theEP.settings.coordMote() == '') {
            epErrorMessage(localized['map_coord_mote']);
            isErrors = true;
          }
          break;
        case 'cards':
          var colorName = theEP.settings.color();
          if (!colorName || colorName === 'disable') {
            epErrorMessage(localized['cards_color_legend']);
            isErrors = true;
          }
            // Do cards have at least one content mote?
          if (theEP.settings.content().length == 0) {
            epErrorMessage(localized['cards_content']);
            isErrors = true;
          }
          break;
        case 'pinboard':
          var w;
          if (theEP.settings.dw() == '' || isNaN(w=parseInt(theEP.settings.dw(),10)) || w <= 0) {
            epErrorMessage(localized['pinboard_width']);
            isErrors = true;
          }
          var h;
          if (theEP.settings.dh() == '' || isNaN(h=parseInt(theEP.settings.dh(),10)) || h <= 0) {
            epErrorMessage(localized['pinboard_height']);
            isErrors = true;
          }
          if (theEP.settings.iw() == '' || isNaN(w=parseInt(theEP.settings.iw(),10)) || w <= 0) {
            epErrorMessage(localized['pinboard_bg_width']);
            isErrors = true;
          }
          if (theEP.settings.ih() == '' || isNaN(h=parseInt(theEP.settings.ih(),10)) || h <= 0) {
            epErrorMessage(localized['pinboard_bg_height']);
            isErrors = true;
          }
            // Do pinboards have at least one legend?
          if (theEP.settings.legends().length == 0) {
            epErrorMessage(localized['pinboard_legend']);
            isErrors = true;
          }
          if (theEP.settings.coordMote() == '') {
            epErrorMessage(localized['pinboard_coord_mote']);
            isErrors = true;
          }
          break;
        case 'tree':
          if (theEP.settings.head() == '') {
            epErrorMessage(localized['tree_head']);
            isErrors = true;
          }
          if (theEP.settings.children() == '') {
            epErrorMessage(localized['tree_pointer']);
            isErrors = true;
          }
          var i;
          if (theEP.settings.fSize() == '' || isNaN(i=parseInt(theEP.settings.fSize(),10)) || i <= 8) {
            epErrorMessage(localized['tree_font_size']);
            isErrors = true;
          }
          if (theEP.settings.width() == '' || isNaN(i=parseInt(theEP.settings.width(),10)) || i <= 20) {
            epErrorMessage(localized['tree_image_width']);
            isErrors = true;
          }
          if (theEP.settings.height() == '' || isNaN(i=parseInt(theEP.settings.height(),10)) || i <= 20) {
            epErrorMessage(localized['tree_image_height']);
            isErrors = true;
          }
          break;
        case 'time':
          if (theEP.settings.date() == '') {
            epErrorMessage(localized['time_date_mote']);
            isErrors = true;
          }
          if (theEP.settings.color() == '') {
            epErrorMessage(localized['time_color_legend']);
            isErrors = true;
          }
          var i;
          if (theEP.settings.bandHt() == '' || isNaN(i=parseInt(theEP.settings.bandHt(),10)) || i <= 8) {
            epErrorMessage(localized['time_band_height']);
            isErrors = true;
          }
          if (theEP.settings.wAxisLbl() == '' || isNaN(i=parseInt(theEP.settings.wAxisLbl(),10)) || i <= 10) {
            epErrorMessage(localized['time_label_width']);
            isErrors = true;
          }
            // Check Dates and their formats -- must be a specific date (can't be fuzzy)
          var dateRegEx = /^(open|-?\d+(-(\d)+)?(-(\d)+)?)$/;
          if (!dateRegEx.test(theEP.settings.from())) {
            epErrorMessage(localized['time_date_start_frame']);
            isErrors = true;
          }
          if (!dateRegEx.test(theEP.settings.to())) {
            epErrorMessage(localized['time_date_end_frame']);
            isErrors = true;
          }
          if (!dateRegEx.test(theEP.settings.openFrom())) {
            epErrorMessage(localized['time_date_start_zoom']);
            isErrors = true;
          }
          if (!dateRegEx.test(theEP.settings.openTo())) {
            epErrorMessage(localized['time_date_end_zoom']);
            isErrors = true;
          }
          break;
        case 'flow':
          var w;
          if (theEP.settings.width() == '' || isNaN(w=parseInt(theEP.settings.width(),10)) || w <= 0) {
            epErrorMessage(localized['facet_bg_width']);
            isErrors = true;
          }
          var h;
          if (theEP.settings.height() == '' || isNaN(h=parseInt(theEP.settings.height(),10)) || h <= 0) {
            epErrorMessage(localized['facet_bg_height']);
            isErrors = true;
          }
          if (theEP.settings.motes().length < 2) {
            epErrorMessage(localized['facet_two_motes']);
            isErrors = true;
          }
            // Ensure that each facet-mote is only used once in list
          var redundFacets=false;
          ko.utils.arrayForEach(theEP.settings.motes(), function(theMote) {
            var matchCnt=0;
            ko.utils.arrayForEach(theEP.settings.motes(), function(mote2) {
              if (theMote.name() === mote2.name()) { matchCnt+=1; }
            });
            if (matchCnt > 1) { redundFacets=true; }
          });
          if (redundFacets) {
            epErrorMessage(localized['facet_unique_motes']);
            isErrors = true;
          }
          break;
        case 'browser':
          if (theEP.settings.motes().length < 1) {
            epErrorMessage(localized['facet_browser_mote']);
            isErrors = true;
          }
            // Ensure that each facet-mote is only used once in list
          var redundFacets=false;
          ko.utils.arrayForEach(theEP.settings.motes(), function(theMote) {
            var matchCnt=0;
            ko.utils.arrayForEach(theEP.settings.motes(), function(mote2) {
              if (theMote.name() === mote2.name()) { matchCnt+=1; }
            });
            if (matchCnt > 1) { redundFacets=true; }
          });
          if (redundFacets) {
            epErrorMessage(localized['redundant_motes']);
            isErrors = true;
          }
          break;          
        } // switch
      });

      if (isErrors) {
        $('#entry_points').show();
      }
  

      $('#testResults').append('<strong>'+localized['misc']+'</strong>');

        // Is there at least one mote for select modal content?
      if (self.selMoteList().length < 1) {
          $('#testResults').append(localized['empty_content_mote']);
      }

        // Anamoly: If no selection possible, edTrnsTime() == undefined; added '' for extra protection

        // If Transcript Source mote selected, ensure other settings are as well
      if (self.edTrnsSrc() !== 'disable') {
        if ((self.edTrnsAudio() === 'disable' && self.edTrnsVideo() === 'disable') || self.edTrnsTransc() === 'disable') {
          $('#testResults').append(localized['transcript_settings']);
        }
      }

        // Call PHP functions to test any transcript texts
      $('#testResults').append(localized['tests_being_conducted']);
      dhpPerformTests();
    }; // runTests

  }; // ProjectSettings


//=================================== INITIALIZAION ===================================

    // Initialize jQuery components
  $("#accordion, #subaccordion").accordion({ collapsible: true, heightStyle: 'content' });
  $("#accordion").click(function(){
      $('html, body').animate({scrollTop: $("#accordion").offset().top}, 500);
    });


    // Add decimal formatting extension (X.X) for observable (opacity)
  ko.extenders.onedigit = function(target) {
    //create a writeable computed observable to intercept writes to our observable
    var result = ko.computed({
        read: target,  //always return the original observables value
        write: function(newValue) {
            var current = target(),
                newValueAsNum = isNaN(newValue) ? 0 : parseFloat(+newValue),
                valueToWrite = Number(newValueAsNum).toFixed(1);
 
              // Only save if value changed
            if (valueToWrite !== current) {
                target(valueToWrite);
            } else {
                  // If the rounded value is same, but a different value was written, force a notification for the current field
                if (newValue !== current) {
                    target.notifySubscribers(valueToWrite);
                }
            }
        }
    }).extend({ notify: 'always' });
 
      // Init with current value to make sure it is rounded appropriately
    result(target());
 
      //return the new computed observable
    return result;
  }; // onedigit


    // Need to Initialize project here first so that object properties and methods visible when
    //    Knockout is activated
  var projObj = new ProjectSettings(customFieldsParam);

    // Manually load the Project Settings object from JSON string
  projObj.setDetails(savedSettings.general);
  ko.utils.arrayForEach(normalizeArray(savedSettings.motes), function(theMote, index) {
    projObj.setMote(theMote.name, theMote.type, theMote.cf, theMote.delim);
  });
  ko.utils.arrayForEach(normalizeArray(savedSettings.eps), function(theEP) {
    projObj.setEP(theEP);
  });
  projObj.setViews(savedSettings.views);


    // Allows entry points to be sorted and updates knockout respectively
    // Adapted from http://stackoverflow.com/questions/9703647/knockout-custom-binding-for-jquery-ui-sortable-strange-behavior
  ko.bindingHandlers.sortableList = {
    init: function (element, valueAccessor) {
      var theEPs = valueAccessor();
      $('#entryPoints').sortable({ 
        containment: 'parent',
        tolerance: 'pointer',
        update: function(event, ui) {
          projObj.settingsDirty(true);
          
          var item = ko.dataFor(ui.item[0]),
          newIndex = ui.item.index();

          if (newIndex >= theEPs().length) newIndex = theEPs().length - 1;
          if (newIndex < 0) newIndex = 0;

          ui.item.remove();
          theEPs.remove(item);
          theEPs.splice(newIndex, 0, item);
        } 
      });
    }
  };


    // Add new functionality for jQueryUI slider
  ko.bindingHandlers.opacitySlider = {
    init: function (element, valueAccessor, allBindingsAccessor) {
      $(element).slider({min: 0, max: 1, orientation: 'horizontal', range: false, step: 0.05 });

      ko.utils.registerEventHandler(element, 'slidechange', function (event, ui) {
        var observable = valueAccessor();
        observable(ui.value);
      });
      ko.utils.domNodeDisposal.addDisposeCallback(element, function () {
          $(element).slider('destroy');
      });
    },
    update: function (element, valueAccessor) {
      var value = ko.utils.unwrapObservable(valueAccessor());
      if (isNaN(value)) value=0;
      $(element).slider('value', value);
    }
  }; // bindingHandlers.opacitySlider


    // Add minimal functionality for jQueryUI button
  ko.bindingHandlers.jqButton = {
    init: function (element) {
      $(element).button();
    }
  };


    // Initialize use of Knockout within the inserted HTML (after bindingHandlers added)
  ko.applyBindings(projObj, document.getElementById('ko-dhp'));


  //=================================== AJAX FUNCTIONS ==================================

    // PURPOSE: Saves project settings data object
  function saveSettingsInWP(settingsData) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpSaveProjectSettings',
              project: projectID,
              settings: settingsData
          },
          success: function(data, textStatus, XMLHttpRequest) {
            $('#btnSaveSettings').button('enable');
            projObj.cleanSettings();
          },
          error: function(XMLHttpRequest, textStatus, errorThrown){
            alert(errorThrown);
            $('#btnSaveSettings').button('enable');
          }
      });
  } // saveSettingsInWP()


    // PURPOSE: Create legend (if doesn't exist yet) and collect the array of values/terms
    // INPUT:   moteName = name of mote whose category names need to be fetched
    //          moteCF = custom field associated with mote
    //          dataDelim = character used as delimiter
    //          funcToCall = callback to invoke upon completion with mote record and data
    // RETURNS: Array with data:
    //            term_id   = ID of this term (string not integer!)
    //            name      = label for this term
    //            parent    = ID of parent term, or 0 (string not integer!)
    //            count     = # times value/tag used (string not integer!)
    //            icon_url  = visual metadata (#number for color, "." for Maki-icon)
  function getLegendValuesInWP(moteName, moteCF, dataDelim, funcToCall) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpGetLegendValues',
              moteName: moteName,
              delim: dataDelim,
              customField: moteCF,
              project: projectID
          },
          success: function(data, textStatus, XMLHttpRequest) {
                // data is a JSON object
            var results = JSON.parse(data);
            //console.log(results);
            funcToCall(results);
          },
          error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert(errorThrown);
          }
      });
  } // getLegendValuesInWP()


    // PURPOSE: Update term structure for legend(introduces icon_url field)
    // RETURNS: Object with terms
    // INPUT:   legendName = Head term id (legend name)
    //          taxTermsList = flat list containing data for updating terms in WP
  function saveLegendValuesInWP(legendName, taxTermsList) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpSaveLegendValues',
              mote_name: legendName,
              project: projectID,
              terms: taxTermsList
          },
          success: function(data, textStatus, XMLHttpRequest) {
            //console.log(data);
          },
          error: function(XMLHttpRequest, textStatus, errorThrown) {
             alert(errorThrown);
          }
      });
  } // saveLegendValuesInWP()

    // PURPOSE: Handle calling WP to rebuild legend, re-enable button when done
  function rebuildLegendValuesInWP(legendName, moteCF, theDelim) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpRebuildLegendValues',
              moteName: legendName,
              customField: moteCF,
              delim: theDelim,
              project: projectID
          },
          success: function(data, textStatus, XMLHttpRequest){
            console.log("Rebuild Legend results: "+data);
            $('#btnRebuildMote').button('enable');
          },
          error: function(XMLHttpRequest, textStatus, errorThrown){
            alert(errorThrown);
            $('#btnRebuildMote').button('enable');
          }
      });
  } // rebuildLegendValuesInWP()

  function dhpCreateTermInTax(newTermName, moteName, callBack) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpCreateTermInTax',
              project: projectID,
              newTerm: newTermName,
              legendName: moteName
          },
          success: function(data, textStatus, XMLHttpRequest) {
              var termData = JSON.parse(data);
              var termID = termData.termID;
              if (typeof(termID) == 'string') { termID = parseInt(termID); }
              callBack(termID);
          },
          error: function(XMLHttpRequest, textStatus, errorThrown){
             alert(errorThrown);
          }
      });
  } // dhpCreateTermInTax()


    // PURPOSE: Remove the head term of a Legend (if it exists)
  function deleteHeadTermInWP(termName) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpDeleteHeadTerm',
              project: projectID,
              term_name: termName
          },
          success: function(data, textStatus, XMLHttpRequest){
            console.log("Delete results: "+data);
          },
          error: function(XMLHttpRequest, textStatus, errorThrown){
             alert(errorThrown);
          }
      });
  } // deleteHeadTermInWP()


  /* ============= CUSTOM FIELD UTILITIES ============= */

    // PURPOSE: Call php function to create new custom field in this project, set value to defValue
  function createCustomField(fieldName, defValue) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpAddCustomField',
              project: projectID,
              field_name: fieldName,
              field_value: defValue
          },
          success: function(data, textStatus, XMLHttpRequest) {
              // Re-enable Create button
              $('#btnNewCF').button('enable');
          },
          error: function(XMLHttpRequest, textStatus, errorThrown) {
             alert(errorThrown);
              $('#btnNewCF').button('enable');
          }
      });
  } // createCustomField()

    // PURPOSE: Call php function to delete a custom field
  function deleteCustomField(dialog, theField) { 
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpDeleteCustomField',
              project: projectID,
              field_name: theField
          },
          success: function(data, textStatus, XMLHttpRequest) {
            $(dialog).dialog('close');
              // Force refresh of custom fields (and keep Delete button disabled)
            $('#selDelCFList').empty();
            $('#btnDelOldCF').button('enable');
          },
          error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert(errorThrown);
            $(dialog).dialog('close');
            $('#btnDelOldCF').button('enable');
          }
      });
  } // deleteCustomField()


    // PURPOSE: Search/replace value in a custom field when match on by another custom field and value
    // INPUT:   fieldName = name of custom field
    //          currentValue = wherever this value appears in the custom field (or the_content)
    //          newValue = it will be replaced by this
    //          filterKey = the other custom field to use as a filter
    //          filterValue = filterKey must have this value before being replaced
  function updateCustomFieldFilter(fieldName, currentValue, newValue, filterKey, filterValue) {
    jQuery.ajax({
      type: 'POST',
      url: ajax_url,
      data: {
          action: 'dhpUpdateCustomFieldFilter',
          project: projectID,
          field_name: fieldName,
          current_value: currentValue,
          new_value: newValue,
          filter_key: filterKey,
          filter_value: filterValue
      },
      success: function(data, textStatus, XMLHttpRequest) {
          // Re-enable Execute Find/Replace button
        $('#btnDoFR').button('enable');
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
        alert(errorThrown);
        $('#btnDoFR').button('enable');
      }
    });
  } // updateCustomFieldFilter()


    // PURPOSE: Replace value in custom fields in this project when qualified by filter
    // INPUT:   fieldName = name of custom field
    //          newValue = it will be replaced by this
    //          filterKey = the other custom field to use as a filter
    //          filterValue = filterKey must have this value before being replaced
  function replaceCustomFieldFilter(fieldName, newValue, filterKey, filterValue) {
    jQuery.ajax({
      type: 'POST',
      url: ajax_url,
      data: {
          action: 'dhpReplaceCustomFieldFilter',
          project: projectID,
          field_name: fieldName,
          new_value: newValue,
          filter_key: filterKey,
          filter_value: filterValue
      },
      success: function(data, textStatus, XMLHttpRequest) {
        //console.log(textStatus); 
        $('#btnDoFR').button('enable');
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
        alert(errorThrown);
        $('#btnDoFR').button('enable');
      }
    });
  } // replaceCustomFieldFilter()


    // PURPOSE: Find/replace on all custom fields in this project (no filter)
    // INPUT:   findCF = name of custom field
    //          findCFvalue = it will be replaced by this
    //          replaceCFvalue = matches will be replaced by this value
  function findReplaceCustomField(findCF, findCFvalue, replaceCFvalue) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpFindReplaceCustomField',
              project: projectID,
              field_name: findCF,
              find_value: findCFvalue,
              replace_value: replaceCFvalue
          },
          success: function(data, textStatus, XMLHttpRequest) {
              $('#btnDoFR').button('enable');
              //console.log(textStatus); 
          },
          error: function(XMLHttpRequest, textStatus, errorThrown) {
              $('#btnDoFR').button('enable');
             alert(errorThrown);
          }
      });
  } // findReplaceCustomField()


    // PURPOSE: Return list of all custom fields for this project
  function dhpGetCustomFields(callBack) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpGetCustomFields',
              project: projectID
          },
          success: function(data, textStatus, XMLHttpRequest) {
              // console.log("Get Custom Fields="+data);
              callBack(JSON.parse(data));
          },
          error: function(XMLHttpRequest, textStatus, errorThrown) {
             alert(errorThrown);
          }
      });
  } // dhpGetCustomFields()

    // PURPOSE: Return list of all values for this custom field
  function dhpGetFieldValues(fieldName, callBack) {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpGetFieldValues',
              project: projectID,
              field_name: fieldName
          },
          success: function(data, textStatus, XMLHttpRequest) {
              // console.log("GetFieldValues = "+data);
              callBack(JSON.parse(data));
          },
          error: function(XMLHttpRequest, textStatus, errorThrown) {
             alert(errorThrown);
          }
      });
  } // dhpGetFieldValues()


    // PURPOSE: Call PHP code to perform other tests on ProjectSettings
  function dhpPerformTests() {
    jQuery.ajax({
          type: 'POST',
          url: ajax_url,
          data: {
              action: 'dhpPerformTests',
              project: projectID
          },
          success: function(data, textStatus, XMLHttpRequest) {
            $('#testResults').append(data);
            $('.runTests').button('enable');
          },
          error: function(XMLHttpRequest, textStatus, errorThrown) {
            alert(errorThrown);
            $('.runTests').button('enable');
          }
      });
  } // dhpGetFieldValues()

});
