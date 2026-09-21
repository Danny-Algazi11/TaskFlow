import { useState } from 'react';
import { useLocation, Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

export default function Register() {
  const { register } = useAuth();
  const location = useLocation();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    setError(null);
    setIsSubmitting(true);
    try {
      // No navigate() here — GuestOnlyRoute in App.jsx redirects on its own
      // once `register` updates the auth context, and calling navigate()
      // here too raced with it.
      await register({ name, email, password, passwordConfirmation });
    } catch (err) {
      const errors = err.response?.data?.errors;
      setError(
        errors
          ? Object.values(errors).flat().join(' ')
          : 'Something went wrong. Please try again.'
      );
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 24 }}>
      <form onSubmit={handleSubmit} style={{ width: '100%', maxWidth: 360, display: 'flex', flexDirection: 'column', gap: 22 }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: 9, marginBottom: 4 }}>
          <div style={{ width: 26, height: 26, borderRadius: 8, background: 'var(--blue)' }} />
          <span className="poppins" style={{ fontWeight: 700, fontSize: 19 }}>
            Task<span style={{ color: 'var(--blue)' }}>Flow</span>
          </span>
        </div>

        <div>
          <h2 className="poppins" style={{ fontWeight: 700, fontSize: '1.7rem', marginBottom: 6 }}>
            Create your account
          </h2>
          <div style={{ color: 'var(--ink-dim)', fontSize: 14 }}>
            Free forever. No credit card.
          </div>
        </div>

        {error && <div className="form-error">{error}</div>}

        <div className="field">
          <label htmlFor="name">Full name</label>
          <input id="name" type="text" autoComplete="name" required value={name} onChange={(e) => setName(e.target.value)} />
        </div>

        <div className="field">
          <label htmlFor="email">Email address</label>
          <input id="email" type="email" autoComplete="email" required value={email} onChange={(e) => setEmail(e.target.value)} />
        </div>

        <div className="field">
          <label htmlFor="password">Password</label>
          <input id="password" type="password" autoComplete="new-password" required minLength={8} value={password} onChange={(e) => setPassword(e.target.value)} />
        </div>

        <div className="field">
          <label htmlFor="password_confirmation">Confirm password</label>
          <input
            id="password_confirmation"
            type="password"
            autoComplete="new-password"
            required
            value={passwordConfirmation}
            onChange={(e) => setPasswordConfirmation(e.target.value)}
          />
        </div>

        <button type="submit" className="btn btn-primary" disabled={isSubmitting} style={{ height: 44 }}>
          {isSubmitting ? 'Creating account...' : 'Start for free'}
        </button>

        <div style={{ textAlign: 'center', fontSize: 14, color: 'var(--ink-dim)' }}>
          Already have an account? <Link to="/login" state={location.state} style={{ fontWeight: 700, color: 'var(--blue)' }}>Sign in</Link>
        </div>
      </form>
    </div>
  );
}
