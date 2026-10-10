
$(document).ready(function(){
  function accountMenu($root) {
    var id = $root.find('[data-account-trigger]').attr('aria-controls');
    return id ? $(document.getElementById(id)) : $root.find('[data-account-menu]');
  }

  function accountItems($root) {
    return accountMenu($root).find('[role="menuitem"]:visible');
  }

  function placeAccountMenu($root) {
    var $menu = accountMenu($root);
    var trigger = $root.find('[data-account-trigger]')[0];
    if (!$menu.length || !trigger) return;
    document.body.appendChild($menu[0]);
    $menu.addClass('is-portaled').removeAttr('hidden');
    var box = trigger.getBoundingClientRect();
    var phone = window.innerWidth < 768;
    var width = phone ? Math.min(280, window.innerWidth - 16) : 224;
    var style = {
      position: 'fixed',
      display: 'block',
      width: width + 'px',
      maxWidth: (window.innerWidth - 16) + 'px',
      zIndex: 80,
      margin: 0
    };
    if (phone) {
      style.top = Math.round(box.bottom + 8) + 'px';
      style.bottom = 'auto';
      style.right = '8px';
      style.left = 'auto';
    } else {
      style.left = Math.round(box.left) + 'px';
      style.right = 'auto';
      style.top = 'auto';
      style.bottom = Math.round(window.innerHeight - box.top + 8) + 'px';
    }
    $menu.css(style);
    if (!phone) {
      var placed = $menu[0].getBoundingClientRect();
      if (placed.top < 8) {
        $menu.css({
          top: '8px',
          bottom: 'auto',
          maxHeight: Math.max(120, Math.round(box.top - 16)) + 'px',
          overflowY: 'auto'
        });
      }
    }
  }

  function closeAccountCard($root) {
    if (!$root || !$root.length) return;
    $root.removeClass('open');
    $root.find('[data-account-trigger]').attr('aria-expanded', 'false');
    accountMenu($root).attr('hidden', true).css('display', 'none');
  }

  function openAccountCard($root) {
    $('.profile-click.account-card').each(function () {
      if (this !== $root[0]) closeAccountCard($(this));
    });
    $('.profile-click').not($root).removeClass('open');
    $root.addClass('open');
    $root.find('[data-account-trigger]').attr('aria-expanded', 'true');
    placeAccountMenu($root);
    var $first = accountItems($root).first();
    if ($first.length) {
      $first.trigger('focus');
    }
  }

  // Profile / account card toggle
  $(document).on('click', '.profile-click.account-card [data-account-trigger]', function(e) {
    e.preventDefault();
    e.stopPropagation();
    var $root = $(this).closest('.profile-click.account-card');
    if ($root.hasClass('open')) {
      closeAccountCard($root);
      $(this).trigger('focus');
    } else {
      openAccountCard($root);
    }
  });

  // Legacy .profile-click without account-card (if any remain)
  $(document).on('click', '.profile-click:not(.account-card)', function(e) {
    if ($(e.target).closest('.user-dtl').length) return;
    var $parent = $(this);
    var wasOpen = $parent.hasClass('open');
    $('.profile-click').removeClass('open');
    if (!wasOpen) {
      $parent.addClass('open');
      e.stopPropagation();
    }
  });

  // Close on outside pointerdown (handoff: pointerdown outside card+trigger)
  $(document).on('pointerdown', function(e) {
    if (!$(e.target).closest('.profile-click.open, [data-account-menu]').length) {
      $('.profile-click.account-card.open').each(function () {
        closeAccountCard($(this));
      });
      $('.profile-click').removeClass('open');
    }
  });

  // Keyboard: Esc, arrows, Tab out
  $(document).on('keydown', function(e) {
    var $open = $('.profile-click.account-card.open');
    if (!$open.length) {
      if (e.key === 'Escape') {
        $('.profile-click').removeClass('open');
      }
      return;
    }

    var $items = accountItems($open);
    var $trigger = $open.find('[data-account-trigger]');

    if (e.key === 'Escape') {
      e.preventDefault();
      closeAccountCard($open);
      $trigger.trigger('focus');
      return;
    }

    if (e.key === 'Tab') {
      closeAccountCard($open);
      return;
    }

    if (!$items.length) return;

    var idx = $items.index(document.activeElement);
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      var next = idx < 0 ? 0 : (idx + 1) % $items.length;
      $items.eq(next).trigger('focus');
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      var prev = idx < 0 ? $items.length - 1 : (idx - 1 + $items.length) % $items.length;
      $items.eq(prev).trigger('focus');
    }
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
    else if($('#'+id).hasClass('block')) {
      $('#'+id).removeClass('block').addClass('hidden');
    }
  }

  // Mobile menu toggle (delegated — replaces both the old per-element listener AND the
  // former inline onclick="showsidebar('res_sidebar')", which double-bound and made the
  // menu unopenable: the inline handler removed `hidden`, this listener re-added it.
  // Delegation also survives Livewire morphing of the navbar.)
  $(document).on('click', '#mobile-menu-trigger', function() {
    var resSidebar = document.getElementById('res_sidebar');
    if (!resSidebar) return;
    var willOpen = resSidebar.classList.contains('hidden');
    resSidebar.classList.toggle('hidden', !willOpen);
    resSidebar.classList.toggle('block', willOpen);
    this.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  });

  function closeMobileDrawer() {
    var resSidebar = document.getElementById('res_sidebar');
    if (!resSidebar || resSidebar.classList.contains('hidden')) return;
    resSidebar.classList.remove('block');
    resSidebar.classList.add('hidden');
    var trigger = document.getElementById('mobile-menu-trigger');
    if (trigger) trigger.setAttribute('aria-expanded', 'false');
  }

  // Drawer closes on tap-outside (concept: document click outside #side) and Esc.
  $(document).on('click', function(e) {
    var resSidebar = document.getElementById('res_sidebar');
    if (!resSidebar || resSidebar.classList.contains('hidden')) return;
    if (e.target.closest('#res_sidebar') || e.target.closest('#mobile-menu-trigger')) return;
    closeMobileDrawer();
  });
  $(document).on('keydown', function(e) {
    if (e.key === 'Escape') closeMobileDrawer();
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
