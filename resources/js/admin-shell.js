<script>
(function () {
  // ===== Bridge & konstanta URL =====
  var bridge = document.getElementById('bridge');
  var ds = bridge ? bridge.dataset : {};
  var API_BASE   = ds && ds.apiBase   ? ds.apiBase   : '/api';
  var LOGIN_URL  = ds && ds.loginUrl  ? ds.loginUrl  : '/login';
  var ADMIN_URL  = ds && ds.adminUrl  ? ds.adminUrl  : '/admin';
  var LOGOUT_URL = ds && ds.logoutUrl ? ds.logoutUrl : '/logout';
  var PROFILE_URL= ds && ds.profileUrl? ds.profileUrl: '/admin/profile';
  var PASS_URL   = ds && ds.passwordUrl? ds.passwordUrl: '/admin/password';

  // ===== Axios global =====
  if (!window.axios) {
    console.warn('axios belum tersedia. Pastikan CDN/Bundle sudah dimuat sebelum shell.');
    return;
  }
  function setAxiosAuth() {
    var token = localStorage.getItem('auth_token') || '';
    window.axios.defaults.baseURL = API_BASE;
    if (token) {
      window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + token;
    } else {
      try { delete window.axios.defaults.headers.common['Authorization']; } catch (e) {}
    }
    // header dasar agar Laravel happy
    window.axios.defaults.headers.common['Accept'] = 'application/json';
    window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
  }
  setAxiosAuth();

  // ===== Utils =====
  function isAuthError(err) {
    var st = err && err.response ? err.response.status : null;
    return st === 401 || st === 419;
  }

  function setUserUI(user) {
    user = user || {};
    var username = user.username || localStorage.getItem('auth_username') || 'Pengguna';
    var role     = user.role     || localStorage.getItem('auth_role')     || '-';

    var userInfoEl = document.getElementById('userInfo');
    var userRoleEl = document.getElementById('userRole');
    if (userInfoEl) userInfoEl.textContent = username;
    if (userRoleEl) userRoleEl.textContent = role;

    // Header dropdown (opsional)
    var nameHdr = document.getElementById('userName');
    var roleHdr = document.getElementById('userRoleMini');
    var descHdr = document.getElementById('userDesc');
    if (nameHdr) nameHdr.textContent = username;
    if (roleHdr) roleHdr.textContent = role && role !== '-' ? '(' + role + ')' : '';
    if (descHdr) descHdr.textContent = role && role !== '-' ? (username + ' • ' + role) : username;

    var lProf = document.getElementById('linkProfile');
    var lPass = document.getElementById('linkPassword');
    if (lProf) lProf.href = PROFILE_URL;
    if (lPass) lPass.href = PASS_URL;
  }

  function guardMenu(role) {
    var links = document.querySelectorAll('#adminMenu a[data-roles]');
    for (var i = 0; i < links.length; i++) {
      var a = links[i];
      var allow = (a.getAttribute('data-roles') || '').split(',');
      var ok = false;
      if (role) {
        for (var j = 0; j < allow.length; j++) {
          if (role === allow[j].trim()) { ok = true; break; }
        }
      }
      if (!ok && role) { a.style.display = 'none'; }
    }
  }

  function handleLogout() {
    window.axios.post('/logout')["catch"](function(e){ /* noop */ }).then(function(){
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_role');
      localStorage.removeItem('auth_username');
      setAxiosAuth();
      window.location.replace(LOGIN_URL);
    });
  }

  // Wire logout (sidebar + header)
  var btnLogout = document.getElementById('btnLogout');
  if (btnLogout) btnLogout.addEventListener('click', function (e) { e.preventDefault(); handleLogout(); });
  var btnLogoutTop = document.getElementById('btnLogoutTop');
  if (btnLogoutTop) btnLogoutTop.addEventListener('click', function (e) { e.preventDefault(); handleLogout(); });

  // ===== Boot =====
  function boot() {
    // Jika tidak ada token → ke login
    if (!localStorage.getItem('auth_token')) {
      window.location.replace(LOGIN_URL);
      return;
    }
    window.axios.get('/me').then(function(res){
      var me = res && res.data ? res.data : {};
      if (me.username) localStorage.setItem('auth_username', me.username);
      if (me.role)     localStorage.setItem('auth_role', me.role);
      setUserUI(me);
      guardMenu(me.role);
    })["catch"](function(e){
      if (isAuthError(e)) {
        handleLogout();
      } else {
        // Non-auth error: tetap tampilkan UI fallback
        setUserUI({});
        guardMenu(localStorage.getItem('auth_role') || null);
        console.warn('Gagal memuat /me:', (e && e.response && e.response.data) || e.message);
      }
    });
  }

  boot();
})();
</script>
