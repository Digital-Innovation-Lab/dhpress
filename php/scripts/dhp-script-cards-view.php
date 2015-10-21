    <!-- Templates for Cards View -->

<script id="dhp-script-cards-sort">
  <li class="has-dropdown">
      <a href="#"><?php _e('Sort By', 'dhpress'); ?></a>
      <ul class="dropdown" id="dhp-cards-sort">
          <!-- links -->
      </ul>
  </li>
</script>

<script id="dhp-script-cards-filter-menu">
  <li class="has-dropdown">
      <a href="#"><?php _e('Filter By'); ?></a>
      <ul class="dropdown" id="dhp-cards-filter-menu">
          <!-- links -->
      </ul>
  </li>
  <li class="active"><a id="dhp-filter-set" href="#"><?php _e('Filter Options', 'dhpress'); ?></a></li>
  <li class="divider"></li>
  <li class="active"><a id="dhp-filter-reset" href="#"><?php _e('No Filter', 'dhpress'); ?></a></li>
</script>

<script id="dhp-script-fltrErrorModal">
  <div id="filterErrModal" class="reveal-modal small" data-reveal>
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="errorModalLabel"></h3>
      </div>
      <div class="modal-body clearfix">
      </div>
    </div> <!-- modal-content -->
    <a class="close-reveal-modal close-select-modal">&#215;</a>
  </div>
</script>

<script id="dhp-script-filterModal">
  <div id="filterModal" class="reveal-modal large" data-reveal>
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="filterModalLabel"></h3>
      </div>
      <div class="modal-body clearfix">
      </div>
      <div class="reveal-modal-footer clearfix ">
        <ul class="button-group left"><li><a class="button success close-select-modal"><?php _e('Apply', 'dhpress'); ?></a></li></ul>
        <ul class="button-group right"><li><a class="button close-select-modal"><?php _e('Cancel', 'dhpress'); ?></a></li></ul>
      </div>
    </div> <!-- modal-content -->
    <a class="close-reveal-modal close-select-modal">&#215;</a>
  </div> <!-- filterModal -->
</script>

<script id="dhp-script-filter-ltext">
  <?php _e('Filter by text (pattern)', 'dhpress'); ?> <input id="filter-text-input" type="text" size="8"/> <br/>
</script>

<script id="dhp-script-filter-stext">
  <input type="radio" name="filter-type" value="valSel"/><?php _e('Select Legend value(s)', 'dhpress'); ?>
  <div id="st-filter-vals"></div>
  <input type="radio" name="filter-type" value="text"/> <?php _e('Or filter by text pattern', 'dhpress'); ?> <input id="filter-text-input" type="text" size="12"/> <br/>
</script>

<script id="dhp-script-filter-dates">
  <div class="date-boxes"><?php _e('Date', 'dhpress'); ?> <input id="filter-date1Y-input" type="text" size="5" placeholder="<?php _e('Year', 'dhpress'); ?>"/> <input id="filter-date1M-input" type="text" size="2" placeholder="<?php _e('Month#', 'dhpress'); ?>"/> <input id="filter-date1D-input" type="text" size="2" placeholder="<?php _e('Date', 'dhpress'); ?>"/></div>
  <input type="radio" name="date1Order" value="before"/><?php _e('Before or', 'dhpress'); ?> <input type="radio" name="date1Order" value="after" checked="checked"/> <?php _e('After', 'dhpress'); ?><br/>

  <input type="checkbox" id="dateAnd"/> <?php _e('(override) After date above and before date below', 'dhpress'); ?> <br/>
  <div class="date-boxes"><?php _e('Date', 'dhpress'); ?> <input id="filter-date2Y-input" type="text" size="5" placeholder="<?php _e('Year', 'dhpress'); ?>"/> <input id="filter-date2M-input" type="text" size="2" placeholder="<?php _e('Month#', 'dhpress'); ?>"/> <input id="filter-date2D-input" type="text" size="2" placeholder="<?php _e('Date', 'dhpress'); ?>"/></div>
</script>

<!-- This must be the verbatim text for "Apply" button label that appears on all Filter modals -->
<script id="dhp-script-btn-apply">
<?php _e('Apply', 'dhpress'); ?>
</script>

<script id="dhp-script-lbl-filter" type="">
<?php _e('Filter options for {{label}}', 'dhpress'); ?>
</script>

<script id="dhp-script-err-date-filter">
<?php _e('Date Filter Error', 'dhpress'); ?>
</script>

<script id="dhp-script-err-req-val" type="">
<?php _e('The value for {{t}} is required but you left it blank.', 'dhpress'); ?>
</script>

<script id="dhp-script-err-invalid-num" type="">
<?php _e('The value you entered for {{t}} is not a valid number.', 'dhpress'); ?>
</script>

<script id="dhp-script-err-too-small" type="">
<?php _e('The value you entered for {{t}} is too small.', 'dhpress'); ?>
</script>

<script id="dhp-script-err-too-big" type="">
<?php _e('The value you entered for {{t}} is too large.', 'dhpress'); ?>
</script>

<script id="dhp-script-1st-year">
<?php _e('the first Date year', 'dhpress'); ?>
</script>

<script id="dhp-script-1st-month">
<?php _e('the first Date month', 'dhpress'); ?>
</script>

<script id="dhp-script-1st-day">
<?php _e('the first Date day', 'dhpress'); ?>
</script>

<script id="dhp-script-2nd-year">
<?php _e('the second Date year', 'dhpress'); ?>
</script>

<script id="dhp-script-2nd-month">
<?php _e('the second Date month', 'dhpress'); ?>
</script>

<script id="dhp-script-2nd-day">
<?php _e('the second Date day', 'dhpress'); ?>
</script>
