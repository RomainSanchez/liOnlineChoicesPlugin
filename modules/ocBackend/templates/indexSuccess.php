<?php use_helper('I18N', 'Date') ?>
<?php include_partial('ocBackend/assets') ?>

      
<div id="sf_admin_container">
  <?php include_partial('ocBackend/flashes') ?>

  <input id="_csrf_token" type="hidden" value="<?php echo $_csrf_token ?>">
  
  <form id="sf_admin_content">
     <div class="floatleft" style="margin-bottom:15px">
      <?php foreach($datesData as $date): ?>
     
          <?php if($date['current']): ?>
            <span class="fg-button ui-widget ui-state-default ui-corner-all current-date ui-state-disabled" >
                <?php echo $date['day']; ?>
            </span>
          <?php else: ?>
          <span  class="fg-button ui-widget ui-state-default ui-corner-all" >
               <a href="?date=<?php echo $date['date']; ?>" >
                    <?php echo $date['day']; ?>
               </a>
            </span>
          <?php endif; ?>
      <?php endforeach; ?>
      </div>
      
      <?php include_partial('ocBackend/list', array(
          'day' => $day,
          'group' => $group,
          'initialChoicesActionEnabled'=>$initialChoicesActionEnabled)) ?>
      
      <?php include_partial('ocBackend/stats'); ?>

  </form>
  <?php include_partial('ocBackend/themeswitcher') ?>
</div>

<?php include_partial('ocBackend/popup', ['type' => 'save']) ?>
<?php include_partial('ocBackend/popup', ['type' => 'load', 'snapshots' => $snapshots, 'day' => $day]) ?>
<?php include_partial('ocBackend/shuffle', ['groups' => $groups]) ?>

<script type="text/javascript">

liOC.valid = <?php echo $valid ? 'true' : 'false'; ?>;

</script>
