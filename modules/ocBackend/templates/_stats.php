<?php use_helper('CrossAppLink') ?>

  
 
	  <?php include_partial('global/chart_jqplot', array(
	          'id'    => 'stats',
	          'data'  => cross_app_url_for('tck', 'ocBackend/json'),
	          'name' => __('Debts'),
	          'width' => '900'
	         )) ?>
 

<?php use_javascript('/js/jqplot/plugins/jqplot.dateAxisRenderer.js') ?>
<?php use_javascript('/js/jqplot/plugins/jqplot.cursor.js') ?>
<?php use_javascript('/liOnlineChoicesPlugin/js/oc_backend_stats?'.date('Ymd')) ?>