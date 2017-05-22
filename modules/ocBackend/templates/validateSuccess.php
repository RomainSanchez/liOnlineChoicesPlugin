<?php use_helper('I18N', 'Date') ?>
<?php include_partial('ocBackend/assets') ?>

<div id="sf_admin_container">
  <?php include_partial('ocBackend/flashes') ?>

  <div id="sf_admin_content">
    
    <div class="sf_admin_list ui-grid-table ui-widget ui-corner-all ui-helper-reset ui-helper-clearfix">
      <table>
        <caption class="fg-toolbar ui-widget-header ui-corner-top">
          <h1><span class="ui-icon ui-icon-triangle-1-s"></span> <?php echo __('Validate placements', null, 'li_oc') ?></h1>
        </caption>

        <thead class="ui-widget-header plan_header">
          <tr class="plan_day"></tr>
        </thead>

        <tbody class="plan_body">
        </tbody>
        
      </table>
    </div>
  </div>

  <?php include_partial('ocBackend/themeswitcher') ?>
</div>

