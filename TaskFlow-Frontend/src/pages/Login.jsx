import { useState } from 'react';
import { useLocation, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function Login() {
  const { login } = useAuth();
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      // No navigate() here — GuestOnlyRoute in App.jsx redirects on its own
      // once `login` updates the auth context, and calling navigate() here
      // too raced with it.
      await login({ email, password });
    } catch (err) {
      setError(
        err.response?.status === 401
          ? 'Invalid credentials.'
          : 'Something went wrong. Please try again.'
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div style={{ minHeight: '100vh', display: 'flex' }}>
      <div
        className="auth-brand-panel"
        style={{
          background: 'var(--ink)',
          color: 'white',
          flexDirection: 'column',
          justifyContent: 'space-between',
          padding: 48,
          position: 'relative',
          overflow: 'hidden',
        }}
      >
        <BrandDecoration />

        <div style={{ display: 'flex', alignItems: 'center', gap: 9, position: 'relative' }}>
          <div style={{ width: 26, height: 26, borderRadius: 8, background: 'var(--blue)' }} />
          <span className="poppins" style={{ fontWeight: 700, fontSize: 19 }}>
            Task<span style={{ color: 'var(--blue)' }}>Flow</span>
          </span>
        </div>

        <div style={{ maxWidth: 380, position: 'relative' }}>
          <h1 className="poppins" style={{ fontWeight: 700, fontSize: '2.1rem', lineHeight: 1.25, marginBottom: 16 }}>
            A simple board for you and your team
          </h1>
          <p style={{ color: '#B7BBD1', fontSize: 14.5, lineHeight: 1.6 }}>
            Track tasks, move cards, and stay in sync - one board your whole team actually opens every day.
          </p>
        </div>

        <div style={{ fontSize: 12, color: '#8489A6', position: 'relative' }}>
          TaskFlow - a solo-built board manager, 2026
        </div>
      </div>

      <div className="auth-form-panel" style={{ flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
        <form onSubmit={handleSubmit} style={{ width: '100%', maxWidth: 360, display: 'flex', flexDirection: 'column', gap: 22 }}>
          <div>
            <h2 className="poppins" style={{ fontWeight: 700, fontSize: '1.7rem', marginBottom: 6 }}>
              Welcome back
            </h2>
            <div style={{ color: 'var(--ink-dim)', fontSize: 14 }}>
              Sign in to pick up where your team left off.
            </div>
          </div>

          {error && <div className="form-error">{error}</div>}

          <div className="field">
            <label htmlFor="email">Email address</label>
            <input
              id="email"
              type="email"
              autoComplete="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
            />
          </div>

          <div className="field">
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
              <label htmlFor="password">Password</label>
            </div>
            <input
              id="password"
              type="password"
              autoComplete="current-password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
            />
          </div>

          <button type="submit" className="btn btn-primary" disabled={isSubmitting} style={{ height: 44 }}>
            {isSubmitting ? 'Signing in...' : 'Sign in'}
          </button>

          <div style={{ textAlign: 'center', fontSize: 14, color: 'var(--ink-dim)' }}>
            New to TaskFlow? <Link to="/register" state={location.state} style={{ fontWeight: 700, color: 'var(--blue)' }}>Start for free</Link>
          </div>
        </form>
      </div>
    </div>
  );
}

function BrandDecoration() {
  return (
    <svg
      width="480"
      height="480"
      viewBox="0 0 480 480"
      style={{ position: 'absolute', right: -140, top: -60, opacity: 0.5 }}
    >
      <rect x="40" y="80" width="100" height="280" rx="14" fill="none" stroke="#2A2F55" strokeWidth="1.5" />
      <rect x="160" y="40" width="100" height="200" rx="14" fill="none" stroke="#2A2F55" strokeWidth="1.5" />
      <rect x="280" y="120" width="100" height="240" rx="14" fill="none" stroke="#2A2F55" strokeWidth="1.5" />
      <rect x="55" y="100" width="70" height="42" rx="8" fill="var(--blue)" opacity="0.9" />
      <rect x="55" y="152" width="70" height="32" rx="8" fill="none" stroke="#2A2F55" strokeWidth="1.5" />
      <rect x="175" y="60" width="70" height="38" rx="8" fill="none" stroke="#2A2F55" strokeWidth="1.5" />
      <rect x="175" y="112" width="70" height="32" rx="8" fill="none" stroke="#2A2F55" strokeWidth="1.5" />
      <rect x="295" y="140" width="70" height="32" rx="8" fill="none" stroke="#2A2F55" strokeWidth="1.5" />
      <rect x="295" y="182" width="70" height="42" rx="8" fill="none" stroke="#2A2F55" strokeWidth="1.5" />
    </svg>
  );
}
