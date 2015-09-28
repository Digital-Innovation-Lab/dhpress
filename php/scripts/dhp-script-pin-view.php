    <!-- Templates for Pinboard View -->
<script id="dhp-script-pin-leg-menu" type="x-tmpl-mustache">
  <li class="has-dropdown">
      <a href="#"><?php _e('Legends', 'dhpress'); ?></a>
      <ul class="dropdown legend-dropdown">
          <!-- links -->
      </ul>
  </li>
  <li class="divider"></li>
</script>

<script id="dhp-script-pin-layer-menu" type="x-tmpl-mustache">
  <li><a id="layers-button" href="#"><?php _e('Opacities', 'dhpress'); ?></a></li>
</script>

<script id="dhp-script-pin-iconpanel" type="x-tmpl-mustache">
<div id="dhp-pin-controls">
	<div class="pin-fndn-icon">
		<i class="fi-arrow-left" title="<?php _e('Move Image Left', 'dhpress'); ?>" id="pin-left"></i>
		<i class="fi-arrow-right" title="<?php _e('Move Image Right', 'dhpress'); ?>" id="pin-right"></i>
		<i class="fi-arrow-down" title="<?php _e('Move Image Down', 'dhpress'); ?>" id="pin-down"></i>
		<i class="fi-arrow-up" title="<?php _e('Move Image Up', 'dhpress'); ?>" id="pin-up"></i>
		<i class="fi-arrows-in" title="<?php _e('Reduce Image Size', 'dhpress'); ?>" id="pin-reduce"></i>
		<i class="fi-arrows-out" title="<?php _e('Zoom Image', 'dhpress'); ?>" id="pin-zoom"></i>
		<i class="fi-refresh" title="<?php _e('Reset Image Settings', 'dhpress'); ?>" id="pin-refresh"></i>
	</div>
</div>
</script>

<script id="dhp-script-pin-lbl-opacities" type="x-tmpl-mustache">
<?php _e('Layer Opacities', 'dhpress'); ?>
</script>

<script id="dhp-script-bkgnd-slider" type="x-tmpl-mustache">
<div class="layer-set" id="layer-opct-base">
	<div><input type="checkbox" checked="checked">
		<a class="value"><?php _e('Background Image', 'dhpress'); ?></a>
	</div>
	<div>
		<div class="layer-opacity">
		</div>
	</div>
</div>
</script>

<script id="dhp-script-mrkr-slider" type="x-tmpl-mustache">
<div class="layer-set" id="layer-opct-markers">
	<div>
		<a class="value"><?php _e('Markers', 'dhpress'); ?></a>
	</div>
	<div>
		<div class="layer-opacity">
		</div>
	</div>
</div>
</script>

<script id="dhp-script-layer-slider" type="x-tmpl-mustache">
<div class="layer-set" id="layer-opct-{{i}}">
	<div>
		<input type="checkbox" checked="checked">
		<a class="value" id="layer-opct-a-{{i}}">{{label}}</a>
	</div>
	<div>
		<div class="layer-opacity">
		</div>
	</div>
</div>
</script>
