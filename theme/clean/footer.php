<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Clean theme
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License in GPL.txt for more details.
 */
?>

</div>
<!-- END CONTAINER -->

<!-- Start Footer -->
<div class="row footer">
  <div class="col-md-6 text-left">
  Copyright &copy; <?php echo date("Y");?> <a href="" target="_blank"><?php echo $site_name;?></a>. All rights reserved.
  </div>
  <div class="col-md-6 text-right">
    Powered by <a href="https://phpaste.sourceforge.io/" target="_blank">Paste</a>
  </div> 
</div>
<?php if (isset($_SESSION['username'])) { ?>
<?php } else { ?>
<div style="text-align:center;">
<?php echo $ads_2; ?>
</div>
<?php } ?>
<!-- End Footer -->

</div>
<!-- End content -->

<!-- Google Analytics -->
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', '<?php echo $ga; ?>', 'auto');
  ga('send', 'pageview');
</script>

<!-- Additional Scripts -->
<?php echo $additional_scripts; ?>

<!-- Scripts -->
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/bootstrap.min.js"></script>
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/paste.js"></script>
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/bootstrap-select.js"></script>
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/datatables.min.js"></script>

<script>
$(document).ready(function() {
	 //$('#archive').DataTable();
     $('#archive').DataTable( {
        "order": [[ 1, "desc" ]] // Paste Time
    } );
} );
</script>

</body>
</html>