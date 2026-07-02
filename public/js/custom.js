

$(document).ready(function(){
  $(".profile-click").click(function(){
    $(".user-dtl").toggle();
  });
});


  function show() {
    if($('.create_event').hasclass('hidden'))
    {
      $('.create_event').removeclass('hidden').addclass('block');
    }
    else 
    {
      $('.create_event').removeclass('block').addclass('hidden');
    }
  }


   function showsidebar(id){
    if($('#'+id).hasClass('hidden')){
      $('#'+id).removeClass('hidden').addClass('block');
    }
      else
      {
      $('#'+id).removeClass('block').addClass('hidden');
    }
  }

  // Mobile menu toggle (moved from navigation.blade.php — Vue strips inline scripts)
  $(document).ready(function() {
    var btn = document.getElementById('mobile-menu-trigger');
    if (btn) {
      btn.addEventListener('click', function() {
        var resSidebar = document.getElementById('res_sidebar');
        if (resSidebar) {
          resSidebar.classList.toggle('hidden');
        }
      });
    }
  });

  // Sidebar accordion toggle (moved from superadmin/menu.blade.php — Vue strips inline scripts)
  $(document).ready(function() {
    var parents = document.querySelectorAll('.sidebar-menu-parent');
    parents.forEach(function (parent) {
      parent.addEventListener('click', function (e) {
        e.preventDefault();
        var submenu = parent.nextElementSibling;
        var arrow = parent.querySelector('.sidebar-menu-arrow');
        if (submenu && submenu.classList.contains('sites-sidebar')) {
          submenu.style.display = (submenu.style.display === 'block') ? 'none' : 'block';
          if (arrow) {
            arrow.style.transform = (submenu.style.display === 'block') ? 'rotate(90deg)' : 'rotate(0deg)';
          }
        }
      });
    });
    // Auto-open the active section
    var activeSection = document.querySelector('.sites-sidebar li.active');
    if (activeSection) {
      var submenu = activeSection.closest('.sites-sidebar');
      if (submenu) {
        submenu.style.display = 'block';
        var arrow = submenu.previousElementSibling ? submenu.previousElementSibling.querySelector('.sidebar-menu-arrow') : null;
        if (arrow) arrow.style.transform = 'rotate(90deg)';
      }
    }
  });
