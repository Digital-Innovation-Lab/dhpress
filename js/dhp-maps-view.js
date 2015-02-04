// DH Press Maps View -- contains all data and functions for rendering maps with help of dhpCustomMaps
// ASSUMES: An area for the map has been marked with HTML div as "dhp-visual"
//          That the custom maps "library" has already been loaded with corresponding map entries
// NOTES:   Format of Marker and Legend data (GeoJSON) is documented in dhp-project-functions.php
//          Once size of Marker array increases, may need to make filter more efficient
//          FeatureCollections can now consist of both Points and Polygons; however, mixing makes it
//              difficult to pass as GeoJSON to Leaflet, as markerStyle() does redundant work. A better
//              solution would be to create and pass separate GeoJSON arrays for Points and Polygons
//              but this is not conducive to current architecture. Better support in next Leaflet?

// USES:    JavaScript libraries jQuery, Underscore, Zurb Foundation, Leaflet


var dhpMapsView = {
        // Contains fields: ajaxURL, projectID, mapEP, viewParams, vizIndex

        //			rawAjaxData = raw data returned from AJAX
        //			allMarkers = All marker posts assoc. w/ Project; see data desc in createMarkerArray() of dhp-project-functions.php

        //          curLgndName = name of current Legend mote
        //          curLgndData = points to terms array of current Legend
        //          menuLgnds = array of legend data for purposes of menu
        //          selLgnds = array of currently selected Legend IDs in order { id, viz }
        //          slidersShowing = true if Legend currently shows Layer sliders

        //          markerOpacity = opacity of marker layer (for all markers)
        //          radius = radius of geometric markers
        //          makiSize = "s" | "m" | "l"
        //          makiIcons = array of maki icons, indexed by name
        //          pngIcons = array of PNG image icons, indexed by name

        //          mapLayers = array of map layer data to display
        //          mapLeaflet = Leaflet map object
        //          markerLayer = Leaflet layer containing individual Markers
        //          control = Leaflet map layer selection controller
        //          useParent = if true (always true!), actions on parent term affect child terms
        //          isTouch = this is a touch-screen interface, not mouse

        //          currentFeature = map feature currently highlighted or selected (with modal)
        //          anyPopupsOpen = true when a popover modal is currently open

        // PURPOSE: Initialize new leaflet map, layers, and markers                         
        // INPUT:   ajaxURL      = URL to WP
        //          projectID    = ID of project
        //          mapEP        = settings for map entry point (from project settings)
        //          viewParams   = array of data about map layers
        //                          (compiled by dhp_get_map_layer_data() in dhp-project-functions.php)
    initialize: function(ajaxURL, projectID, vizIndex, mapEP, viewParams) {
             // Constants
        dhpMapsView.checkboxHeight = 12; // default checkbox height

        dhpMapServices.init(viewParams.layerData);

            // Save reset data for later
        dhpMapsView.mapEP          = mapEP;
        dhpMapsView.viewParams     = viewParams;

        dhpMapsView.isTouch        = dhpServices.isTouchDevice();

        dhpMapsView.markerOpacity  = 1;     // default marker opacity
        dhpMapsView.makiSize       = mapEP.size;
        switch (mapEP.size) {
        case "s":
            dhpMapsView.radius     = 4;
            break;
        case "m":
            dhpMapsView.radius     = 8;
            break;
        case "l":
            dhpMapsView.radius     = 12;
            break;
        }
        dhpMapsView.makiIcons      = [];    // array of Maki-icons by name
        dhpMapsView.pngIcons       = [];    // array of PNG image icons by name

        dhpMapsView.mapLayers      = [];

            // expand to show/hide child terms and use their colors
        dhpMapsView.useParent = true;

        dhpMapsView.initializeMap2();

        dhpMapsView.createLayers();
        dhpMapsView.createMapControls();

            // Create Leaflet icons for each defined PNG image
        for (var i=0; i<viewParams.pngs.length; i++)
        {
            var thePNG = viewParams.pngs[i];
            var pngSize = [ thePNG.w, thePNG.h ];
            var pngAnchor = [ thePNG.w/2, thePNG.h ];
            dhpMapsView.pngIcons[thePNG.title] = L.icon(
                {   iconUrl: thePNG.url,
                    iconSize: pngSize,
                    iconAnchor: pngAnchor
                } );
        }


        jQuery.ajax({
            type: 'POST',
            url: ajaxURL,
            data: {
                action: 'dhpGetMarkers',
                project: projectID,
                index: vizIndex
            },
            success: function(data, textStatus, XMLHttpRequest)
            {
                dhpMapsView.createDataObjects(JSON.parse(data));
                    // Remove Loading modal
                dhpServices.remLoadingModal();
                jQuery('.reveal-modal-bg').remove();
            },
            error: function(XMLHttpRequest, textStatus, errorThrown)
            {
               alert(errorThrown);
            }
        });
    }, // initialize()


        // PURPOSE: Initialize map viewing area with controls
    initializeMap2: function()
    {
        dhpMapsView.anyPopupsOpen = false;

            // Add map elements to nav bar
        jQuery('.dhp-nav .top-bar-section .left').append(Handlebars.compile(jQuery("#dhp-script-map-menus").html()));

            // Insert Legend area -- Joe had "after" but menu off map above if not "append"
        jQuery('#dhp-visual').append(Handlebars.compile(jQuery("#dhp-script-legend-head").html()));

        jQuery('#dhp-visual').append('<div id="dhpMap"/>');

           //create map with view
        dhpMapsView.mapLeaflet = L.map('dhpMap',{ zoomControl:false }).setView([dhpMapsView.mapEP.lat, dhpMapsView.mapEP.lon], dhpMapsView.mapEP.zoom);

            // Handle hover modal popup
        if (dhpMapsView.isTouch) {
            dhpMapsView.mapLeaflet.on('popupopen', function(e) {
                dhpMapsView.anyPopupsOpen = true;
            });
            dhpMapsView.mapLeaflet.on('popupclose', function(e) {
                    // popupclose event fires on open and close (bug?)
                if (dhpMapsView.anyPopupsOpen) {
                    dhpMapsView.markerLayer.resetStyle(e.popup._source);
                    dhpMapsView.anyPopupsOpen = false;
                }
            });
        }

        // jQuery('#dhp-visual').height(jQuery('#dhp-visual')-45);
    }, // initializeMap2()


        // PURPOSE: Create base layers and overlays
    createLayers: function()
    {
        var opacity;

            // The control object manages which layers are visible at any time (user selection)
        dhpMapsView.control = L.control.layers();
        dhpMapsView.control.addTo(dhpMapsView.mapLeaflet);

            // Compile map layer data into mapLayers array and create with Leaflet
        _.each(dhpMapsView.mapEP.layers, function(layerToUse, index) {
            var newLayer;

            opacity = layerToUse.opacity || 1;

            newLayer = dhpMapServices.createMapLayer(layerToUse.id, opacity,
                            dhpMapsView.mapLeaflet, dhpMapsView.control);
            dhpMapsView.mapLayers.push(newLayer);
        }); // each sourceLayers
    }, // createLayers()


        // PURPOSE: Create Leaflet map controls
    createMapControls: function() {
        //control position
        var layerControl = L.control.zoom({position: 'topright'});
        layerControl.addTo(dhpMapsView.mapLeaflet);

        // add reset button
        var resetControl = L.control({position: 'topright'});

        resetControl.onAdd = function (map) {
            this._div = L.DomUtil.create('div', 'reset-control leaflet-bar');
            this.update();
            return this._div;
        };
        resetControl.update = function (props) {
            this._div.innerHTML = '<a class="reset-map" ><i class="fi-refresh"></i></a>';
        };
        resetControl.addTo(dhpMapsView.mapLeaflet);
        jQuery('.reset-control').click(function(){
            dhpMapsView.resetMap();
        });
    }, // createMapControls()


        // PURPOSE: Create marker objects for map visualization; called by loadMapMarkers()
        // INPUT:   geoData = all AJAX data as JSON object: Array of ["type", ...]
    createDataObjects: function(data) 
    {
        dhpMapsView.rawAjaxData = data;

        var legends = [];

            // Assign data to appropriate objects
        _.each(dhpMapsView.rawAjaxData, function(dataSet) {
            switch(dataSet.type) {
            case 'filter':
                legends.push(dhpServices.flattenTerms(dataSet));
                break;
            case 'FeatureCollection':
                dhpMapsView.allMarkers = dataSet;
                break;
            }
        });
        dhpMapsView.menuLgnds = legends;

            // First legend will be selected by default
        dhpMapsView.createLegends();
        dhpMapsView.createMarkerLayer();
        dhpMapsView.buildLayerControls();

            // Set filter to first Legend
        dhpMapsView.switchFilter();
    }, // createDataObjects()


        // PURPOSE: Creates initial Marker Layer (only once -- not the Markers on it)
    createMarkerLayer: function()
    {
            // Is it a Clustering Marker Layer?
        if (dhpMapsView.mapEP.cluster) {
            dhpMapsView.markerLayer = new L.MarkerClusterGroup();
        } else {
            dhpMapsView.markerLayer = L.featureGroup();            
        }
            // Create options properties if they don't already exist
        dhpMapsView.markerLayer.options = dhpMapsView.markerLayer.options || { };
        dhpMapsView.markerLayer.options.layerName = 'Markers';

        dhpMapsView.markerLayer.addTo(dhpMapsView.mapLeaflet);
        dhpMapsView.control.addOverlay(dhpMapsView.markerLayer, 'Markers');

        dhpMapsView.mapLayers.push(dhpMapsView.markerLayer);
    }, // createMarkerLayer()


        // PURPOSE: Handle click on feature
    markerClick: function(e)
    {
        if (e.target && e.target.options) {
            var marker = dhpMapsView.rawAjaxData[dhpMapsView.rawAjaxData.length-1];
            marker = marker.features[e.target.options.id];
            dhpServices.showMarkerModal(marker);
        }
    }, // markerClick()


        // PURPOSE: Bind controls for each Marker
    // onEachFeature: function(feature, layer)
    // {
    //         // Hover popup only for touchscreen
    //     if (dhpMapsView.isTouch) {
    //         layer.bindPopup('<div><h1>'+feature.properties.title+
    //             '</h1><a class="button success" onclick="javascript:dhpMapsView.onFeatureSelect()">More</a></div>',
    //             {offset: L.Point(0, -10)});

    //             // Click is automatically handled by Leaflet popup
    //         layer.on({
    //             mouseover: dhpMapsView.hoverFeature,
    //             mouseout: dhpMapsView.resetHighlight
    //         });
    //     } else {
    //         layer.on({
    //             click: dhpMapsView.clickFeature
    //         });
    //     }
    // }, // onEachFeature()


        // PURPOSE: Handle touch over this feature
    // hoverFeature: function(e) {
    //     dhpMapsView.currentFeature = e.target.feature;

    //     e.target.openPopup();

    //         // This only works for geometric markers, not maki-icons, so must remove for now
    //     // e.target.setStyle({ // highlight the feature
    //     //     weight: 3,
    //     //     color: '#666',
    //     //     dashArray: '',
    //     //     fillOpacity: 0.6
    //     // });

    //         // Can't feature foregrounding on Internet Explorer or Opera
    //         // This only works for geometric markers, not maki-icons
    //     // if (!L.Browser.ie && !L.Browser.opera) {
    //     //     e.target.bringToFront();
    //     // }
    // },


        // PURPOSE: Handle mouse(only!) selection of feature
    // clickFeature: function(e) {
    //     dhpMapsView.currentFeature = e.target.feature;
    //     dhpMapsView.onFeatureSelect();
    // },


        // PURPOSE: Remove the hover style
    resetHighlight: function(e) {
        dhpMapsView.markerLayer.resetStyle(e.target);
    },

        // PURPOSE: Reset map view to default state
    resetMap: function()
    {
        dhpMapsView.mapLeaflet.setView([dhpMapsView.mapEP.lat, dhpMapsView.mapEP.lon], dhpMapsView.mapEP.zoom);
    }, // resetMap()


        // PURPOSE: Create all Markers based on current Legend selection
        // NOTES:   Set options.id in resulting Marker to index of Marker
    createAllMarkers: function()
    {
        var markerArray, aNewMarker;

            // Remove all previous markers
        dhpMapsView.markerLayer.clearLayers();
            // Go through all markers
        markerArray = dhpMapsView.rawAjaxData[dhpMapsView.rawAjaxData.length-1];
        markerArray = markerArray.features;
        _.forEach(markerArray, function(theMarker, markerIndex) {
                // Reset results
            aNewMarker = null;
                // Find the first Legend match
            var sIndex, found=false, fKey, tempRec, lgndRec;
            for (var i=0; i<theMarker.properties.categories.length; i++) {
                tempRec = { id: theMarker.properties.categories[i] };
                sIndex = _.sortedIndex(dhpMapsView.selLgnds, tempRec, 'id');
                if (sIndex < dhpMapsView.selLgnds.length) {
                    lgndRec = dhpMapsView.selLgnds[sIndex];
                    if (lgndRec.id == tempRec.id) {
                        fKey = lgndRec.viz;
                        found = true;
                        break;
                    } // if
                } // if
            } // for

            if (found) {
                switch (fKey.charAt(0)) {
                    // Color value -- Point or Polygon
                case '#':
                    if (theMarker.geometry.type === 'Point') {
                        aNewMarker = L.circleMarker(theMarker.geometry.coordinates, {
                            id: markerIndex, weight: 1, radius: dhpMapsView.radius,
                            fillColor: fKey, color: "#000",
                            opacity: dhpMapsView.markerOpacity, fillOpacity: dhpMapsView.markerOpacity
                        });
                    } else {
                        if (theMarker.geometry.type !== 'Polygon') {
                            throw new Error("Bad Marker type: "+theMarker.geometry.type);
                        } else {
                            aNewMarker = L.polygon(theMarker.geometry.coordinates, {
                                id: markerIndex, weight: 1, color: "#000", fillColor: fKey,
                                opacity: dhpMapsView.markerOpacity, fillOpacity: dhpMapsView.markerOpacity
                            });
                        }
                    }
                    break;

                    // Maki-icon -- cannot be a Polygon!
                case '.':
                        // See if maki-icon has already been created and if not create it
                    var iName = fKey.substring(1);
                    var mIcon = dhpMapsView.makiIcons[iName];
                    if (mIcon == undefined || mIcon == null) {
                        mIcon = L.MakiMarkers.icon({
                            icon: iName, color: "#12a",
                            size: dhpMapsView.makiSize
                        });
                        dhpMapsView.makiIcons[iName] = mIcon;
                    }
                    if (theMarker.geometry.type === 'Point') {
                        aNewMarker = L.marker(theMarker.geometry.coordinates, {
                            id: markerIndex, icon: mIcon, riseOnHover: true
                        });
                    } else {
                        throw new Error("Cannot use Maki-icon Legends with non-Point Markers: "+theMarker.geometry.type);
                    }
                    break;

                    // PNG icon -- cannot be a Polygon!
                case '@':
                    var pngTitle = fKey.substring(1);
                    var pngIcon = dhpMapsView.pngIcons[pngTitle];
                    if (pngIcon == undefined || pngIcon === null) {
                        throw new Error("Could not find PNG image for: "+pngTitle);
                    }
                    if (theMarker.geometry.type === 'Point') {
                        aNewMarker = L.marker(theMarker.geometry.coordinates, {
                            id: markerIndex, icon: pngIcon, riseOnHover: true
                        });
                    } else {
                        throw new Error("Cannot use PNG icon Legends with non-Point Markers: "+theMarker.geometry.type);
                    }
                    break;

                default:
                    throw new Error("Unsupported Legend viz value: "+fKey);
                } // switch
                if (aNewMarker) {
                    aNewMarker.on('click', dhpMapsView.markerClick);
                    dhpMapsView.markerLayer.addLayer(aNewMarker);
                }
            } // if found
        }); // forEach
    }, // createAllMarkers()


        // PURPOSE: Create object record for Legend value { id, viz }
        // ASSUMES: curLgndData is set to current legend terms array
        // NOTES:   The curLgndData has been "flattened" by dhpServices.flattenTerms()
    getLgndData: function(id)
    {
        var lgndData = { id: id };
        var iTerm1, term1, iTerm2, term2;       // 1st level and 2nd level terms
        var term1Size, term2Size;

        term1Size = dhpMapsView.curLgndData.length;
        for (iTerm1=0; iTerm1<term1Size; iTerm1++) {
            term1 = dhpMapsView.curLgndData[iTerm1];
                // Don't process "head" entry (for Legend name itself)
            if (term1.name !== dhpMapsView.curLgndName) {
                if (term1.id === id) {
                    lgndData.viz = term1.icon_url;
                    return lgndData;
                }
                    // The following is not necessary because data is flattened
                // if (term1.children) {
                //     term2Size = term1.children.length;
                //     for (iTerm2=0; iTerm2<term2Size; iTerm2++) {
                //         term2 = term1.children[iTerm2];
                //         if (term2.id === id) {
                //             lgndData.viz = term1.icon_url;
                //             return lgndData;
                //         }
                //     }
                // }
            }
        }
        return null;
    }, // getLgndData()


        // PURPOSE: Compute the selLgnds array based on current menu selection
    computeSelLgnds: function()
    {
        var newSelection = [], tempRec;
        var legID, newIndex;

            // Go through all of the items selected in currently active legend
        jQuery('#legends .active-legend .compare input:checked').each(function(index) {
            legID = jQuery(this).closest('.row').find('.columns .value').data('id');
                // Insert in sorted order
            tempRec = { id: legID };
            newIndex = _.sortedIndex(newSelection, tempRec, 'id');
            newSelection.splice(newIndex, 0, dhpMapsView.getLgndData(legID));
        });
        dhpMapsView.selLgnds = newSelection;
    }, // computeSelLgnds()


        // PURPOSE: Handle user selection of legend in navbar menu
        // INPUT:   target = element selected by user
    switchLegend: function(target)
    {
            // Unhighlight the layers button in nav bar
        jQuery('#layers-button').parent().removeClass('active');

        var newLegend = jQuery(target).text();

            // If sliders are showing, then might just need to adjust Legend display, not recalculate
        if (dhpMapsView.slidersShowing || newLegend !== dhpMapsView.curLgndName) {
            dhpMapsView.slidersShowing = false;

                // Don't display current (or any) Legend
            jQuery('.legend-div').hide();
            jQuery('.legend-div').removeClass('active-legend');

                // Display selected legend (whose ID was stored in href)
            var action = jQuery(target).attr('href');
            jQuery(action).addClass('active-legend');
            jQuery(action).show();

                // Have to do extra check in case we are just switching out layer sliders
            if (newLegend !== dhpMapsView.curLgndName) {
                    // Update the markers to show on map
                dhpMapsView.switchFilter(newLegend);
                dhpMapsView.dhpUpdateSize();

                    // Change active menu item in navbar drop-down
                jQuery('.legend-dropdown > .active').removeClass('active');
                jQuery(target).parent().addClass('active');
            }
        }
    },  // switchLegend()


        // PURPOSE: Handle user selecting new legend category
        // INPUT:   filterName = name of legend/category selected (if null if first legend)
        // ASSUMES: rawAjaxData has been assigned, selectControl has been initialized
        // SIDE-FX: Changes catFilter
    switchFilter: function(filterName)
    {
        var filterObj;

        if (filterName) {
            filterObj = _.find(dhpMapsView.rawAjaxData, function(item) {
                return item.type === 'filter' && item.name === filterName;
            });

        } else {
            filterObj = dhpMapsView.rawAjaxData[0];
        }
        dhpMapsView.curLgndName = filterObj.name;
        dhpMapsView.curLgndData = filterObj.terms;
        dhpMapsView.computeSelLgnds();
        dhpMapsView.createAllMarkers();
    },  // switchFilter()


        // PURPOSE: Create HTML for all of the legends for this visualization
    createLegends: function() 
    {
        dhpServices.createLegends(dhpMapsView.menuLgnds, 'Layer Controls');

            // Handle user selection of value name from current Legend
        jQuery('#legends div.terms .row a').click(function(event) {
            var spanName = jQuery(this).data('id');

                // "Hide/Show all" button
            if (spanName==='all') {
                    // Should legend values now be checked or unchecked?
                var boxState = jQuery(this).closest('.row').find('input').prop('checked');
                jQuery('.active-legend .terms .row').find('input').prop('checked',!boxState);
            }
                // a specific legend/category value (ID#)
            else {
                    // uncheck everything
                jQuery('.active-legend .terms input').prop('checked', false);
                jQuery('.active-legend .terms .row.selected').removeClass('selected');
                    // select just this item
                jQuery(this).closest('.row').addClass('selected');
                jQuery(this).closest('.row').find('input').prop('checked', true);

                    // Child terms are hidden in menu -- selects them also automatically if parent is checked
                if (dhpMapsView.useParent) {
                    jQuery('.active-legend .terms .row').find('*[data-parent="'+spanName+'"]').each(function( index ) {
                        jQuery( this ).closest('.row').find('input').prop('checked',true);
                    }); 
                }
            }
                // Recompute selected legend values and Markers
            dhpMapsView.computeSelLgnds();
            dhpMapsView.createAllMarkers();
        });

            // Handle user selection of checkbox from current Legend
        jQuery('#legends div.terms input').click(function(event) {
            var checkAll = jQuery(this).closest('.row').hasClass('check-all');
            var boxState = jQuery(this).prop('checked');
            var spanName = jQuery(this).closest('.row').find('a').data('id');
                // "Hide/Show all" checkbox
            if (checkAll) {
                jQuery('.active-legend .terms .row').find('input').prop('checked',boxState);
            }
                // toggle individual terms
            else {
                jQuery('.active-legend .terms .check-all').find('input').prop('checked',false);

                    // Child terms are hidden in legend. This selects them if parent is checked
                if (dhpMapsView.useParent) {
                    jQuery('.active-legend .terms .row').find('*[data-parent="'+spanName+'"]').each(function(index) {
                        jQuery(this).closest('.row').find('input').prop('checked',true);
                    });
                }
            }
                // Recompute selected legend values and Markers
            dhpMapsView.computeSelLgnds();
            dhpMapsView.createAllMarkers();
        });

            // Handle selection of different Legends from navbar
        jQuery('.dhp-nav .legend-dropdown a').click(function(evt) {
            evt.preventDefault();
            dhpMapsView.switchLegend(evt.target);
        });

            // Handle selecting "Layer Sliders" button on navbar
        jQuery('#layers-button').click(function(evt) {
            evt.preventDefault();

                // Hide current Legend info
            jQuery('.legend-div').hide();
            jQuery('.legend-div').removeClass('active-legend');

                // Were sliders already showing? Make filter mote legend visible again
            if (dhpMapsView.slidersShowing) {
                    // Find the legend div that should be active now!
                var activeLegend = jQuery('.legend-title').filter(function() {
                    return (jQuery(this).text() === dhpMapsView.curLgndName);
                }).parent();

                jQuery(activeLegend).addClass('active-legend');
                jQuery(activeLegend).show();

                jQuery('#layers-button').parent().removeClass('active');

                dhpMapsView.slidersShowing = false;

                // Show sliders now
            } else {
                    // Show section of Legend with sliders
                jQuery('#layers-panel').addClass('active-legend');
                jQuery('#layers-panel').show();

                jQuery('#layers-button').parent().addClass('active');

                dhpMapsView.slidersShowing = true;
            }
        });

          // Show initial Legend selection and show it as active on the menu
        dhpMapsView.slidersShowing = false;
        // dhpMapsView.findSelectedCats();
    }, // createLegends()


        // PURPOSE: Create UI controls for opacity of each layer in Legend area
        // ASSUMES: map.layers has been initialized, settings are loaded
        //          HTML element "layers-panel" has been inserted into document
        // NOTE:    The final map layer is for Markers, so has no corresponding user settings
    buildLayerControls: function()
    {
        var layerOpacity, label;
        var layerSettings = dhpMapsView.mapEP.layers;
        _.each(dhpMapsView.mapLayers, function(thisLayer, index) {
                // Markers start out "fully on" by default
            if (index == dhpMapsView.mapLayers.length-1) {
                layerOpacity = 1;
                label = 'Markers (Circles only)';
            } else {
                layerOpacity = layerSettings[index].opacity || 1;
                label = thisLayer.options.layerName;
            }

                // Don't create checkbox or opacity slider for Blank layer
            if (thisLayer.options.id !== '.blank') {
                jQuery('#layers-panel').append('<div class="layer-set" id="layer'+index+'">'+
                    '<div><input type="checkbox" checked="checked"> '+
                    '<a class="value" id="'+thisLayer.options.id+'">'+label+'</a></div>'+
                    '<div><div class="layer-opacity"></div></div>'+
                    '</div>');

                jQuery('#layer'+index+' .layer-opacity').slider({
                    range: false,
                    min: 0,
                    max: 1,
                    step: 0.05,
                    values: [ layerOpacity ],
                    slide: function( event, ui ) {
                        dhpMapsView.layerOpacity(index, ui.values[0]);
                    }
                });
                    // Handle turning on and off map layer
                jQuery( '#layer'+index+' input').click(function() {
                    if (jQuery(this).is(':checked')) {
                        dhpMapsView.mapLeaflet.addLayer(thisLayer);
                    } else {
                        dhpMapsView.mapLeaflet.removeLayer(thisLayer);
                    }
                });
            }
        });
    }, // buildLayerControls()


        // PURPOSE: Callback to handle user setting of opacity slider
        // NOTES:   Opacity setting will only work for Circle (< Path) markers, not icons
        //          Because Marker layer is destroyed and rebuilt whenever Legend changes, need to
        //              pass index, not layer itself
    layerOpacity: function(index, val) {
        var layer = dhpMapsView.mapLayers[index];
            // Is it the Marker Layer?
        if (index == dhpMapsView.mapLayers.length-1) {
            dhpMapsView.markerOpacity = val;
            layer.setStyle( { fillOpacity: dhpMapsView.markerOpacity, opacity: dhpMapsView.markerOpacity });
        } else {
            layer.setOpacity(val);
        }
    }, // layerOpacity()


        // PURPOSE: Handle user selection of a marker on a map to bring up modal
        // INPUT:   e = event whose target is the feature selected on map
        //             HOWEVER! This also called from hover modal WITHOUT a parameter!
        // ASSUMES: currentFeature is set for reason noted above
    onFeatureSelect: function(e)
    {
        dhpServices.showMarkerModal(dhpMapsView.currentFeature);
    }, // onFeatureSelect()


        // PURPOSE: Resizes map-specific elements when browser size changes
    dhpUpdateSize: function()
    {
            // This is an Leaflet function to redraw the markers after map resize
        dhpMapsView.mapLeaflet.invalidateSize();
    } // dhpUpdateSize()
};
