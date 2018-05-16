<?php echo $header; ?>
<?php echo $column_left; ?>

<div id="content">

  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <a href="<?php echo ''; ?>" data-toggle="tooltip" title="" class="btn btn-default" data-original-title="Отменить"><i class="fa fa-reply"></i></a></div>

      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
      <?php foreach ($breadcrumbs as $breadcrumb) : ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
      <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="container-fluid">
    <div id="foc_csv">
      <div class="container">
        <div class="panel panel-default">
          <div class="panel-content">
            <h3><?php echo $foc_app_preload_title; ?></h3>
            <p><?php echo $foc_app_preload_description; ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
// Frontend app settings
window.FOC_CSV_PARAMS = {
  'requestConfig' : {
    'token': '<?php echo $token; ?>',
    'tokenName': '<?php echo $tokenName; ?>',
    'baseRoute': '<?php echo $baseRoute; ?>',
    'baseUrl': '<?php echo $baseUrl; ?>',
  },
  'language': '<?php echo $language; ?>',
  'initial' : <?php echo $initial; ?>
};
</script>
<?php foreach ($scripts as $script) { ?>
  <script src="<?php echo $script; ?>" type="text/javascript"></script>
<?php } ?>

<?php echo $footer; ?>