<?php
/*
 * Paste <https://github.com/jordansamuel/PASTE> - Bulma theme
 * Theme by wsehl <github.com/wsehl> (January, 2021)
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

<footer class="footer has-background-white" style="border-top: 1px solid #ebeaeb">
  <div class="container">
    <div class="columns">
      <div class="column is-8">
        <strong><?php echo $site_name; ?></strong> by <a href="https://github.com/wsehl">wsehl</a>, on <a href="https://github.com/wsehl/wspaste">github</a>
      </div>
      <div class="column is-4">
        <?php if (isset($_SESSION['username'])) { ?>
        <?php } else { ?>
          <div>
            <?php echo $ads_2; ?>
          </div>
        <?php } ?>
      </div>
    </div>
  </div>
</footer>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gtag; ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];

  function gtag() {
    dataLayer.push(arguments);
  }
  gtag('js', new Date());

  gtag('config', '<?php echo $gtag; ?>');
</script>

<!-- Ads -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    (document.querySelectorAll('.notification .delete') || []).forEach(($delete) => {
      $notification = $delete.parentNode;

      $delete.addEventListener('click', () => {
        $notification.parentNode.removeChild($notification);
      });
    });
  });
</script>

<!-- Tabs -->
<script>
  function openTab(evt, tabName) {
    var i, x, tablinks;
    x = document.getElementsByClassName("content-tab");
    for (i = 0; i < x.length; i++) {
      x[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab");
    for (i = 0; i < x.length; i++) {
      tablinks[i].className = tablinks[i].className.replace(" is-active", "");
    }
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " is-active";
  }
</script>

<!-- Scripts -->
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/jquery-ui.min.js"></script>
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/paste.js"></script>
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/modal-fx.min.js"></script>
<script type="text/javascript" src="<?php echo '//' . $baseurl . '/theme/' . $default_theme; ?>/js/datatables.min.js"></script>


<!--Notification -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    (document.querySelectorAll('.notification .delete') || []).forEach(($delete) => {
      var $notification = $delete.parentNode;

      $delete.addEventListener('click', () => {
        $notification.parentNode.removeChild($notification);
      });
    });
  });
</script>

<!--Data Tables -->
<script>
  $(document).ready(function() {
    $('#archive').DataTable({
      "order": [
        [1, "desc"]
      ] // Paste Time
    });
  });
</script>

<!-- Hamburger menu -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
    if ($navbarBurgers.length > 0) {
      $navbarBurgers.forEach(el => {
        el.addEventListener('click', () => {
          const target = el.dataset.target;
          const $target = document.getElementById(target);
          el.classList.toggle('is-active');
          $target.classList.toggle('is-active');
        });
      });
    }
  });
</script>

<!-- Additional Scripts -->
<?php echo $additional_scripts; ?>

</body>

</html>