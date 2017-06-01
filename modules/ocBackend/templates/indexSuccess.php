<?php use_helper('I18N', 'Date') ?>
<?php include_partial('ocBackend/assets') ?>

<div id="sf_admin_container">
  <?php include_partial('ocBackend/flashes') ?>

  
  <input id="_csrf_token" type="hidden" value="<?php echo $_csrf_token ?>">
  
  <form id="sf_admin_content">
    
      <div class="sf_admin_actions_block floatleft">
        <?php if ( $sf_user->hasCredential('tck-onlinechoices-data-snapshot') ): ?>
        <span class="fg-button ui-widget ui-state-default ui-corner-all save_popup"><?php echo __('Save state', null, 'li_oc') ?></span>
        <?php endif ?>
        <?php if ( $sf_user->hasCredential('tck-onlinechoices-data') ): ?>
        <span data-url="<?php echo url_for('oc_backend_list_snapshots'); ?>" class="fg-button ui-widget ui-state-default ui-corner-all load_popup"><?php echo __('Load state', null, 'li_oc') ?></span>
        <?php endif ?>
      </div>
      <div class="sf_admin_actions_block floatright">
        <?php if ( $sf_user->hasCredential('tck-onlinechoices-data-rank') ): ?>
        <span class="fg-button ui-widget ui-state-default ui-corner-all shuffle"><?php echo __('Shuffle ordering', null, 'li_oc') ?></span>
        <span class="fg-button ui-widget ui-state-default ui-corner-all ranks"><a href="<?php echo url_for('oc_backend_save_ordering') ?>" target="_blank"><?php echo __('Save ordering', null, 'li_oc') ?></a></span>
        <?php endif ?>
        <?php if ( $sf_user->hasCredential('tck-onlinechoices-data-auto') ): ?>
        <span data-url="<?php echo url_for('oc_backend/auto'); ?>" class="fg-button ui-widget ui-state-default ui-corner-all positioning"><?php echo __('Auto positioning', null, 'li_oc') ?></span>
        <?php endif ?>
        <?php if ( $sf_user->hasCredential('tck-onlinechoices-data-transpose') ): ?>
        <span data-url="<?php echo url_for('oc_backend/validate'); ?>" class="fg-button ui-widget ui-state-default ui-corner-all validate"><?php echo __('Validate', null, 'li_oc') ?></span>
        <?php endif ?>
      </div>

      <?php include_partial('ocBackend/list', array('day' => $day, 'group' => $group)) ?>


  </form>

  <?php include_partial('ocBackend/themeswitcher') ?>
</div>

<?php include_partial('ocBackend/popup', ['type' => 'save']) ?>
<?php include_partial('ocBackend/popup', ['type' => 'load', 'snapshots' => $snapshots, 'day' => $day]) ?>
<?php include_partial('ocBackend/shuffle', ['groups' => $groups]) ?>

<script type="text/javascript">

liOC.valid = <?php echo $valid ? 'true' : 'false'; ?>;

</script>
