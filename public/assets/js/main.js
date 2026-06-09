// Toast notification system
(function() {
  var container = document.createElement('div');
  container.id = 'toast-container';
  document.body.appendChild(container);
})();

function showToast(type, title, message) {
  var icons = {
    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>',
    error:   '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M15 9l-6 6M9 9l6 6"/></svg>',
    warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>',
    info:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="8"/></svg>'
  };
  var container = document.getElementById('toast-container');
  var toast = document.createElement('div');
  toast.className = 'toast ' + type;
  toast.innerHTML = icons[type] + '<div><div class="toast-title">' + title + '</div>' + (message ? '<div class="toast-message">' + message + '</div>' : '') + '</div>';
  container.appendChild(toast);
  setTimeout(function() { toast.remove(); }, 4000);
}

// Nav toggle for mobile (landing page)
(function() {
  var navToggle = document.getElementById('navToggle');
  var navLinks = document.getElementById('navLinks');
  var mobileClose = document.getElementById('mobileNavClose');

  function toggleNav(open) {
    if (!navLinks) return;
    navLinks.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
  }

  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function() {
      toggleNav(true);
    });

    if (mobileClose) {
      mobileClose.addEventListener('click', function() {
        toggleNav(false);
      });
    }

    document.addEventListener('click', function(e) {
      if (navLinks.classList.contains('open') &&
          !navToggle.contains(e.target) &&
          !navLinks.contains(e.target)) {
        toggleNav(false);
      }
    });

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && navLinks.classList.contains('open')) {
        toggleNav(false);
      }
    });

    navLinks.querySelectorAll('a').forEach(function(link) {
      link.addEventListener('click', function() {
        toggleNav(false);
      });
    });
  }
})();

// Sidebar toggle for mobile
(function() {
  var sidebarToggle = document.getElementById('sidebarToggle');
  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebarOverlay');

  function toggleSidebar(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('open', open);
    if (overlay) overlay.classList.toggle('open', open);
    document.body.style.overflow = open ? 'hidden' : '';
  }

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
      toggleSidebar(!sidebar.classList.contains('open'));
    });

    if (overlay) {
      overlay.addEventListener('click', function() {
        toggleSidebar(false);
      });
    }

    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && sidebar.classList.contains('open')) {
        toggleSidebar(false);
      }
    });
  }
})();

// Landing page nav scroll effect
(function() {
  var nav = document.querySelector('.landing-nav');
  if (nav) {
    window.addEventListener('scroll', function() {
      nav.classList.toggle('scrolled', window.scrollY > 20);
    }, { passive: true });
  }
})();

// Auto-hide flash messages
(function() {
  var alerts = document.querySelectorAll('.alert');
  alerts.forEach(function(alert) {
    setTimeout(function() {
      alert.style.transition = 'opacity 0.5s';
      alert.style.opacity = '0';
      setTimeout(function() {
        alert.style.display = 'none';
      }, 500);
    }, 5000);
  });
})();

// Appointment reminders
(function() {
  if (!('Notification' in window)) return;
  if (Notification.permission === 'default') Notification.requestPermission();

  var apptDates = document.querySelectorAll('[data-appt-date]');
  apptDates.forEach(function(el) {
    var apptTime = new Date(el.getAttribute('data-appt-date'));
    var diffMin = (apptTime - new Date()) / 60000;
    if (diffMin > 0 && diffMin <= 30 && Notification.permission === 'granted') {
      new Notification('Upcoming Appointment', {
        body: 'Your appointment is in ' + Math.round(diffMin) + ' minutes.',
        icon: '/assets/img/icons/icon-192.png'
      });
    }
  });
})();

// Smooth scroll for anchor links
(function() {
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      var target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });
})();
