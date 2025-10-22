// Simple token storage
export const auth = {
  get() {
    try { return localStorage.getItem('auth_token') || ''; } catch { return ''; }
  },
  set(token) {
    try { localStorage.setItem('auth_token', token || ''); } catch {}
  },
  clear() {
    try { localStorage.removeItem('auth_token'); } catch {}
  }
};
