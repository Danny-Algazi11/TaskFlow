import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const SWATCH_COLORS = ['var(--blue)', 'var(--teal)', 'var(--green)', 'var(--violet)', 'var(--rose)'];

function initials(name) {
  return name
    .split(' ')
    .map((part) => part[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

export default function Sidebar({
  workspaces,
  activeWorkspace,
  onSelectWorkspace,
  onCreateWorkspace,
  active = 'boards',
  isOpen = false,
  onClose = () => {},
}) {
  const { user, logout } = useAuth();
  const navigate = useNavigate();
  const [isCreating, setIsCreating] = useState(false);
  const [newName, setNewName] = useState('');

  async function handleCreate(e) {
    e.preventDefault();
    if (!newName.trim()) return;
    await onCreateWorkspace(newName.trim());
    setNewName('');
    setIsCreating(false);
  }

  // Carries the current workspace along as a query param so switching
  // pages (Boards/Members/Settings) doesn't reset back to whichever
  // workspace happens to be first in the list.
  const activePath = active === 'members' ? '/members' : active === 'settings' ? '/settings' : '/';

  function goTo(path) {
    navigate(activeWorkspace ? `${path}?workspace=${activeWorkspace.id}` : path);
    onClose(); // no-op on desktop; closes the mobile drawer after a nav choice
  }

  function selectWorkspace(workspace) {
    onSelectWorkspace(workspace);
    navigate(`${activePath}?workspace=${workspace.id}`, { replace: true });
    onClose();
  }

  return (
    <>
      {/* Only rendered/visible on narrow screens (see .sidebar-scrim) — taps
          outside the drawer close it, same as any other off-canvas menu. */}
      {isOpen && <div className="sidebar-scrim" onClick={onClose} />}
      <div className={`sidebar${isOpen ? ' open' : ''}`}>
      <div className="logo">
        <span className="mark" />
        <span className="word">
          Task<b>Flow</b>
        </span>
      </div>

      <button className={`navitem${active === 'boards' ? ' active' : ''}`} type="button" onClick={() => goTo('/')}>
        <svg className="icon" viewBox="0 0 20 20" fill="none">
          <rect x="3" y="3" width="6" height="14" rx="1.5" stroke="currentColor" strokeWidth="1.6" />
          <rect x="11" y="3" width="6" height="8" rx="1.5" stroke="currentColor" strokeWidth="1.6" />
        </svg>
        Boards
      </button>
      <button className={`navitem${active === 'members' ? ' active' : ''}`} type="button" onClick={() => goTo('/members')}>
        <svg className="icon" viewBox="0 0 20 20" fill="none">
          <circle cx="7" cy="7" r="3" stroke="currentColor" strokeWidth="1.6" />
          <path d="M2 17c0-3 2.2-5 5-5s5 2 5 5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
        </svg>
        Members
      </button>
      <button className={`navitem${active === 'settings' ? ' active' : ''}`} type="button" onClick={() => goTo('/settings')}>
        <svg className="icon" viewBox="0 0 20 20" fill="none">
          <circle cx="10" cy="10" r="3.4" stroke="currentColor" strokeWidth="1.6" />
        </svg>
        Settings
      </button>

      <div className="ws-section-title">Workspaces</div>
      <div style={{ display: 'flex', flexDirection: 'column', gap: 1 }}>
        {workspaces.map((workspace, i) => (
          <button
            key={workspace.id}
            type="button"
            className={`navitem${workspace.id === activeWorkspace?.id ? ' active' : ''}`}
            onClick={() => selectWorkspace(workspace)}
          >
            <span
              style={{
                width: 18,
                height: 18,
                borderRadius: 5,
                background: SWATCH_COLORS[i % SWATCH_COLORS.length],
                flexShrink: 0,
              }}
            />
            {workspace.name}
          </button>
        ))}

        {isCreating ? (
          <form onSubmit={handleCreate} style={{ padding: '4px 12px' }}>
            <input
              autoFocus
              value={newName}
              onChange={(e) => setNewName(e.target.value)}
              onBlur={() => !newName.trim() && setIsCreating(false)}
              placeholder="Workspace name"
              style={{
                width: '100%',
                height: 32,
                borderRadius: 7,
                border: '1px solid var(--line)',
                padding: '0 8px',
                fontSize: 13,
                fontFamily: 'Inter, sans-serif',
                outline: 'none',
              }}
            />
          </form>
        ) : (
          <button type="button" className="navitem" style={{ color: 'var(--ink-faint)' }} onClick={() => setIsCreating(true)}>
            <svg className="icon" style={{ width: 16, height: 16 }} viewBox="0 0 16 16" fill="none">
              <path d="M8 3v10M3 8h10" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
            </svg>
            New workspace
          </button>
        )}
      </div>

      <div className="user-row">
        <div className="avatar" style={{ background: 'var(--violet)' }}>
          {initials(user?.name ?? '?')}
        </div>
        <div className="who">
          <div className="name">{user?.name}</div>
          <div className="email">{user?.email}</div>
        </div>
        <button
          type="button"
          onClick={logout}
          title="Log out"
          style={{ background: 'none', border: 'none', cursor: 'pointer', color: 'var(--ink-faint)', padding: 4 }}
        >
          <svg className="icon" style={{ width: 16, height: 16 }} viewBox="0 0 16 16" fill="none">
            <path d="M6 14H3.5A1.5 1.5 0 012 12.5v-9A1.5 1.5 0 013.5 2H6M10.5 11.5L14 8l-3.5-3.5M14 8H6" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </button>
      </div>
      </div>
    </>
  );
}
