import { useEffect, useState } from 'react';
import * as invitationsApi from '../api/invitations';

function relativeTime(isoString) {
  const minutes = Math.round((Date.now() - new Date(isoString).getTime()) / 60000);
  if (minutes < 1) return 'just now';
  if (minutes < 60) return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `${hours} hour${hours === 1 ? '' : 's'} ago`;
  return `${Math.round(hours / 24)} day${Math.round(hours / 24) === 1 ? '' : 's'} ago`;
}

function acceptLinkFor(token) {
  return `${window.location.origin}/invitations/${token}/accept`;
}

export default function InviteModal({ workspace, onClose }) {
  const [email, setEmail] = useState('');
  const [role, setRole] = useState('member');
  const [error, setError] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [lastInviteToken, setLastInviteToken] = useState(null);
  const [copied, setCopied] = useState(false);

  const [pending, setPending] = useState(null);

  useEffect(() => {
    let isCurrent = true;
    invitationsApi.listInvitations(workspace.id).then((list) => {
      if (isCurrent) setPending(list);
    });
    return () => {
      isCurrent = false;
    };
  }, [workspace.id]);

  async function handleSubmit(e) {
    e.preventDefault();
    if (!email.trim()) return;
    setError(null);
    setIsSubmitting(true);
    setCopied(false);
    try {
      const invitation = await invitationsApi.createInvitation(workspace.id, { email: email.trim(), role });
      setPending((prev) => [invitation, ...(prev ?? [])]);
      setLastInviteToken(invitation.token);
      setEmail('');
    } catch (err) {
      setError(err.response?.data?.message ?? 'Something went wrong. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleCopy() {
    try {
      await navigator.clipboard.writeText(acceptLinkFor(lastInviteToken));
      setCopied(true);
    } catch {
      setCopied(false);
    }
  }

  async function handleRevoke(invitation) {
    await invitationsApi.revokeInvitation(invitation.id);
    setPending((prev) => prev.filter((i) => i.id !== invitation.id));
    if (invitation.token === lastInviteToken) setLastInviteToken(null);
  }

  return (
    <>
      <div className="scrim" onClick={onClose} />
      <div className="modal modal-sm">
        <div className="modal-head">
          <div>
            <h2 className="poppins">Invite to {workspace.name}</h2>
            <div className="sub">They will get access to every board in this workspace.</div>
          </div>
          <button type="button" className="modal-close" onClick={onClose}>
            <svg className="icon" viewBox="0 0 16 16" fill="none">
              <path d="M3.5 3.5l9 9M12.5 3.5l-9 9" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: 16 }}>
          {error && <div className="form-error">{error}</div>}

          <div className="field">
            <label htmlFor="invite-email">Email address</label>
            <input
              id="invite-email"
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="teammate@company.com"
            />
          </div>

          <div className="field">
            <label>Role</label>
            <div className="role-row">
              <button
                type="button"
                className={`role-card${role === 'member' ? ' selected' : ''}`}
                onClick={() => setRole('member')}
              >
                <div className="role-name">Member</div>
                <div className="role-desc">Can create and edit boards</div>
              </button>
              <button
                type="button"
                className={`role-card${role === 'admin' ? ' selected' : ''}`}
                onClick={() => setRole('admin')}
              >
                <div className="role-name">Admin</div>
                <div className="role-desc">Can also invite and manage members</div>
              </button>
            </div>
          </div>

          <button type="submit" className="btn btn-primary" disabled={isSubmitting} style={{ justifyContent: 'center', height: 42 }}>
            {isSubmitting ? 'Sending...' : 'Send invite'}
          </button>
        </form>

        {lastInviteToken && (
          <>
            <div className="divider">
              <div className="line" />
              <span>OR SHARE THIS LINK</span>
              <div className="line" />
            </div>
            <div className="link-row">
              <div className="link-field">{acceptLinkFor(lastInviteToken)}</div>
              <button type="button" className="copy-btn" onClick={handleCopy}>
                {copied ? 'Copied!' : 'Copy'}
              </button>
            </div>
          </>
        )}

        {pending && pending.length > 0 && (
          <div>
            <div className="pending-title">Pending invitations</div>
            {pending.map((invitation) => (
              <div className="pending-row" key={invitation.id}>
                <div>
                  <div className="email">{invitation.email}</div>
                  <div className="info">
                    Invited {relativeTime(invitation.created_at)} - {invitation.role}
                  </div>
                </div>
                <button type="button" className="revoke-link" onClick={() => handleRevoke(invitation)}>
                  Revoke
                </button>
              </div>
            ))}
          </div>
        )}
      </div>
    </>
  );
}
