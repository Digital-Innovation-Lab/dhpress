// PURPOSE: Handle low-level Map interface between DH Press and Leaflet
//			DH Press 2.6 (replaces previous map functionality)
//			Base Layers are built-in; Overlay layers can be added via Map Library
// ASSUMES: Hidden data in DIV ID dhp-map-data for info about map layers
//          Initial project settings embedded in HTML in DIV ID project_settings by ##
// USES:    Libraries jQuery, underscore, Leaflet
// NOTES:   Don't rely on WordPress post ID of map library entries -- Use unique ID
//			There is only one hub at a time so no need for instantiating instances
//			dhpHub is implemented with the "Module" design pattern for hiding
//				private variables and minimizing public accessibility

//			A Map Entry (as passed in arrays to init) consists of the following fields:
//				id = unique identifier [String]; if starts with "." it is a Base map
//				sname = a short name [String]
//				url = base pattern for URL
//				subd = subdomain(s) (optional)
//				credits = credit for map (optional)
//				desc = description [String] (optional)
//			Overlay Maps also contain the following information:
//				minZoom = integer
//				maxZoom = integer
//				swBounds = LatLong
//				neBounds = LatLong


var dhpMapServices = (function () {
		// Built-in Base Layers
	var baseLayers	= [
		{ id: '.blank', sname: 'Blank', url: '', subd: '', credits: '', desc: 'Blank Base Map' },
		{	id: '.mq-aerial', sname: 'MQ OpenAerial',
			baseurl: 'http://{s}.mqcdn.com/tiles/1.0.0/sat/{z}/{x}/{y}.jpg',
			subd: 'otile1|otile2|otile3|otile4', credits: 'MapQuest', desc: 'MapQuest Open Aerial Base Map'
		},
		{	id: '.mq-base', sname: 'Map Quest OSM Base',
			url: 'http://{s}.mqcdn.com/tiles/1.0.0/osm/{z}/{x}/{y}.png',
			subd: 'otile1|otile2|otile3|otile4', credits: 'MapQuest', desc: 'MapQuest Default Base Map'
		},
		{	id: '.osm-base', sname: 'OSM Base',
			url: 'http://{s}.tile.osm.org/{z}/{x}/{y}.png',
			subd: '', credits: 'OpenStreetMap', desc: 'OpenStreetMap Base Map'
		}
	];
	var overLayers	= [];


	function doGetBaseByID(id)
	{
		var index = _.sortedIndex(baseLayers, { id: id }, 'id');
		var item = baseLayers[index];
		return item.id === id ? item : null;
	}; // doGetBaseByID()


	function doGetOverlayByID(id)
	{
		var index = _.sortedIndex(overLayers, { id: id }, 'id');
		var item = overLayers[index];
		return item.id === id ? item : null;
	}; // doGetOverlayByID()


		// The Interface return object
	return {
			// PURPOSE: Initialize Map Services
			// INPUT:   overlay = array of overlay map data
		init: function(overlayArray)
		{
			overLayers = overlayArray;
		}, // init()


			// PURPOSE: Return array of available base layers
		getBaseLayers: function()
		{
			return baseLayers;
		}, // getBaseLayers()


			// PURPOSE: Return array of available overlay layers
		getOverlays: function()
		{
			return overLayers;
		}, // getOverlays()


			// PURPOSE: Return base map object by id
		getBaseByID: function(id)
		{
			return doGetBaseByID(id);
		}, // getBaseByID()


			// PURPOSE: Return overlay map object by id
		getOverlayByID: function(id)
		{
			return doGetOverlayByID(id);
		}, // getOverlayByID()


			// PURPOSE: Return map object by id
		getMapByID: function(id)
		{
			if (id.charAt(0) === '.')
				return doGetBaseByID(id);
			else
				return doGetOverlayByID(id);
		}, // getMapByID()


			// PURPOSE: Create a Leaflet map layer, add to Leaflet control
			// INPUT: 	id = unique ID of map layer
			//			opacity = initial opacity of layer
			//			leafMap = Leaflet map to which to add the layer, or null if none
			//			control = Leaflet control to which to add the layer, or null if none
			// RETURNS: Object representing Leaflet layer with fields
			//				leafletLayer
			//				options
			//					opacity
			//					layerName
			//					isBaseLayer [true|false]
		createMapLayer: function(id, opacity, leafMap, control)
		{
			var layerDef, newLayerRec, newLeafLayer, subDomains;

				// Prepare return layer data
			newLayerRec = { };
			newLayerRec.options = { };
			newLayerRec.options.opacity = opacity;

			if (id === '.blank') {
				newLayerRec.options.layerName = 'Blank';
				newLayerRec.options.isBaseLayer = true;
				if (leafMap) {
					leafMap.minZoom = 1;
					leafMap.maxZoom = 20;	
				}

			} else if (id.charAt(0) === '.') {
				layerDef = doGetBaseByID(id);

				newLayerRec.options.isBaseLayer = true;

				subDomains = (layerDef.subd && layerDef.subd !== '') ? layerDef.subd.split('|') : [];
				if (subDomains.length>1) {
					newLeafLayer = L.tileLayer(layerDef.url, {
						subdomains: subDomains,
						attribution: layerDef.credits,
						maxZoom: 20,
						opacity: opacity
					});
				} else {
					newLeafLayer = L.tileLayer(layerDef.url, {
						attribution: layerDef.credits,
						maxZoom: 20,
						opacity: opacity
					});
				}
				newLayerRec.leafletLayer = newLeafLayer;

				if (leafMap)
					newLeafLayer.addTo(leafMap);
				if (control)
                	control.addBaseLayer(newLeafLayer, layerDef.sname);

			} else {
				layerDef = doGetOverlayByID(id);

				newLayerRec.options.isBaseLayer = false;

				subDomains = (layerDef.subd && layerDef.subd !== '') ? layerDef.subd.split('|') : [];
				if (subDomains.length>1) {
					newLeafLayer = L.tileLayer(layerDef.url, {
						subdomains: subDomains,
						attribution: layerDef.credits,
						minZoom: layerDef.minZoom,
						maxZoom: layerDef.maxZoom,
						opacity: opacity,
						bounds: L.latLngBounds(layerDef.swBounds, layerDef.neBounds)
					});
				} else {
					newLeafLayer = L.tileLayer(layerDef.url, {
						attribution: layerDef.credits,
						minZoom: layerDef.minZoom,
						maxZoom: layerDef.maxZoom,
						opacity: opacity,
						bounds: L.latLngBounds(layerDef.swBounds, layerDef.neBounds)
					});
				}
				newLayerRec.leafletLayer = newLeafLayer;

				if (leafMap)
					newLeafLayer.addTo(leafMap);
				if (control)
					control.addOverlay(newLeafLayer, layerDef.sname);
			}
			return newLayerRec;
		} // createMapLayer()
	} // return

})();
