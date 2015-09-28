    <!-- Templates for Map View -->
<script id="dhp-script-map-menus" type="x-tmpl-mustache">
  <li id="legend-dropdown" class="has-dropdown">
      <a href="#"><?php _e('Legends', 'dhpress'); ?></a>
      <ul class="dropdown legend-dropdown">
          <!-- links -->
      </ul>
  </li>

  <li class="divider"></li>
  <li><a id="layers-button" href="#"><?php _e('Layer Sliders', 'dhpress'); ?></a></li>
</script>

<script id="dhp-script-map-markers-label" type="x-tmpl-mustache">
<?php _e('Markers', 'dhpress'); ?>
</script>

<script id="dhp-script-map-markers-opacity" type="x-tmpl-mustache">
<?php _e('Markers (Geometric only)', 'dhpress'); ?>
</script>

<script id="dhp-script-map-layer-ctrls" type="x-tmpl-mustache">
<?php _e('Layer Controls', 'dhpress'); ?>
</script>