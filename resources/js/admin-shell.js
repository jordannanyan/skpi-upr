<script>
(function(){
  const bridge = document.getElementById('bridge');
  const API_BASE   = bridge?.dataset.apiBase || '/api';
  const LOGIN_URL  = bridge?.dataset.loginUrl || '/login';
  const ADMIN_URL  = bridge?.dataset.adminUrl || '/admin';
  const LOGOUT_URL = bridge?.dataset.logoutUrl || '/logout';

  // axios global
  if (window.axios) {
    const token = localStorage.getItem('auth_token') || '';
    window.axios.defaults.baseURL = API_BASE;
    if (token) window.axios.defaults.headers.common['Authorization'] = 'Bearer ' + token;
  }

  // fetch profile -> guard menu
  async function boot() {
    try {
      const { data } = await axios.get('/me');
      const me = data || {};
      document.getElementById('userInfo').textContent = me.username || '-';
      document.getElementById('userRole').textContent = me.role || '-';
      guardMenu(me.role);
    } catch (e) {
      localStorage.removeItem('auth_token');
      window.location.replace(LOGIN_URL);
    }
  }

  function guardMenu(role){
    document.querySelectorAll('#adminMenu a[data-roles]').forEach((a)=>{
      const allow = (a.getAttribute('data-roles') || '').split(',').map(s=>s.trim());
      if (role && !allow.includes(role)) {
        a.style.display = 'none';
      }
    });
  }

  // logout
  const btnLogout = document.getElementById('btnLogout');
  btnLogout?.addEventListener('click', async (e)=>{
    e.preventDefault();
    try { await axios.post('/logout'); } catch {}
    localStorage.removeItem('auth_token');
    window.location.replace(LOGOUT_URL);
  });

  boot();
})();
</script>
