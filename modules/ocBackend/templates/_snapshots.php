<?php use_helper('I18N', 'Date') ?>

<?php foreach ( $snapshots as $snapshot ): ?>
<li>
  <a href="<?php echo url_for('@oc_backend_load_snapshot?id='.$snapshot->id.'&date='.$day); ?>" target="_blank" class="snapshot"><?php echo $snapshot->name ? $snapshot->name : __('Validated',null,'li_oc') ?></a>
  <span class="details">(<?php echo format_date(strtotime($snapshot->created_at), 'EEEE d MMMM yyyy HH:mm').' - '.$snapshot->sfGuardUser ?>)</span>
</li>
<?php endforeach ?>
