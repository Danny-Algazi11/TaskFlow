import { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import * as invitationsApi from '../api/invitations';

export default function AcceptInvitation() {
  const { token } = useParams();
  const navigate = useNavigate();
  const [status, setStatus] = useState('pending'); // pending | success | error
  const [workspace, setWorkspace] = useState(null);
  const [error, setError] = useState(null);

  // Accepting isn't idempotent (a second call 404s with "already used"), so
  // this can't rely on an isCurrent-style cleanup flag alone — React 18
  // StrictMode intentionally mounts/cleans-up/remounts every effect once in
  // dev, and cleanup doesn't run before the first request's promise settles,
  // so both requests would fire. A ref survives that remount (same fiber),
  // so it's used to guarantee the actual API call happens exactly once.
  const hasRequestedRef = useRef(false);

  useEffect(() => {
    if (hasRequestedRef.current) return;
    hasRequestedRef.current = true;

    invitationsApi
      .acceptInvitation(token)
      .then((ws) => {
        setWorkspace(ws);
        setStatus('success');
      })
      .catch((err) => {
        setError(err.response?.data?.message ?? 'Something went wrong. Please try again.');
        setStatus('error');
      });
  }, [token]);

  return (
    <div style={{ minHeight: '100vh', display: 'flex', alignItems: 'center', justifyContent: 'center', padding: 24 }}>
      <div style={{ width: '100%', maxWidth: 380, textAlign: 'center', display: 'flex', flexDirection: 'column', gap: 20 }}>
        <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', gap: 9 }}>
          <div style={{ width: 26, height: 26, borderRadius: 8, background: 'var(--blue)' }} />
          <span className="poppins" style={{ fontWeight: 700, fontSize: 19 }}>
            Task<span style={{ color: 'var(--blue)' }}>Flow</span>
          </span>
        </div>

        {status === 'pending' && <p style={{ color: 'var(--ink-dim)', fontSize: 14 }}>Joining workspace...</p>}

        {status === 'success' && (
          <>
            <h1 className="poppins" style={{ fontWeight: 700, fontSize: '1.5rem' }}>
              You're in!
            </h1>
            <p style={{ color: 'var(--ink-dim)', fontSize: 14 }}>
              You've joined <b>{workspace.name}</b>.
            </p>
            <button type="button" className="btn btn-primary" style={{ height: 44, justifyContent: 'center' }} onClick={() => navigate('/')}>
              Go to workspace
            </button>
          </>
        )}

        {status === 'error' && (
          <>
            <h1 className="poppins" style={{ fontWeight: 700, fontSize: '1.5rem' }}>
              Couldn't join workspace
            </h1>
            <div className="form-error">{error}</div>
            <button type="button" className="btn btn-ghost" style={{ height: 44, justifyContent: 'center' }} onClick={() => navigate('/')}>
              Back to dashboard
            </button>
          </>
        )}
      </div>
    </div>
  );
}
