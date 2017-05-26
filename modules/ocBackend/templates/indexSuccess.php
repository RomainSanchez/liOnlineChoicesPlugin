<?php use_helper('I18N', 'Date') ?>
<?php include_partial('ocBackend/assets') ?>

<div id="sf_admin_container">
  <?php include_partial('ocBackend/flashes') ?>

  
  <input id="_csrf_token" type="hidden" value="<?php echo $_csrf_token ?>">
  
  <div id="sf_admin_content">
    
      <div class="sf_admin_actions_block floatleft">
        <?php if ( $sf_user->hasCredential('tck-onlinechoices-data-snapshot') ): ?>
        <span class="fg-button ui-widget ui-state-default ui-corner-all save_popup"><?php echo __('Save state', null, 'li_oc') ?></span>
        <?php endif ?>
        <?php if ( $sf_user->hasCredential('tck-onlinechoices-data') ): ?>
        <span class="fg-button ui-widget ui-state-default ui-corner-all load_popup"><?php echo __('Load state', null, 'li_oc') ?></span>
        <?php endif ?>
      </div>
      <div class="sf_admin_actions_block floatright">
        <?php if ( $sf_user->hasCredential('tck-onlinechoices-auto') ): ?>
        <span data-url="" class="fg-button ui-widget ui-state-default ui-corner-all"><?php echo __('Auto positioning', null, 'li_oc') ?></span>
        <?php if ( $sf_user->hasCredential('tck-onlinechoices-transpose') ): ?>
        <span data-url="<?php echo url_for('oc_backend/saveSnapshot'); ?>" class="fg-button ui-widget ui-state-default ui-corner-all validate"><?php echo __('Validate', null, 'li_oc') ?></span>
        <?php endif ?>
      </div>

      <?php include_partial('ocBackend/list', array('day' => $day)) ?>


  </div>

  <?php include_partial('ocBackend/themeswitcher') ?>
</div>

<?php include_partial('ocBackend/popup', array('type' => 'save')) ?>
<?php include_partial('ocBackend/popup', array('type' => 'load', 'snapshots' => $snapshots)) ?>

