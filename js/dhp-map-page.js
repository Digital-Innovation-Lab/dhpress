// PURPOSE: To render a single map into the div marked "dhp-visual" when looking at Map Library entries
//          Loaded by dhp_map_page_template() in dhp-map-library.php to show a Map view
// ASSUMES: That the code in dhp-map-template.php has embedded parameters into the HTML about
//            the map
//          Thus, the code assumes that only one map being shown!
// USES:    jQuery, Leaflet, dhpMapServices

jQuery(document).ready(function($) {

        // Get map parameters from the hidden input values
        // Wrap into array to pass to dhpMapServices
    var singleOverlayArray = { };
    singleOverlayArray.id       = $('#map-id').val();
    singleOverlayArray.sname    = $('#map-sname').val();
    singleOverlayArray.url      = $('#map-url').val();
    singleOverlayArray.subd     = $('#map-subdomains').val();
    singleOverlayArray.credits  = $('#map-credits').val();
    singleOverlayArray.desc     = $('#map-desc').val();
    singleOverlayArray.minZoom  = $('#map-zoom-min').val();
    singleOverlayArray.maxZoom  = $('#map-zoom-max').val();
    singleOverlayArray.inverseY = $('#map-inverse-y').val();
    singleOverlayArray.swBounds = [$('#map-s_bounds').val(), $('#map-w_bounds').val()];
    singleOverlayArray.neBounds = [$('#map-n_bounds').val(), $('#map-e_bounds').val()];

        // Initialize with Map Overlay library of just this entry
    dhpMapServices.init([singleOverlayArray]);

        // Setup Leaflet Map Object
    var dhpMapTest = L.map('dhp-visual',{ zoomControl: true, layerControl: true });
    $('#dhp-visual').width(600).height(500);

        // Create two possible base layers
    var mpq = dhpMapServices.createMapLayer('.mq-base', 1, dhpMapTest, null);
    var osm = dhpMapServices.createMapLayer('.osm-base', 1, dhpMapTest, null);
    var dhpBaseLayers = {
        'MapQuest': mpq,
        'OpenStreetMap' : osm
    };    

        // Create overlay layer object
    var mapLayer = dhpMapServices.createMapLayer(singleOverlayArray.id, 1, dhpMapTest, null);

    var overlay = {
        'Overlay': mapLayer
    };

        // Create controls to turn on and off each layer
    L.control.layers(dhpBaseLayers, overlay).addTo(dhpMapTest);

        // Center the map on the entire map layer
    dhpMapTest.fitBounds(L.latLngBounds(singleOverlayArray.swBounds, singleOverlayArray.neBounds));
});