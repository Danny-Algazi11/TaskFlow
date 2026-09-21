import { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import Sidebar from '../components/Sidebar';
import Topbar from '../components/Topbar';
import { useAuth } from '../context/AuthContext';
import * as workspacesApi from '../api/workspaces';

export default function Settings() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { user } = useAuth();
  const [workspaces, setWorkspaces] = useState(null);
  const [activeWorkspace, setActiveWorkspace] = useState(null);
  const [members, setMembers] = useState([]);
  const [name, setName] = useState('');
  const [error, setError] = useState(null);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  useEffect(() => {
    workspacesApi.listWorkspaces().then((list) => {
      setWorkspaces(list);
      const requestedId = Number(searchParams.get('workspace'));
      const initial = list.find((w) => w.id === requestedId) ?? list[0];
      if (initial) selectWorkspace(initial);
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function selectWorkspace(workspace) {
    setActiveWorkspace(workspace);
    setName(workspace.name);
    const memberList = await workspacesApi.listMembers(workspace.id);
    setMembers(memberList);
  }

  async function handleCreateWorkspace(name) {
    const workspace = await workspacesApi.createWorkspace({ name });
    setWorkspaces((prev) => [...prev, workspace]);
    await selectWorkspace(workspace);
  }

  const isAdmin = members.some((m) => m.id === user.id && m.role === 'admin');
  const isOwner = activeWorkspace?.owner_id === user.id;

  async function handleRename() {
    if (!name.trim() || name === activeWorkspace.name) return;
    setError(null);
    try {
      const updated = await workspacesApi.updateWorkspace(activeWorkspace.id, { name: name.trim() });
      setActiveWorkspace(updated);
      setName(updated.name);
      setWorkspaces((prev) => prev.map((w) => (w.id === updated.id ? updated : w)));
    } catch (err) {
      setError(err.response?.data?.message ?? 'Something went wrong. Please try again.');
      setName(activeWorkspace.name);
    }
  }

  async function handleDelete() {
    if (!window.confirm(`Delete "${activeWorkspace.name}"? All its boards, lists, and cards will be permanently removed. This cannot be undone.`)) {
      return;
    }
    await workspacesApi.deleteWorkspace(activeWorkspace.id);
    navigate('/', { replace: true });
  }

  if (workspaces === null || !activeWorkspace) {
    return null;
  }

  return (
    <div className="app-shell">
      <Sidebar
        workspaces={workspaces}
        activeWorkspace={activeWorkspace}
        onSelectWorkspace={selectWorkspace}
        onCreateWorkspace={handleCreateWorkspace}
        active="settings"
        isOpen={isSidebarOpen}
        onClose={() => setIsSidebarOpen(false)}
      />

      <div style={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0 }}>
        <Topbar
          members={members}
          canInvite={isAdmin}
          onInviteClick={() => navigate(`/members?workspace=${activeWorkspace.id}`)}
          onMenuClick={() => setIsSidebarOpen(true)}
        />

        <div className="page-content">
          <h1 className="poppins" style={{ fontWeight: 700, fontSize: '1.9rem', marginBottom: 28 }}>
            Settings
          </h1>

          <div style={{ maxWidth: 440, display: 'flex', flexDirection: 'column', gap: 20 }}>
            {error && <div className="form-error">{error}</div>}

            <div className="field">
              <label htmlFor="workspace-name">Workspace name</label>
              <input
                id="workspace-name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                onBlur={handleRename}
                disabled={!isAdmin}
              />
              {!isAdmin && (
                <div style={{ fontSize: 12, color: 'var(--ink-faint)' }}>
                  Only workspace admins can rename this workspace.
                </div>
              )}
            </div>

            <div style={{ marginTop: 20, paddingTop: 24, borderTop: '1px solid var(--line)' }}>
              <div className="section-label" style={{ color: 'var(--red)', marginBottom: 8 }}>
                Danger zone
              </div>
              <p style={{ fontSize: 13, color: 'var(--ink-dim)', marginBottom: 14, lineHeight: 1.6 }}>
                Deleting a workspace removes all its boards, lists, and cards for every member. This cannot be undone.
              </p>
              <button
                type="button"
                className="btn btn-ghost"
                style={{ borderColor: 'rgba(224,71,63,0.3)', color: 'var(--red)' }}
                disabled={!isOwner}
                title={isOwner ? undefined : 'Only the workspace owner can delete it.'}
                onClick={handleDelete}
              >
                Delete workspace
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
