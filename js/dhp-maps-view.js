// DH Press Maps View -- contains all data and functions for rendering maps with help of dhpCustomMaps
// ASSUMES: An area for the map has been marked with HTML div as "dhp-visual"
//          That the custom maps "library" has already been loaded with corresponding map entries
// NOTES:   Format of Marker and Legend data is documented in dhp-project-functions.php

// USES:    JavaScript libraries jQuery, Underscore, Zurb Foundation, Leaflet


var dhpMapsView = (function () {
		// INTERNALS
	var mapEP; 				// initialization parameters needed later

	var rawAjaxData;		// raw data returned from AJAX
	var allMarkers;			// All marker posts assoc. w/ Project; see data desc in createMarkerArray() of dhp-project-functions.php

	var curLgndName;		// name of current Legend mote
	var curLgndData;		// points to terms array of current Legend
	var menuLgnds;			// array of legend data for purposes of menu
	var selLgnds;			// array of currently selected Legend IDs in order { id, viz }
	var	slidersShowing = false;	// true if Legend currently shows Layer sliders

	var makiSize;				// "s" | "m" | "l"
	var makiIcons = [];			// array of maki icons, indexed by name
	var pngIcons = [];			// array of PNG image icons, indexed by name
	var radius;					// radius of geometric markers
	var markerOpacity  = 1;     // opacity of marker layer (for all markers)

	var mapLayers = [];			// array of map layer data to display
	var mapLeaflet;				// Leaflet map object
	var markerLayer;			// Leaflet layer containing individual Markers
	var control;				// Leaflet map layer selection controller
	var useParent = true;		// if true (always true!), actions on parent term affect child terms

	var currentFeature;			// map feature currently highlighted or selected (with modal)
	var anyPopupsOpen;			// true when a popover modal is currently open


		// PURPOSE: Initialize map viewing area with controls
	function initializeMap2()
	{
		anyPopupsOpen = false;

			// Add map elements to nav bar
		jQuery('.dhp-nav .top-bar-section .left').append(Handlebars.compile(jQuery("#dhp-script-map-menus").html()));

			// But remove Legend drop-down if only one Legend
		if (mapEP.legends.length == 1) {
			jQuery('#legend-dropdown').remove();
		}

			// Insert Legend area -- Joe had "after" but menu off map above if not "append"
		jQuery('#dhp-visual').append(Handlebars.compile(jQuery("#dhp-script-legend-head").html()));

		jQuery('#dhp-visual').append('<div id="dhpMap"/>');

		   //create map with view
		mapLeaflet = L.map('dhpMap',{ zoomControl:false }).setView([mapEP.lat, mapEP.lon], mapEP.zoom);

		// jQuery('#dhp-visual').height(jQuery('#dhp-visual')-45);
	} // initializeMap2()


		// PURPOSE: Create base layers and overlays
	function createLayers()
	{
		var opacity;

			// The control object manages which layers are visible at any time (user selection)
		control = L.control.layers();
		control.addTo(mapLeaflet);

			// Compile map layer data into mapLayers array and create with Leaflet
		_.each(mapEP.layers, function(layerToUse, index) {
			var newLayer;

			opacity = layerToUse.opacity || 1;

			newLayer = dhpMapServices.createMapLayer(layerToUse.id, opacity,
							mapLeaflet, control);
			mapLayers.push(newLayer);
		}); // each sourceLayers
	} // createLayers()


		// PURPOSE: Create Leaflet map controls
	function createMapControls()
	{
		var layerControl = L.control.zoom({position: 'topright'});
		layerControl.addTo(mapLeaflet);

		// Reset button
		var resetControl = L.control({position: 'topright'});

		resetControl.onAdd = function (map) {
			this._div = L.DomUtil.create('div', 'reset-control leaflet-bar');
			this.update();
			return this._div;
		};
		resetControl.update = function (props) {
			this._div.innerHTML = '<a class="reset-map" ><i class="fi-refresh"></i></a>';
		};
		resetControl.addTo(mapLeaflet);
		jQuery('.reset-control').click(function(){
			resetMap();
		});
	} // createMapControls()


		// PURPOSE: Create marker objects for map visualization; called by loadMapMarkers()
		// INPUT:   geoData = all AJAX data as JSON object: Array of ["type", ...]
	function createDataObjects(data)
	{
		rawAjaxData = data;

		var legends = [];

			// Assign data to appropriate objects
		_.each(rawAjaxData, function(dataSet) {
			switch(dataSet.type) {
			case 'filter':
				legends.push(dhpServices.flattenTerms(dataSet));
				break;
			case 'FeatureCollection':
				allMarkers = dataSet;
				break;
			}
		});
		menuLgnds = legends;

			// First legend will be selected by default
		createLegends();
		createMarkerLayer();
		buildLayerControls();

			// Set filter to first Legend
		switchFilter();
	} // createDataObjects()


		// PURPOSE: Creates initial Marker Layer (only once -- not the Markers on it)
	function createMarkerLayer()
	{
			// Load language-dependent string
		var strMarkers = dhpServices.getText('#dhp-script-map-markers-label');

			// Is it a Clustering Marker Layer?
		if (mapEP.cluster) {
			markerLayer = new L.MarkerClusterGroup();
		} else {
			markerLayer = L.featureGroup();            
		}
			// Create options properties if they don't already exist
		markerLayer.options = markerLayer.options || { };
		markerLayer.options.layerName = strMarkers;

		markerLayer.addTo(mapLeaflet);
		control.addOverlay(markerLayer, strMarkers);

		mapLayers.push(markerLayer);
	} // createMarkerLayer()


		// PURPOSE: Handle click on feature
	function markerClick(e)
	{
		if (e.target && e.target.options) {
			var marker = rawAjaxData[rawAjaxData.length-1];
			marker = marker.features[e.target.options.id];
			dhpServices.showMarkerModal(marker);
		}
	} // markerClick()


		// PURPOSE: Remove the hover style
	function resetHighlight(e) {
		markerLayer.resetStyle(e.target);
	}

		// PURPOSE: Reset map view to default state
	function resetMap()
	{
		mapLeaflet.setView([mapEP.lat, mapEP.lon], mapEP.zoom);
	} // resetMap()


		// PURPOSE: Create all Markers based on current Legend selection
		// NOTES:   Set options.id in resulting Marker to index of Marker
	function createAllMarkers()
	{
		var markerArray, aNewMarker;

			// Remove all previous markers
		markerLayer.clearLayers();
			// Go through all markers
		markerArray = rawAjaxData[rawAjaxData.length-1];
		markerArray = markerArray.features;
		_.forEach(markerArray, function(theMarker, markerIndex) {
				// Reset results
			aNewMarker = null;
				// Find the first Legend match
			var sIndex, found=false, fKey, tempRec, lgndRec;
			for (var i=0; i<theMarker.properties.categories.length; i++) {
				tempRec = { id: theMarker.properties.categories[i] };
				sIndex = _.sortedIndex(selLgnds, tempRec, 'id');
				if (sIndex < selLgnds.length) {
					lgndRec = selLgnds[sIndex];
					if (lgndRec.id == tempRec.id) {
						fKey = lgndRec.viz;
						found = true;
						break;
					} // if
				} // if
			} // for

			if (found) {
				switch (fKey.charAt(0)) {
					// Color value -- Must be Point (1), Line (2) or Polygon (3)
				case '#':
					var type = theMarker.geometry.type;
					if (type === 1) {
						aNewMarker = L.circleMarker(theMarker.geometry.coordinates, {
							id: markerIndex, weight: 1, radius: radius,
							fillColor: fKey, color: "#000",
							opacity: markerOpacity, fillOpacity: markerOpacity
						});
					} else if (type === 2) {
						aNewMarker = L.polyline(theMarker.geometry.coordinates, {
							id: markerIndex, weight: 1, color: fKey,
							opacity: markerOpacity, weight: 4
						});

					} else {
						if (type !== 3) {
							throw new Error("Bad Marker type: "+type);
						} else {
							aNewMarker = L.polygon(theMarker.geometry.coordinates, {
								id: markerIndex, weight: 1, color: "#000", fillColor: fKey,
								opacity: markerOpacity, fillOpacity: markerOpacity
							});
						}
					}
					break;

					// Maki-icon -- cannot be a Polygon!
				case '.':
						// See if maki-icon has already been created and if not create it
					var iName = fKey.substring(1);
					var mIcon = makiIcons[iName];
					if (mIcon == undefined || mIcon == null) {
						mIcon = L.MakiMarkers.icon({
							icon: iName, color: "#12a",
							size: makiSize
						});
						makiIcons[iName] = mIcon;
					}
					if (theMarker.geometry.type === 1) {
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
					var pngIcon = pngIcons[pngTitle];
					if (pngIcon == undefined || pngIcon === null) {
						throw new Error("Could not find PNG image for: "+pngTitle);
					}
					if (theMarker.geometry.type === 1) {
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
					aNewMarker.on('click', markerClick);
					markerLayer.addLayer(aNewMarker);
				}
			} // if found
		}); // forEach
	} // createAllMarkers()


		// PURPOSE: Create object record for Legend value { id, viz }
		// ASSUMES: curLgndData is set to current legend terms array
		// NOTES:   The curLgndData has been "flattened" by dhpServices.flattenTerms()
	function getLgndData(id)
	{
		var lgndData = { id: id };
		var iTerm1, term1, iTerm2, term2;       // 1st level and 2nd level terms
		var term1Size, term2Size;

		term1Size = curLgndData.length;
		for (iTerm1=0; iTerm1<term1Size; iTerm1++) {
			term1 = curLgndData[iTerm1];
				// Don't process "head" entry (for Legend name itself)
			if (term1.name !== curLgndName) {
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
	} // getLgndData()


		// PURPOSE: Compute the selLgnds array based on current menu selection
	function computeSelLgnds()
	{
		var newSelection = [], tempRec;
		var legID, newIndex;

			// Go through all of the items selected in currently active legend
		jQuery('#legends .active-legend .compare input:checked').each(function(index) {
			legID = jQuery(this).closest('.row').find('.columns .value').data('id');
				// Insert in sorted order
			tempRec = { id: legID };
			newIndex = _.sortedIndex(newSelection, tempRec, 'id');
			newSelection.splice(newIndex, 0, getLgndData(legID));
		});
		selLgnds = newSelection;
	} // computeSelLgnds()


		// PURPOSE: Handle user selection of legend in navbar menu
		// INPUT:   target = element selected by user
	function switchLegend(target)
	{
			// Unhighlight the layers button in nav bar
		jQuery('#layers-button').parent().removeClass('active');

		var newLegend = jQuery(target).text();

			// If sliders are showing, then might just need to adjust Legend display, not recalculate
		if (slidersShowing || newLegend !== curLgndName) {
			slidersShowing = false;

				// Don't display current (or any) Legend
			jQuery('.legend-div').hide();
			jQuery('.legend-div').removeClass('active-legend');

				// Display selected legend (whose ID was stored in href)
			var action = jQuery(target).attr('href');
			jQuery(action).addClass('active-legend');
			jQuery(action).show();

				// Have to do extra check in case we are just switching out layer sliders
			if (newLegend !== curLgndName) {
					// Update the markers to show on map
				switchFilter(newLegend);
				dhpMapsView.dhpUpdateSize();

					// Change active menu item in navbar drop-down
				jQuery('.legend-dropdown > .active').removeClass('active');
				jQuery(target).parent().addClass('active');
			}
		}
	}  // switchLegend()


		// PURPOSE: Handle user selecting new legend category
		// INPUT:   filterName = name of legend/category selected (if null if first legend)
		// ASSUMES: rawAjaxData has been assigned, selectControl has been initialized
		// SIDE-FX: Changes catFilter
	function switchFilter(filterName)
	{
		var filterObj;

		if (filterName) {
			filterObj = _.find(rawAjaxData, function(item) {
				return item.type === 'filter' && item.name === filterName;
			});

		} else {
			filterObj = rawAjaxData[0];
		}
		curLgndName = filterObj.name;
		curLgndData = filterObj.terms;
		computeSelLgnds();
		createAllMarkers();
	}  // switchFilter()


		// PURPOSE: Create HTML for all of the legends for this visualization
	function createLegends() 
	{
		dhpServices.createLegends(menuLgnds, dhpServices.getText('#dhp-script-map-layer-ctrls'));

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
				if (useParent) {
					jQuery('.active-legend .terms .row').find('*[data-parent="'+spanName+'"]').each(function( index ) {
						jQuery( this ).closest('.row').find('input').prop('checked',true);
					}); 
				}
			}
				// Recompute selected legend values and Markers
			computeSelLgnds();
			createAllMarkers();
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
				if (useParent) {
					jQuery('.active-legend .terms .row').find('*[data-parent="'+spanName+'"]').each(function(index) {
						jQuery(this).closest('.row').find('input').prop('checked',true);
					});
				}
			}
				// Recompute selected legend values and Markers
			computeSelLgnds();
			createAllMarkers();
		});

			// Handle selection of different Legends from navbar
		jQuery('.dhp-nav .legend-dropdown a').click(function(evt) {
			evt.preventDefault();
			switchLegend(evt.target);
		});

			// Handle selecting "Layer Sliders" button on navbar
		jQuery('#layers-button').click(function(evt) {
			evt.preventDefault();

				// Hide current Legend info
			jQuery('.legend-div').hide();
			jQuery('.legend-div').removeClass('active-legend');

				// Were sliders already showing? Make filter mote legend visible again
			if (slidersShowing) {
					// Find the legend div that should be active now!
				var activeLegend = jQuery('.legend-title').filter(function() {
					return (jQuery(this).text() === curLgndName);
				}).parent();

				jQuery(activeLegend).addClass('active-legend');
				jQuery(activeLegend).show();

				jQuery('#layers-button').parent().removeClass('active');

				slidersShowing = false;

				// Show sliders now
			} else {
					// Show section of Legend with sliders
				jQuery('#layers-panel').addClass('active-legend');
				jQuery('#layers-panel').show();

				jQuery('#layers-button').parent().addClass('active');

				slidersShowing = true;
			}
		});
	} // createLegends()


		// PURPOSE: Callback to handle user setting of opacity slider
		// NOTES:   Opacity setting will only work for Circle (< Path) markers, not icons
		//          Because Marker layer is destroyed and rebuilt whenever Legend changes, need to
		//              pass index, not layer itself
	function layerOpacity(index, val)
	{
		var layer = mapLayers[index];
			// Is it the Marker Layer?
		if (index == mapLayers.length-1) {
			markerOpacity = val;
			layer.setStyle( { fillOpacity: val, opacity: val });
		} else {
			layer.setOpacity(val);
		}
	} // layerOpacity()


		// PURPOSE: Create UI controls for opacity of each layer in Legend area
		// ASSUMES: map.layers has been initialized, settings are loaded
		//          HTML element "layers-panel" has been inserted into document
		// NOTE:    The final map layer is for Markers, so has no corresponding user settings
		//			Clustering layer does not support changing opacity
	function buildLayerControls()
	{
		var lOpacity, label, disable;
		var layerSettings = mapEP.layers;
		var strMarkerLayer = dhpServices.getText('#dhp-script-map-markers-opacity');

		_.each(mapLayers, function(thisLayer, index) {
			disable = false;
				// Markers start out "fully on" by default
			if (index == mapLayers.length-1) {
				lOpacity = 1;
				label = strMarkerLayer;
				if (mapEP.cluster)
					disable = true;
			} else {
				lOpacity = layerSettings[index].opacity || 1;
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
					values: [ lOpacity ],
					disabled: disable,
					slide: function( event, ui ) {
						layerOpacity(index, ui.values[0]);
					}
				});
					// Handle turning on and off map layer
				jQuery( '#layer'+index+' input').click(function() {
					if (jQuery(this).is(':checked')) {
						mapLeaflet.addLayer(thisLayer);
					} else {
						mapLeaflet.removeLayer(thisLayer);
					}
				});
			}
		});
	} // buildLayerControls()


		// PURPOSE: Handle user selection of a marker on a map to bring up modal
		// INPUT:   e = event whose target is the feature selected on map
		//             HOWEVER! This also called from hover modal WITHOUT a parameter!
		// ASSUMES: currentFeature is set for reason noted above
	function onFeatureSelect(e)
	{
		dhpServices.showMarkerModal(currentFeature);
	} // onFeatureSelect()


		// EXTERNAL interface
	return {

			// PURPOSE: Initialize new leaflet map, layers, and markers
			// INPUT:   ajaxURL      = URL to WP
			//          projectID    = ID of project
			//          mapEP        = settings for map entry point (from project settings)
			//          viewParams   = array of data about map layers
			//                          (compiled by dhp_get_map_layer_data() in dhp-project-functions.php)
		initialize: function(ajaxURL, projectID, vizIndex, theMapEP, viewParams) {
			dhpMapServices.init(viewParams.layerData);

				// Save init data for later
			mapEP          = theMapEP;

			makiSize       = theMapEP.size;
			switch (theMapEP.size) {
			case "s":
				radius     = 4;
				break;
			case "m":
				radius     = 8;
				break;
			case "l":
				radius     = 12;
				break;
			}

			initializeMap2();
			createLayers();
			createMapControls();

				// Create Leaflet icons for each defined PNG image
			for (var i=0; i<viewParams.pngs.length; i++)
			{
				var thePNG = viewParams.pngs[i];
				var pngSize = [ thePNG.w, thePNG.h ];
				var pngAnchor = [ thePNG.w/2, thePNG.h ];
				pngIcons[thePNG.title] = L.icon(
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
					createDataObjects(JSON.parse(data));
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


			// PURPOSE: Resizes map-specific elements when browser size changes
		dhpUpdateSize: function()
		{
				// This is an Leaflet function to redraw the markers after map resize
			mapLeaflet.invalidateSize();
		} // dhpUpdateSize()

	} // return
})();
