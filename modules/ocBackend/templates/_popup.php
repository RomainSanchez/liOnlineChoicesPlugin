<div style="position: fixed; top: 50%; left: 50%; width:50%; max-width: 600px; min-width: 500px; height: auto; transform: translateX(-50%) translateY(-50%); display:none;" 
  class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-front ui-dialog-buttons snapshot_<?php echo $type ?>">
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
        <?php foreach ( $snapshots as $snapshot ): ?>
        <li>
          <a href="<?php echo url_for('@oc_backend_load_snapshot?id='.$snapshot->id); ?>" target="_blank" class="snapshot"><?php echo $snapshot->name ? $snapshot->name : __('Validated',null,'li_oc') ?></a>
          <span class="details">(<?php echo format_date(strtotime($snapshot->created_at), 'EEEE d MMMM yyyy HH:mm').' - '.$snapshot->sfGuardUser ?>)</span>
        </li>
        <?php endforeach ?>
      </ul>
    </p>
    <?php endif ?>
    <div class="sf_admin_actions_block floatright">
      <button class="fg-button ui-widget ui-state-default ui-corner-all popup_close"><?php echo __('Cancel', null, 'li_oc') ?></button>
    </div>
  </div>
</div>
