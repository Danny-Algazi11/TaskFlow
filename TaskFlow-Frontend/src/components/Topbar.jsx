function initials(name) {
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

const MEMBER_COLORS = ['var(--teal)', 'var(--green)', 'var(--violet)', 'var(--rose)', 'var(--blue)'];

export default function Topbar({ members, canInvite, onInviteClick, onMenuClick = () => {} }) {
  return (
    <div className="topbar">
      <button type="button" className="mobile-menu-btn" onClick={onMenuClick} title="Menu">
        <svg className="icon" viewBox="0 0 20 20" fill="none">
          <path d="M3 5.5h14M3 10h14M3 14.5h14" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
        </svg>
      </button>
      <div className="search-field">
        <svg className="icon" style={{ width: 15, height: 15 }} viewBox="0 0 16 16" fill="none">
          <circle cx="7" cy="7" r="4.5" stroke="currentColor" strokeWidth="1.6" />
          <path d="M13 13l-2.5-2.5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
        </svg>
        Search boards, cards...
      </div>
      <div style={{ display: 'flex', alignItems: 'center', gap: 18 }}>
        <svg className="icon" style={{ width: 19, height: 19, color: 'var(--ink-dim)' }} viewBox="0 0 20 20" fill="none">
          <path d="M6 8a4 4 0 018 0c0 3.2 1.2 4.4 1.8 5H4.2c.6-.6 1.8-1.8 1.8-5z" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
          <path d="M8.3 15.5a1.8 1.8 0 003.4 0" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
        </svg>

        {members.length > 0 && (
          <div className="avatars-inline">
            {members.slice(0, 4).map((member, i) => (
              <div key={member.id} className="avatar" style={{ background: MEMBER_COLORS[i % MEMBER_COLORS.length] }} title={member.name}>
                {initials(member.name)}
              </div>
            ))}
            {members.length > 4 && (
              <div className="avatar" style={{ background: 'var(--line)', color: 'var(--ink-dim)' }}>
                +{members.length - 4}
              </div>
            )}
          </div>
        )}

        <button
          className="btn btn-ghost"
          style={{ padding: '8px 16px', fontSize: 13 }}
          disabled={!canInvite}
          title={canInvite ? undefined : 'Only workspace admins can invite'}
          onClick={onInviteClick}
        >
          Invite
        </button>
      </div>
    </div>
  );
}
