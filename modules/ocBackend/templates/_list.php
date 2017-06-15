<div class="sf_admin_list ui-grid-table ui-widget ui-corner-all ui-helper-reset ui-helper-clearfix">
  
  <div class="gauge_content raw">
    <div class="gauge">
      <span class="text"></span>
      <span class="resa" style="width:0%;">&nbsp;</span>
    </div>
    
    <div class="gauge_first_choice">
      <span class="text"></span>
      <span class="resa" style="width:0%;">&nbsp;</span>
    </div>
  </div>
  
  <table class="real">
    
    <caption class="fg-toolbar ui-widget-header ui-corner-top">
      <h1><span class="ui-icon ui-icon-triangle-1-s"></span> <?php echo __('List of events', null, 'li_oc').' - '.$group ?></h1>
    </caption>

    <thead class="ui-widget-header plan_header" data-url="<?php echo url_for('oc_backend/events') ?>">
      <tr class="plan_day" data-day="<?php echo $day ?>"></tr>
      <tr class="plan_hours"></tr>
      <tr class="plan_events"></tr>
      <tr class="plan_gauges">
        <th class="sf_admin_text sf_admin_list_th_id ui-state-default ui-th-column participants">
            <span><?php echo __('Participants') ?></span>
        </th>
      </tr>
    </thead>

    <tbody class="plan_body" data-url="<?php echo url_for('oc_backend/pros') ?>">
    </tbody>
    
    <tfoot>
      <th class="sf_admin_text sf_admin_list_th_id ui-state-default ui-th-column"></th>
      <th><a href="#" class="fg-button ui-widget ui-state-default ui-corner-all"></a></th>
    </tfoot>
    
  </table>

</div>