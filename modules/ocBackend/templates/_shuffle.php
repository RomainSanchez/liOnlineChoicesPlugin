<div class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-front ui-dialog-buttons shuffle">
  <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">
    <span id="ui-id-1" class="ui-dialog-title"><?php echo __('Shuffle ordering', null, 'li_oc') ?></span>
  </div>
  <div class="ui-dialog-content ui-widget-content" style="background-color: white;">
    <ol class="list_snapshots"><?php foreach ( $groups as $group ): ?>
        <li class="ui-corner-all" data-id="<?php echo $group->id ?>"><?php echo $group ?></li>
    <?php endforeach ?></ul>
    <div class="sf_admin_actions_block floatright">
      <button class="fg-button ui-widget ui-state-default ui-corner-all popup_close cancel"><?php echo __('Cancel', null, 'li_oc') ?></button>
      <button class="fg-button ui-widget ui-state-default ui-corner-all popup_close shuffle"><?php echo __('Shuffle ordering', null, 'li_oc') ?></button>
    </div>
  </div>
</div>
