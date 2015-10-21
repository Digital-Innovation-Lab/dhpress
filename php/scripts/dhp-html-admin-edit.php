
<div id="ko-dhp">
    <p data-bind="visible: optionsCF.length == 0" style="color: red">
      <?php _e('NOTE: You cannot configure your Project until you import Markers associated with this Project (by using the Project ID above). Make sure to click the Publish button on the right if you have just created your project with the <b>Add New</b> button.', 'dhpress'); ?>
    </p>

    <div data-bind="visible: optionsCF.length > 0">
      <button id="btnSaveSettings" data-bind="jqButton, click: saveSettings, style: { color: settingsDirty() ? 'red' : 'green' }"><?php _e('Save Settings', 'dhpress'); ?></button>
      <!-- <button id="exportSaveSettings" data-bind="jqButton, click: exportSettings"><?php _e('Export Settings', 'dhpress'); ?></button> -->
      <button data-bind ="jqButton, click: showSettings"><?php _e('Show Settings in Console', 'dhpress'); ?></button>
    </div>

      <div id="accordion">
        <h3><?php _e('General Settings', 'dhpress'); ?></h3>
        <div><ul>
    	    <li><?php _e('Label for Home Button:', 'dhpress'); ?> <input data-bind="value: edHomeBtnLbl" type="text" size="12" placeholder="<?php _e('Home', 'dhpress'); ?>"/></li>
    	    <li><?php _e('Home URL:', 'dhpress'); ?> <input data-bind="value: edHomeURL" type="text" size="30" placeholder="http://"/></li>
          <li><?php _e('Marker Labels:', 'dhpress'); ?> <select data-bind="options: mTitleMoteNames, value: edMTitle" ></select>
          <li><?php _e('Primary Key (unique identifier used only for Pointers):', 'dhpress'); ?> <select data-bind="options: keyNames, value: edMKey" ></select>
    	  </ul></div>


      <h3><?php _e('Motes', 'dhpress'); ?></h3>
      <div>
          <p><?php _e('Create relational containers for data in WP custom fields', 'dhpress'); ?></p>
          <ul>
            <li><?php _e('Mote Name:', 'dhpress'); ?> <input data-bind="value: edMoteName" type="text" size="20"/></li>
            <li><?php _e('Custom Field:', 'dhpress'); ?> <select data-bind="value: edMoteCF, options: optionsCF"></select></li>
            <li>
            	<?php _e('Mote Type:', 'dhpress'); ?>
            	<select data-bind="value: edMoteType">
                    <option value="- choose -"><?php _e('- choose -', 'dhpress'); ?></option>
                    <option value="Short Text"><?php _e('Short Text', 'dhpress'); ?></option>
                    <option value="Long Text"><?php _e('Long Text', 'dhpress'); ?></option>
                    <option value="Lat/Lon Coordinates"><?php _e('Lat/Lon Coordinates', 'dhpress'); ?></option>
                    <option value="X-Y Coordinates"><?php _e('X-Y Coordinates', 'dhpress'); ?></option>
                    <option value="Date"><?php _e('Date', 'dhpress'); ?></option>
                    <option value="Pointer"><?php _e('Pointer', 'dhpress'); ?></option>
                    <option value="Image"><?php _e('Image', 'dhpress'); ?></option>
                    <option value="Link To"><?php _e('Link To', 'dhpress'); ?></option>
                    <option value="SoundCloud"><?php _e('SoundCloud', 'dhpress'); ?></option>
                    <option value="YouTube"><?php _e('YouTube', 'dhpress'); ?></option>
                    <option value="Transcript"><?php _e('Transcript', 'dhpress'); ?></option>
                    <option value="Timestamp"><?php _e('Timestamp', 'dhpress'); ?></option>
            	</select>
            </li>
            <li><?php _e('Value delimiter:', 'dhpress'); ?>  <input data-bind="value: edMoteDelim" type="text" size="2"/></li>
          </ul>
          <button data-bind="jqButton, click: createMote"><?php _e('Create New', 'dhpress'); ?></button>
          <br/>
          <p><b><?php _e('Currently Defined Motes:', 'dhpress'); ?></b></p>
          <div data-bind="template: { name: 'mote-template', foreach: allMotes }"></div>
      </div>


      <h3><?php _e('Entry Points', 'dhpress'); ?></h3>
      <div>
          <p>
            <button data-bind="jqButton, click: createBrowserEP"><?php _e('New Browser', 'dhpress'); ?></button>
            <button data-bind="jqButton, click: createMapEP"><?php _e('New Map', 'dhpress'); ?></button>
            <button data-bind="jqButton, click: createCardsEP"><?php _e('New Cards', 'dhpress'); ?></button>
            <button data-bind="jqButton, click: createPinEP"><?php _e('New Pinboard', 'dhpress'); ?></button>
            <button data-bind="jqButton, click: createTreeEP"><?php _e('New Tree', 'dhpress'); ?></button>
            <button data-bind="jqButton, click: createTimeEP"><?php _e('New Timeline', 'dhpress'); ?></button>
            <button data-bind="jqButton, click: createFlowEP"><?php _e('New Facet Flow', 'dhpress'); ?></button>
          </p>
          <div data-bind="template: { name: calcEPTemplate, foreach: entryPoints, as: 'theEP' }"></div>
      </div>


      <h3><?php _e('Views', 'dhpress'); ?></h3>
      <div>
          <div id="subaccordion">
            <h3><?php _e('Select Modal windows (item selected from visualization)', 'dhpress'); ?></h3>
            <div>
              <?php _e('Modal size:', 'dhpress'); ?>
              <select data-bind="value: edSelWidth">
                    <option value="tiny"><?php _e('Tiny', 'dhpress'); ?></option>
                    <option value="small"><?php _e('Small', 'dhpress'); ?></option>
                    <option value="medium"><?php _e('Medium', 'dhpress'); ?></option>
                    <option value="large"><?php _e('Large', 'dhpress'); ?></option>
                    <option value="x-large"><?php _e('X-Large', 'dhpress'); ?></option>
                </select>
              <br/>

              <?php _e('Link 1:', 'dhpress'); ?> <select data-bind="value: edSelLinkMt, options: getModalLinkNames"></select> <?php _e('Label:', 'dhpress'); ?> <input type="text" size="12" data-bind="value: edSelLinkLbl"/> <?php _e('Open new tab:', 'dhpress'); ?> <input type="checkbox" data-bind="checked: edSelLinkNewTab"><br/>
              <?php _e('Link 2:', 'dhpress'); ?> <select data-bind="value: edSelLink2Mt, options: getModalLinkNames"></select> <?php _e('Label:', 'dhpress'); ?> <input type="text" size="12" data-bind="value: edSelLink2Lbl"/> <?php _e('Open new tab:', 'dhpress'); ?> <input type="checkbox" data-bind="checked: edSelLink2NewTab"><br/>
              <?php _e('Widgets to show:', 'dhpress'); ?> <button data-bind="jqButton, click: addWidget"><?php _e('Add', 'dhpress'); ?></button> <?php _e('this widget:', 'dhpress'); ?>
              	<select id="selModalWidget">
              		<option value="scloud"><?php _e('SoundCloud', 'dhpress'); ?></option>
                  <option value="youtube"><?php _e('YouTube', 'dhpress'); ?></option>
              	</select><br/>
              <div data-bind="template: { name: 'widget-template', foreach: widgetList }"></div>

              <div data-bind="template: { name: 'sel-mote-template', foreach: selMoteList }"></div>
              <button data-bind="jqButton, click: addSelMote"><?php _e('Add Content Mote to Select Modal', 'dhpress'); ?></button>
            </div>

            <h3><?php _e('Post view (Marker pages)', 'dhpress'); ?></h3>
            <div>
              <?php _e('Page title:', 'dhpress'); ?> <select data-bind="value: edPostTitle, options: anyTxtDMoteNames"></select><br/>

              <div data-bind="template: { name: 'post-mote-template', foreach: postMoteList }"></div>
              <button data-bind="jqButton, click: addPostMote"><?php _e('Add Content Mote to Post Pages', 'dhpress'); ?></button><br/>
            </div>

            <h3><?php _e('Taxonomy/Archive/Category view', 'dhpress'); ?></h3>
            <div>
              <div data-bind="template: { name: 'tax-mote-template', foreach: taxMoteList }"></div>
              <button data-bind="jqButton, click: addTaxMote"><?php _e('Add Content Mote to Tax/Archive Pages', 'dhpress'); ?></button><br/>
            </div>

            <h3><?php _e('Playback Widgets', 'dhpress'); ?></h3>
            <div>
              <?php _e('Audio Source:', 'dhpress'); ?> <select data-bind="value: edTrnsAudio, options: soundMoteNames"></select><br/>
              <?php _e('Video Source:', 'dhpress'); ?> <select data-bind="value: edTrnsVideo, options: ytMoteNames"></select><br/>
              <?php _e('Transcript:', 'dhpress'); ?> <select data-bind="value: edTrnsTransc, options: transcMoteNames"></select><br/>
              <?php _e('Transcript 2:', 'dhpress'); ?> <select data-bind="value: edTrnsTransc2, options: transcMoteNames"></select><br/>
              <?php _e('Timecode:', 'dhpress'); ?> <select data-bind="value: edTrnsTime, options: tstMoteNames"></select><br/>
              <?php _e('Source:', 'dhpress'); ?> <select data-bind="value: edTrnsSrc, options: stdMoteNames"></select><br/>
            </div>
          </div> <!-- subaccordion -->
        </div> <!-- Views -->


      <h3><?php _e('Custom Field Utilities', 'dhpress'); ?></h3>
      <div>
          <p><?php _e('WARNING: These functions modify your data irrevocably and should be used with extreme caution. If you make changes to custom fields that correspond to Legends (Short Text motes), you will have to Rebuild them.', 'dhpress'); ?></p>
          <div id="subaccordion">
            <h3><?php _e('New Custom Field', 'dhpress'); ?></h3>
            <div>
              <?php _e('Create new custom field named', 'dhpress'); ?> <input type="text" size="15" id="newCFName"/><br/>
              <?php _e('Default value', 'dhpress'); ?> <input type="text" size="15" id="newCFDefault"/><br/>
              <button id="btnNewCF" data-bind="jqButton, click: createNewCF"><?php _e('Create Custom Field', 'dhpress'); ?></button>
            </div>

            <h3><?php _e('Delete Custom Field', 'dhpress'); ?></h3>
            <div>
              <button data-bind="jqButton, click: getDelCurrentCFs"><?php _e('Get Custom Fields', 'dhpress'); ?></button><br/>
              <?php _e('Delete this custom field:', 'dhpress'); ?> <select id="selDelCFList"> </select>
                <button id="btnDelOldCF" data-bind="jqButton, click: delOldCF"><?php _e('Delete', 'dhpress'); ?></button>
            </div>

            <h3><?php _e('Find/Replace Custom Field Values', 'dhpress'); ?></h3>
            <div>
              <button data-bind="jqButton, click: getFRCurrentCFs"><?php _e('Get Custom Fields', 'dhpress'); ?></button> <br/>
                <?php _e('Replace values in the field', 'dhpress'); ?> <select id="selFRCFSelect"></select> <br/>
              <input type="checkbox" id="getFRMustMatch"> <?php _e('For matches of the value', 'dhpress'); ?> 
                 <input type="text" size="20" id="edFRMatchValue"/> <?php _e('(always applied if no filter)', 'dhpress'); ?> <br/>
              <?php _e('With this value', 'dhpress'); ?> <input type="text" size="20" id="edFRCFvalue"/> <br/>
              <input type="checkbox" id="getFRFilterCF"> <?php printf(__('Only when the value in the field %1$s is %2$s', 'dhpress'), '<select id="selFRFilterCF"></select>', '<select id="selFRFilterValue"></select>'); ?> <br/>
              <button id="btnDoFR" data-bind="jqButton, click: doFRCF"><?php _e('Do Find/Replace', 'dhpress'); ?></button>
            </div>

          </div> <!-- subaccordion -->
      </div> <!-- Custom Field Utilities -->


      <h3><?php _e('Testing and Error-checking', 'dhpress'); ?></h3>
      <div>
        <button id="runTests" data-bind="jqButton, click: runTests"><?php _e('Run tests', 'dhpress'); ?></button></br>
        <div id="testResults"></div>
      </div>

    </div> <!-- accordion -->


<!-- ================== KO Templates ================== -->

<script type="text/html" id="mote-template">
    <li>
        <?php printf(__('%1$s of type %2$s from'), '<b><span data-bind="text: name"></span></b>', '<i><span data-bind="text: type"></span></i>'); ?>
        <?php printf(__('%1$s, delimiter [ %2$s ]'), '<span data-bind="text: cf"></span>', '<span data-bind="text: delim"></span>'); ?>
        <button data-bind="jqButton, click: $parent.editMote"><?php _e('Edit', 'dhpress'); ?></button>
        <button data-bind="jqButton, click: $parent.delMote"><?php _e('Delete', 'dhpress'); ?></button>
        <span data-bind="if: type == 'Short Text'">
            <button data-bind="jqButton, click: $parent.configCat" class="configCat"><?php _e('Configure', 'dhpress'); ?></button>
            <button id="btnRebuildMote" data-bind="jqButton, click: $parent.rebuildCat" class="rebuildCat"><?php _e('Rebuild', 'dhpress'); ?></button>
        </span>
    </li>
</script>

<!-- Map Entry Point Templates -->
<script type="text/html" id="ep-map-template">
    <h2><b><?php _e('Map Entry Point', 'dhpress'); ?></b>
      <button data-bind="jqButton, click: $root.delEP"><?php _e('Delete Map', 'dhpress'); ?></button>
      <span data-bind="if: $index() > 0">
        <button data-bind="jqButton, click: function() { $root.topEP($data, $index()); }"><?php _e('To Top', 'dhpress'); ?></button>
      </span>
      <span data-bind="if: $index() < $root.maxEPindex()">
        <button data-bind="jqButton, click: function() { $root.bottomEP($data, $index()); }"><?php _e('To Bottom', 'dhpress'); ?></button>
      </span>
    </h2>
    <ul>
        <li><?php _e('Map short label', 'dhpress'); ?> <input data-bind="value: theEP.label" type="text" size="12"/></li>
        <li><?php _e('Map Center (Lat)', 'dhpress'); ?>
            <input class="ed-lat-id" data-bind="value: theEP.settings.lat" type="text" size="7"/>
            <?php _x('(Long)', 'map longitude', 'dhpress'); ?> <input class="ed-lon-id" data-bind="value: theEP.settings.lon" type="text" size="7"/>
            <?php _e('Initial Zoom', 'dhpress'); ?> <input data-bind="value: theEP.settings.zoom" type="text" size="2"/>
          <?php _e('Cluster Markers', 'dhpress'); ?> <input type="checkbox" data-bind="checked: theEP.settings.cluster"/>
        </li>
        <li><?php _e('Marker Radius', 'dhpress'); ?> <select data-bind="value: theEP.settings.size">
                <option value="s"><?php _e('Small', 'dhpress'); ?></option>
                <option value="m"><?php _e('Medium', 'dhpress'); ?></option>
                <option value="l"><?php _e('Large', 'dhpress'); ?></option>
            </select>&nbsp;
          <?php _e('Marker Mote (for Lat/Lon)', 'dhpress'); ?> <select data-bind="value: theEP.settings.coordMote, options: $root.coordMoteNames"></select>
        </li>
        <div data-bind="template: { name: 'map-layer-template', foreach: theEP.settings.layers, as: 'theLayer' }"></div>
        <button data-bind="jqButton, click: $root.addMapLayer.bind(theEP)"><?php _e('Add Layer', 'dhpress'); ?></button>
        <div data-bind="template: { name: 'map-legend-template', foreach: theEP.settings.legends }"></div>
        <button data-bind="jqButton, click: $root.addMapLegend.bind(theEP)"><?php _e('Add Legend', 'dhpress'); ?></button>
    </ul>
</script>

<script type="text/html" id="map-layer-template">
    <li>
        <span data-bind="if: $index() == 0">
            <b><?php _e('Base Layer', 'dhpress'); ?></b>
            <select data-bind="value: theLayer.id, options: $root.baseLayers, optionsText: 'sname', optionsValue: 'id'"></select>
        </span>
        <span data-bind="if: $index() > 0">
            <b><?php _e('Overlay Layer', 'dhpress'); ?></b>
            <select data-bind="value: theLayer.id, options: $root.overLayers, optionsText: 'sname', optionsValue: 'id'"></select>
            <button data-bind="jqButton, click: function() { $root.delMapLayer($data, theEP, $index()); }"><?php _e('Delete', 'dhpress'); ?></button>
        </span>
        <?php _e('Opacity', 'dhpress'); ?> <span data-bind="text: theLayer.opacity"></span>
        <div class="opacity-slider"><div data-bind="opacitySlider: theLayer.opacity"></div></div>
    </li>
</script>

<script type="text/html" id="map-legend-template">
    <li>
        <b><?php _e('Legend', 'dhpress'); ?></b>: <select data-bind="value: name, options: $root.stMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delMapLegend($data, theEP, $index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>


<!-- Card Entry Point Template -->
<script type="text/html" id="ep-cards-template">
    <h2><b><?php _e('Cards Entry Point', 'dhpress'); ?></b>
      <button data-bind="jqButton, click: $root.delEP"><?php _e('Delete Cards', 'dhpress'); ?></button>
      <span data-bind="if: $index() > 0">
        <button data-bind="jqButton, click: function() { $root.topEP($data, $index()); }"><?php _e('To Top', 'dhpress'); ?></button>
      </span>
      <span data-bind="if: $index() < $root.maxEPindex()">
        <button data-bind="jqButton, click: function() { $root.bottomEP($data, $index()); }"><?php _e('To Bottom', 'dhpress'); ?></button>
      </span>
    </h2>
    <ul>
        <li><?php _e('Card short label', 'dhpress'); ?> <input data-bind="value: theEP.label" type="text" size="12"/></li>
        <li>
        <?php _e('Width', 'dhpress'); ?> <select data-bind="value: theEP.settings.width">
                              <option value="auto"><?php _e('Automatic', 'dhpress'); ?></option>
                              <option value="thin"><?php _e('Thin', 'dhpress'); ?></option>
                              <option value="med-width"><?php _e('Medium', 'dhpress'); ?></option>
                              <option value="wide"><?php _e('Wide', 'dhpress'); ?></option>
              </select>
        <?php _e('Height', 'dhpress'); ?> <select data-bind="value: theEP.settings.height">
                              <option value="auto"><?php _e('Automatic', 'dhpress'); ?></option>
                              <option value="short"><?php _e('Short', 'dhpress'); ?></option>
                              <option value="med-height"><?php _e('Medium', 'dhpress'); ?></option>
                              <option value="tall"><?php _e('Tall', 'dhpress'); ?></option>
              </select>
        <?php _e('Show marker title at top of card', 'dhpress'); ?> <input type="checkbox" data-bind="checked: theEP.settings.titleOn"/>
        </li>
        <li>
          <?php _e('Card Color (must be configured for colors)', 'dhpress'); ?> <select data-bind="value: theEP.settings.color, options: $root.stdMoteNames"></select> 
          <?php _e('Default Color', 'dhpress'); ?> <input data-bind="value: theEP.settings.defColor" type="text" size="9"/>
        </li>
        <li><?php _e('Background Color', 'dhpress'); ?> <input data-bind="value: theEP.settings.bckGrd" type="text" size="12"/> <?php _e('(CSS color name, #hexvalue, or blank for default)', 'dhpress'); ?></li>
        <div data-bind="template: { name: 'cards-content-template', foreach: theEP.settings.content }"></div>
        <button data-bind="jqButton, click: $root.addCardContent.bind(theEP)"><?php _e('Add Content Mote', 'dhpress'); ?></button>

        <div data-bind="template: { name: 'cards-filter-template', foreach: theEP.settings.filterMotes }"></div>
        <button data-bind="jqButton, click: $root.addCardFilter.bind(theEP)"><?php _e('Add Filter Mote', 'dhpress'); ?></button>

        <div data-bind="template: { name: 'cards-sort-template', foreach: theEP.settings.sortMotes }"></div>
        <button data-bind="jqButton, click: $root.addCardSort.bind(theEP)"><?php _e('Add Sort Mote', 'dhpress'); ?></button>
    </ul>
</script>

<script type="text/html" id="cards-content-template">
    <li>
        <?php _e('<b>Content mote</b>:', 'dhpress'); ?> <select data-bind="value: name, options: $root.cardContentMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delCardContent($data, theEP, $index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>

<script type="text/html" id="cards-filter-template">
    <li>
        <?php _e('<b>Filter mote</b>:', 'dhpress'); ?> <select data-bind="value: name, options: $root.compMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delCardFilter($data, theEP, $index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>

<script type="text/html" id="cards-sort-template">
    <li>
        <?php _e('<b>Sort mote</b>:', 'dhpress'); ?> <select data-bind="value: name, options: $root.compMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delCardSort($data, theEP, $index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>


<!-- Pinboard Point Templates -->
<script type="text/html" id="ep-pin-template">
    <h2><?php _e('<b>Pinboard Entry Point</b>', 'dhpress'); ?>
      <button data-bind="jqButton, click: $root.delEP"><?php _e('Delete Pinboard', 'dhpress'); ?></button>
      <span data-bind="if: $index() > 0">
        <button data-bind="jqButton, click: function() { $root.topEP($data, $index()); }"><?php _e('To Top', 'dhpress'); ?></button>
      </span>
      <span data-bind="if: $index() < $root.maxEPindex()">
        <button data-bind="jqButton, click: function() { $root.bottomEP($data, $index()); }"><?php _e('To Bottom', 'dhpress'); ?></button>
      </span>
    </h2>
    <ul>
        <li><?php _e('Pinboard short label', 'dhpress'); ?> <input data-bind="value: theEP.label" type="text" size="12"/></li>
        <li><?php _e('Display Frame Size: Width (in pixels)', 'dhpress'); ?>
            <input data-bind="value: theEP.settings.dw" type="number" size="4"/>
            <?php _e('Height (in pixels)', 'dhpress'); ?> <input data-bind="value: theEP.settings.dh" type="number" size="4"/>
        </li>
        <li><?php _e('Background Color', 'dhpress'); ?> <input data-bind="value: theEP.settings.bckGrd" type="text" size="12"/> <?php _e('(CSS color name, #hexvalue, or blank for default)', 'dhpress'); ?> </li>
        <li><?php _e('Background Image URL', 'dhpress'); ?>
            <input data-bind="value: theEP.settings.imageURL" type="text" size="48"/>
        </li>
        <li><?php _e('Background Image Size: Width (in pixels)', 'dhpress'); ?>
            <input data-bind="value: theEP.settings.iw" type="number" size="4"/>
            <?php _e('Height (in pixels)', 'dhpress'); ?> <input data-bind="value: theEP.settings.ih" type="number" size="4"/>
        </li>
        <li><?php _e('Pin marker: Size (circle and diamond only)', 'dhpress'); ?> <select data-bind="value: theEP.settings.size">
                <option value="s"><?php _e('Small', 'dhpress'); ?></option>
                <option value="m"><?php _e('Medium', 'dhpress'); ?></option>
                <option value="l"><?php _e('Large', 'dhpress'); ?></option>
            </select>&nbsp;
          <?php _e('Shape', 'dhpress'); ?> <select data-bind="value: theEP.settings.icon">
                <option value="circle"><?php _e('Circle', 'dhpress'); ?></option>
                <option value="diamond"><?php _e('Diamond', 'dhpress'); ?></option>
                <option value="ballon"><?php _e('Ballon', 'dhpress'); ?></option>
                <option value="tack"><?php _e('Tack', 'dhpress'); ?></option>
                <option value="mag"><?php _e('Magnifying Glass', 'dhpress'); ?></option>
            </select>&nbsp;
          <?php _e('Mote (for X-Y)', 'dhpress'); ?> <select data-bind="value: theEP.settings.coordMote, options: $root.xyMoteNames"></select>
        </li>
        <li><?php _e('Animation Script URL', 'dhpress'); ?> <input data-bind="value: theEP.settings.animscript" type="text" size="48"  placeholder="<?php _e('Leave blank for none', 'dhpress'); ?>"/><li>
        <li><?php _e('Animation SVG URL', 'dhpress'); ?> <input data-bind="value: theEP.settings.animSVG" type="text" size="48"  placeholder="<?php _e('Leave blank for none', 'dhpress'); ?>"/><li>
        <li><?php _e('Animation YouTube', 'dhpress'); ?> <input data-bind="value: theEP.settings.ytvcode" type="text" size="48" placeholder="<?php _e('YT code only - Leave blank for none', 'dhpress'); ?>"/><li>
        <div data-bind="template: { name: 'pin-layer-template', foreach: theEP.settings.layers, as: 'theLayer' }"></div>
        <button data-bind="jqButton, click: $root.addPinLayer.bind(theEP)"><?php _e('Add SVG Layer File', 'dhpress'); ?></button>
        <div data-bind="template: { name: 'pin-legend-template', foreach: theEP.settings.legends }"></div>
        <button data-bind="jqButton, click: $root.addPinLegend.bind(theEP)"><?php _e('Add Legend', 'dhpress'); ?></button>
    </ul>
</script>

<script type="text/html" id="pin-layer-template">
    <li>
      <?php _e('<b>SVG Layer File</b>', 'dhpress'); ?>
      <input data-bind="value: theLayer.label" type="text" size="12" placeholder="<?php _e('Label', 'dhpress'); ?>"/> </input>
      <input data-bind="value: theLayer.file" type="text" size="48" placeholder="<?php _e('File URL', 'dhpress'); ?>"/> </input>
      <button data-bind="jqButton, click: function() { $root.delPinLayer($data, theEP, $index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>

<script type="text/html" id="pin-legend-template">
    <li>
        <?php _e('<b>Legend</b>:', 'dhpress'); ?> <select data-bind="value: name, options: $root.stMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delPinLegend($data, theEP, $index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>


<!-- Tree Entry Point Templates -->
<script type="text/html" id="ep-tree-template">
    <h2><?php _e('<b>Tree Entry Point</b>', 'dhpress'); ?>
      <button data-bind="jqButton, click: $root.delEP"><?php _e('Delete Tree', 'dhpress'); ?></button>
      <span data-bind="if: $index() > 0">
        <button data-bind="jqButton, click: function() { $root.topEP($data, $index()); }"><?php _e('To Top', 'dhpress'); ?></button>
      </span>
      <span data-bind="if: $index() < $root.maxEPindex()">
        <button data-bind="jqButton, click: function() { $root.bottomEP($data, $index()); }"><?php _e('To Bottom', 'dhpress'); ?></button>
      </span>
    </h2>
    <ul>
        <li><?php _e('Tree short label', 'dhpress'); ?> <input data-bind="value: theEP.label" type="text" size="12"/></li>
        <li><?php _e('Tree Panel Size: Width (in pixels)', 'dhpress'); ?>
            <input data-bind="value: theEP.settings.width" type="number" size="4"/>
            <?php _e('Height (in pixels)', 'dhpress'); ?> <input data-bind="value: theEP.settings.height" type="number" size="4"/>
        </li>
        <li><?php _e('Type of Tree', 'dhpress'); ?> <select data-bind="value: theEP.settings.form">
                <option value="flat"><?php _e('Flat Tree', 'dhpress'); ?></option>
                <option value="radial"><?php _e('Radial Tree', 'dhpress'); ?></option>
                <option value="segment"><?php _e('Segmented Wheel', 'dhpress'); ?></option>
            </select>&nbsp;
            <?php _e('Padding (meaning depends on tree type)', 'dhpress'); ?> <input data-bind="value: theEP.settings.padding" type="number" size="3"/>
        </li>
        <li><?php _e('Head/Top Marker of Tree', 'dhpress'); ?> <input data-bind="value: theEP.settings.head" type="text" size="24"/>
            <?php _e('Children stored in', 'dhpress'); ?> <select data-bind="value: theEP.settings.children, options: $root.pointerMoteNames"></select>
        </li>
        <li><?php _e('Font size (pixels)', 'dhpress'); ?> <input data-bind="value: theEP.settings.fSize" type="number" size="2"/>
            <?php _e('Marker radius (pixels)', 'dhpress'); ?> <input data-bind="value: theEP.settings.radius" type="number" size="2"/>
        </li>
        <li><?php _e('Marker Color', 'dhpress'); ?> <select data-bind="value: theEP.settings.color, options: $root.stdMoteNames"></select> <?php _e('(Must be configured for colors)', 'dhpress'); ?></li>
    </ul>
</script>


<!-- Timeline Entry Point Templates -->
<script type="text/html" id="ep-time-template">
    <h2><?php _e('<b>Timeline Entry Point</b>', 'dhpress'); ?>
      <button data-bind="jqButton, click: $root.delEP"><?php _e('Delete Timeline', 'dhpress'); ?></button>
      <span data-bind="if: $index() > 0">
        <button data-bind="jqButton, click: function() { $root.topEP($data, $index()); }"><?php _e('To Top', 'dhpress'); ?></button>
      </span>
      <span data-bind="if: $index() < $root.maxEPindex()">
        <button data-bind="jqButton, click: function() { $root.bottomEP($data, $index()); }"><?php _e('To Bottom', 'dhpress'); ?></button>
      </span>
    </h2>
    <ul>
        <li><?php _e('Timeline short label', 'dhpress'); ?> <input data-bind="value: theEP.label" type="text" size="12"/></li>
        <li><?php _e('Date range', 'dhpress'); ?> <select data-bind="value: theEP.settings.date, options: $root.dateMoteNames"></select>
          <?php _e('Marker Color', 'dhpress'); ?> <select data-bind="value: theEP.settings.color, options: $root.stMoteNames"></select> <?php _e('(Must be configured for colors)', 'dhpress'); ?>
        </li>
        <li><?php _e('Height of each band (in pixels)', 'dhpress'); ?> <input data-bind="value: theEP.settings.bandHt" type="number" size="4"/>
          <?php _e('Width of start/end axis labels (in pixels)', 'dhpress'); ?> <input data-bind="value: theEP.settings.wAxisLbl" type="number" size="3"/>
        </li>
        <li><?php _e('Timeline start date', 'dhpress'); ?> <input data-bind="value: theEP.settings.from" type="text" size="12"/>
          <?php _e('to end date', 'dhpress'); ?> <input data-bind="value: theEP.settings.to" type="text" size="12"/>
        </li>
        <li><?php _e('Zoom window start date', 'dhpress'); ?> <input data-bind="value: theEP.settings.openFrom" type="text" size="12"/>
          <?php _e('to end date', 'dhpress'); ?> <input data-bind="value: theEP.settings.openTo" type="text" size="12"/>
        </li>
    </ul>
</script>


<!-- Facet Flow Entry Point Templates -->
<script type="text/html" id="ep-flow-template">
    <h2><?php _e('<b>Facet Flow Entry Point</b>', 'dhpress'); ?>
      <button data-bind="jqButton, click: $root.delEP"><?php _e('Delete Facet Flow', 'dhpress'); ?></button>
      <span data-bind="if: $index() > 0">
        <button data-bind="jqButton, click: function() { $root.topEP($data, $index()); }"><?php _e('To Top', 'dhpress'); ?></button>
      </span>
      <span data-bind="if: $index() < $root.maxEPindex()">
        <button data-bind="jqButton, click: function() { $root.bottomEP($data, $index()); }"><?php _e('To Bottom', 'dhpress'); ?></button>
      </span>
    </h2>
    <ul>
        <li><?php _e('Facet Flow short label', 'dhpress'); ?> <input data-bind="value: theEP.label" type="text" size="12"/></li>
        <li><?php _e('Flow Panel Size: Width (in pixels)', 'dhpress'); ?>
            <input data-bind="value: theEP.settings.width" type="number" size="4"/>
            <?php _e('Height (in pixels)', 'dhpress'); ?> <input data-bind="value: theEP.settings.height" type="number" size="4"/>
        </li>
        <li><?php _e('Facet Flow views require at least 2 Short Text mote Legends to work', 'dhpress'); ?></li>
        <div data-bind="template: { name: 'flow-mote-template', foreach: theEP.settings.motes }"></div>
        <button data-bind="jqButton, click: $root.addFlowMote.bind(theEP)"><?php _e('Add Mote to Flow', 'dhpress'); ?></button>
    </ul>
</script>

<script type="text/html" id="flow-mote-template">
    <li>
        <?php _e('<b>Legend</b>:', 'dhpress'); ?> <select data-bind="value: name, options: $root.stMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delFlowMote($data, theEP, $index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>


<!-- Facet Browser Entry Point Templates -->
<script type="text/html" id="ep-browser-template">
    <h2><?php _e('<b>Facet Browser Entry Point</b>', 'dhpress'); ?>
      <button data-bind="jqButton, click: $root.delEP"><?php _e('Delete Browser', 'dhpress'); ?></button>
      <span data-bind="if: $index() > 0">
        <button data-bind="jqButton, click: function() { $root.topEP($data, $index()); }"><?php _e('To Top', 'dhpress'); ?></button>
      </span>
      <span data-bind="if: $index() < $root.maxEPindex()">
        <button data-bind="jqButton, click: function() { $root.bottomEP($data, $index()); }"><?php _e('To Bottom', 'dhpress'); ?></button>
      </span>
    </h2>
    <ul>
        <li><?php _e('Browser short label', 'dhpress'); ?> <input data-bind="value: theEP.label" type="text" size="12"/></li>
        <li><?php _e('Group Date mote values by', 'dhpress'); ?> <select data-bind="value: theEP.settings.dateGrp">
                <option value="exact"><?php _e('Keep exact dates', 'dhpress'); ?></option>
                <option value="month"><?php _e('Same month', 'dhpress'); ?></option>
                <option value="year"><?php _e('Same year', 'dhpress'); ?></option>
                <option value="decade"><?php _e('Same decade', 'dhpress'); ?></option>
                <option value="century"><?php _e('Same century', 'dhpress'); ?></option>
            </select>
        </li>
        <li><?php _e('Browser views require at least 1 mote to work', 'dhpress'); ?></li>
        <div data-bind="template: { name: 'browser-mote-template', foreach: theEP.settings.motes }"></div>
        <button data-bind="jqButton, click: $root.addBrowserMote.bind(theEP)"><?php _e('Add Mote to Browser', 'dhpress'); ?></button>
    </ul>
</script>

<script type="text/html" id="browser-mote-template">
    <li>
        <?php _e('<b>Mote</b>:', 'dhpress'); ?> <select data-bind="value: name, options: $root.compMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delBrowserMote($data, theEP, $index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>


<!-- Views Data -->
<script type="text/html" id="widget-template">
    <li>
        <?php _e('<b>Widget</b>:', 'dhpress'); ?> <span data-bind="text: name"></span>
        <button data-bind="jqButton, click: function() { $root.delWidget($index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>

<script type="text/html" id="sel-mote-template">
    <li>
        <?php _e('<b>Modal content mote</b>:', 'dhpress'); ?> <select data-bind="value: name, options: $root.allMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delSelMote($index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>

<script type="text/html" id="post-mote-template">
    <li>
        <?php _e('<b>Post content mote</b>:', 'dhpress'); ?> <select data-bind="value: name, options: $root.allMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delPostMote($index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>

<script type="text/html" id="tax-mote-template">
    <li>
        <?php _e('<b>Taxonomy/Archive content mote</b>:', 'dhpress'); ?> <select data-bind="value: name, options: $root.allMoteNames"></select>
        <button data-bind="jqButton, click: function() { $root.delTaxMote($index()); }"><?php _e('Delete', 'dhpress'); ?></button>
    </li>
</script>


<!-- ================= Dialog boxes and modals -- put in hidden section ================= -->
<div class="hide">

    <div id="mdl-server-err" title="<?php _e('Server Error', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('There was an error on the server and your operation could not complete. Please try again.', 'dhpress'); ?></p>
    </div>

    <div id="mdl-del-mote" title="<?php _e('Delete mote?', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('Are you sure you wish to delete the mote definition and all references to it?', 'dhpress'); ?></p>
    </div>

    <div id="mdl-def-mote" title="<?php _e('Select Mote Parameters', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('You must select a valid Custom Field and Mote Type before you can create a mote.', 'dhpress'); ?></p>
    </div>

    <div id="mdl-no-cfs" title="<?php _e('No Custom Fields', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('You have not imported any Markers associated with this Project and therefore cannot define motes.', 'dhpress'); ?></p>
    </div>

    <div id="mdl-mote-name-badchars" title="<?php _e('Illegal Characters', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('This mote cannot be created with the name you have given. You can only use alphanumeric characters, spaces, underscores and hyphens in mote names.', 'dhpress'); ?></p>
    </div>

    <div id="mdl-edit-mote" title="<?php _e('Edit Mote', 'dhpress'); ?>">
        <h3 id="mdl-edit-mote-title"></h3>
        <?php _e('Name:', 'dhpress'); ?> <input id="edMoteModalName" type="text" size="20"/><br/>
        <?php _e('Mote Type:', 'dhpress'); ?>
        <select id="edMoteModalType">
            <option value="Short Text"><?php _e('Short Text', 'dhpress'); ?></option>
            <option value="Long Text"><?php _e('Long Text', 'dhpress'); ?></option>
            <option value="Lat/Lon Coordinates"><?php _e('Lat/Lon Coordinates', 'dhpress'); ?></option>
            <option value="X-Y Coordinates"><?php _e('X-Y Coordinates', 'dhpress'); ?></option>
            <option value="Date"><?php _e('Date', 'dhpress'); ?></option>
            <option value="Pointer"><?php _e('Pointer', 'dhpress'); ?></option>
            <option value="Image"><?php _e('Image', 'dhpress'); ?></option>
            <option value="Link To"><?php _e('Link To', 'dhpress'); ?></option>
            <option value="SoundCloud"><?php _e('SoundCloud', 'dhpress'); ?></option>
            <option value="YouTube"><?php _e('YouTube', 'dhpress'); ?></option>
            <option value="Transcript"><?php _e('Transcript', 'dhpress'); ?></option>
            <option value="Timestamp"><?php _e('Timestamp', 'dhpress'); ?></option>
        </select><br/>
        <?php _e('Custom Field:', 'dhpress'); ?> <select id="edMoteModalCF" data-bind="options: optionsCF"></select><br/>
        <?php _e('Value delimiter:', 'dhpress'); ?> <input id="edMoteModalDelim" type="text" size="2"/><br/>
        <p><?php _e('WARNING: Editing the mote definition will reset it in the rest of the settings.', 'dhpress'); ?> <span id="edMoteModalSTWarn"><?php _e('Delete this mote and recreate it if you have already created the corresponding legend and need to specify a different delimiter or custom field.', 'dhpress'); ?></span></p>
    </div> <!-- Edit Mote Modal -->

    <div id="mdl-del-ep" title="<?php _e('Delete entry point?', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('Are you sure you wish to delete this entry point?', 'dhpress'); ?></p>
    </div>

    <div id="mdl-rebuild-cat" title="<?php _e('Rebuild Legend?', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('Are you sure you wish to rebuild this Legend from scratch?', 'dhpress'); ?></p>
    </div>


    <div id="mdl-config-cat" title="<?php _e('Configure Legend', 'dhpress'); ?>">
        <h3 id="mdl-config-cat-title"></h3>
        <div>
            <input type="radio" name="viz-type-setting" value="colors"/><?php _e('Use Colors', 'dhpress'); ?>
            <input type="radio" name="viz-type-setting" value="icons"/><?php _e('Use Icons', 'dhpress'); ?>
            <input type="radio" name="viz-type-setting" value="pngs" id="use-png"/><?php _e('Use PNG images', 'dhpress'); ?>
            <button id="viz-type-reset" data-bind="jqButton"><?php _e('Reset All', 'dhpress'); ?></button>
            <input type="hidden" id="color-picker"></input>
        </div><br/>
        <button id="add-new-term"><?php _e('Add Term', 'dhpress'); ?></button> <input id="ed-new-term" type="text" size="16"/>
        <p class="wait-message"><?php _e('Please wait while the category/legend data is loaded.', 'dhpress'); ?></p>
        <div class="dd" id="category-tree">
            <ol class="dd-list">
            </ol>
        </div>
    </div> <!-- Configure Legend Modal -->


    <div id="mdl-del-cf" title="<?php _e('Delete custom field?', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('Are you sure you wish to delete this custom field entirely? This cannot be undone.', 'dhpress'); ?></p>
    </div>

    <div id="mdl-fr-cf" title="<?php _e('Find/Replace custom field?', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('Are you sure you wish to execute this find/replace operation on the custom field? This cannot be undone.', 'dhpress'); ?></p>
    </div>

    <div id="mdl-mote-name-too-long" title="<?php _e('Error', 'dhpress'); ?>">
        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php _e('The name of your mote is too long: a mote name cannot be longer than 32 characters.', 'dhpress'); ?></p>
    </div>

    <div id="mdl-select-color" title="<?php _e('Select Color', 'dhpress'); ?>">
      <p><?php _e('Select a color:', 'dhpress'); ?></p>
    </div>

    <div id="mdl-reset-color-options" title="<?php _e('Reset Colors', 'dhpress'); ?>">
      <p>
        <?php _e('<strong>Clear All:</strong> Resets all colors to default gray value', 'dhpress'); ?><br />
        <?php _e('<strong>Random Colors:</strong> Assigns a random color value to each term', 'dhpress'); ?><br />
        <?php _e('<strong>Gradient:</strong> Assigns a color value to each term within a range of colors:', 'dhpress'); ?>
      </p>

      <div id="gradient-colors">
        <input type="hidden" id="color-range" />
        <em><?php _e('Start Color:', 'dhpress'); ?> </em><div class="color-box"></div>
        <em><?php _e('End Color:', 'dhpress'); ?> </em><div class="color-box"></div> 
      </div>    
    </div>

    <div id="mdl-select-png" title="<?php _e('Select PNG image', 'dhpress'); ?>">
        <p id="mdl-select-png-title"></p>
        <ol class="png-list" id="select-png-list">
        </ol>
    </div>

    <div id="mdl-select-icon" title="<?php _e('Select Icon', 'dhpress'); ?>">
        <p id="mdl-select-icon-title"></p>
        <ol class="icon-list" id="select-icon-list">
            <li class="maki-icon circle-stroked"></li>
            <li class="maki-icon circle"></li>
            <li class="maki-icon square-stroked"></li>
            <li class="maki-icon square"></li>
            <li class="maki-icon triangle-stroked"></li>
            <li class="maki-icon triangle"></li>
            <li class="maki-icon star-stroked"></li>
            <li class="maki-icon star"></li>
            <li class="maki-icon cross"></li>
            <li class="maki-icon marker-stroked"></li>
            <li class="maki-icon marker"></li>
            <li class="maki-icon religious-jewish"></li>
            <li class="maki-icon religious-christian"></li>
            <li class="maki-icon religious-muslim"></li>
            <li class="maki-icon cemetery"></li>
            <li class="maki-icon rocket"></li>
            <li class="maki-icon airport"></li>
            <li class="maki-icon heliport"></li>
            <li class="maki-icon rail"></li>
            <li class="maki-icon rail-metro"></li>
            <li class="maki-icon rail-light"></li>
            <li class="maki-icon bus"></li>
            <li class="maki-icon fuel"></li>
            <li class="maki-icon parking"></li>
            <li class="maki-icon parking-garage"></li>
            <li class="maki-icon airfield"></li>
            <li class="maki-icon roadblock"></li>
            <li class="maki-icon ferry"></li>
            <li class="maki-icon harbor"></li>
            <li class="maki-icon bicycle"></li>
            <li class="maki-icon park"></li>
            <li class="maki-icon park2"></li>
            <li class="maki-icon museum"></li>
            <li class="maki-icon lodging"></li>
            <li class="maki-icon monument"></li>
            <li class="maki-icon zoo"></li>
            <li class="maki-icon garden"></li>
            <li class="maki-icon campsite"></li>
            <li class="maki-icon theatre"></li>
            <li class="maki-icon art-gallery"></li>
            <li class="maki-icon pitch"></li>
            <li class="maki-icon soccer"></li>
            <li class="maki-icon america-football"></li>
            <li class="maki-icon tennis"></li>
            <li class="maki-icon basketball"></li>
            <li class="maki-icon baseball"></li>
            <li class="maki-icon golf"></li>
            <li class="maki-icon swimming"></li>
            <li class="maki-icon cricket"></li>
            <li class="maki-icon skiing"></li>
            <li class="maki-icon school"></li>
            <li class="maki-icon college"></li>
            <li class="maki-icon library"></li>
            <li class="maki-icon post"></li>
            <li class="maki-icon fire-station"></li>
            <li class="maki-icon town-hall"></li>
            <li class="maki-icon police"></li>
            <li class="maki-icon prison"></li>
            <li class="maki-icon embassy"></li>
            <li class="maki-icon beer"></li>
            <li class="maki-icon restaurant"></li>
            <li class="maki-icon cafe"></li>
            <li class="maki-icon shop"></li>
            <li class="maki-icon fast-food"></li>
            <li class="maki-icon bar"></li>
            <li class="maki-icon bank"></li>
            <li class="maki-icon grocery"></li>
            <li class="maki-icon cinema"></li>
            <li class="maki-icon pharmacy"></li>
            <li class="maki-icon hospital"></li>
            <li class="maki-icon danger"></li>
            <li class="maki-icon industrial"></li>
            <li class="maki-icon warehouse"></li>
            <li class="maki-icon commercial"></li>
            <li class="maki-icon building"></li>
            <li class="maki-icon place-of-worship"></li>
            <li class="maki-icon alcohol-shop"></li>
            <li class="maki-icon logging"></li>
            <li class="maki-icon oil-well"></li>
            <li class="maki-icon slaughterhouse"></li>
            <li class="maki-icon dam"></li>
            <li class="maki-icon water"></li>
            <li class="maki-icon wetland"></li>
            <li class="maki-icon disability"></li>
            <li class="maki-icon telephone"></li>
            <li class="maki-icon emergency-telephone"></li>
            <li class="maki-icon toilets"></li>
            <li class="maki-icon waste-basket"></li>
            <li class="maki-icon music"></li>
            <li class="maki-icon land-use"></li>
            <li class="maki-icon city"></li>
            <li class="maki-icon town"></li>
            <li class="maki-icon village"></li>
            <li class="maki-icon farm"></li>
            <li class="maki-icon bakery"></li>
            <li class="maki-icon dog-park"></li>
            <li class="maki-icon lighthouse"></li>
            <li class="maki-icon clothing-store"></li>
            <li class="maki-icon polling-place"></li>
            <li class="maki-icon playground"></li>
            <li class="maki-icon entrance"></li>
            <li class="maki-icon heart"></li>
            <li class="maki-icon london-underground"></li>
            <li class="maki-icon minefield"></li>
            <li class="maki-icon rail-underground"></li>
            <li class="maki-icon rail-above"></li>
            <li class="maki-icon camera"></li>
            <li class="maki-icon laundry"></li>
            <li class="maki-icon car"></li>
            <li class="maki-icon suitcase"></li>
            <li class="maki-icon hairdresser"></li>
            <li class="maki-icon chemist"></li>
            <li class="maki-icon mobilephone"></li>
            <li class="maki-icon scooter"></li>
        </ol>
    </div> <!-- Select Icon Modal -->

</div>

</div> <!-- ko-dhp -->
