<div class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-front ui-dialog-buttons snapshot_<?php echo $type ?>">
  <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
    <span id="ui-id-1" class="ui-dialog-title"><?php echo $type=='load'?__('Load state', null, 'li_oc'):__('Save state', null, 'li_oc') ?></span>
  </div>
  <div class="ui-dialog-content ui-widget-content" style="background-color: white;">
    <?php if ( $type == "save" && $sf_user->hasCredential('tck-onlinechoices-data-snapshot') ): ?>
    <p>
      <label>Nom :</label>
      <input type="text" id="snapshot_name">
    </p>
    <div class="sf_admin_actions_block floatleft">
      <button data-url="<?php echo url_for('oc_backend_save_snapshot'); ?>" class="fg-button ui-widget ui-state-default ui-corner-all save_snapshot_popup"><?php echo __('Save', null, 'li_oc') ?></button>
    </div>
    <?php endif ?>
    <?php if ( $type == "load" ): ?>
    <p>
      <ul class="list_snapshots">
      <?php include_partial('ocBackend/snapshots', array('snapshots' => $snapshots, 'day' => $day)) ?>
      </ul
    </p>
    <?php endif ?>
    <div class="sf_admin_actions_block floatright">
      <button class="fg-button ui-widget ui-state-default ui-corner-all popup_close"><?php echo __('Cancel', null, 'li_oc') ?></button>
    </div>
  </div>
</div>
