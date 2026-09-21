import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import Sidebar from '../components/Sidebar';
import Topbar from '../components/Topbar';
import InviteModal from '../components/InviteModal';
import { useAuth } from '../context/AuthContext';
import * as workspacesApi from '../api/workspaces';
import * as invitationsApi from '../api/invitations';

function initials(name) {
  return name.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase();
}

function relativeTime(isoString) {
  const minutes = Math.round((Date.now() - new Date(isoString).getTime()) / 60000);
  if (minutes < 1) return 'just now';
  if (minutes < 60) return `${minutes} minute${minutes === 1 ? '' : 's'} ago`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `${hours} hour${hours === 1 ? '' : 's'} ago`;
  return `${Math.round(hours / 24)} day${Math.round(hours / 24) === 1 ? '' : 's'} ago`;
}

const MEMBER_COLORS = ['var(--teal)', 'var(--green)', 'var(--violet)', 'var(--rose)', 'var(--blue)'];

export default function Members() {
  const [searchParams] = useSearchParams();
  const { user } = useAuth();
  const [workspaces, setWorkspaces] = useState(null);
  const [activeWorkspace, setActiveWorkspace] = useState(null);
  const [members, setMembers] = useState([]);
  const [pending, setPending] = useState(null);
  const [showInviteModal, setShowInviteModal] = useState(false);
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

  const isAdmin = members.some((m) => m.id === user.id && m.role === 'admin');

  async function selectWorkspace(workspace) {
    setActiveWorkspace(workspace);
    const memberList = await workspacesApi.listMembers(workspace.id);
    setMembers(memberList);
  }

  async function refreshPending(workspaceId, adminFlag) {
    if (!adminFlag) {
      setPending(null);
      return;
    }
    const list = await invitationsApi.listInvitations(workspaceId);
    setPending(list);
  }

  // Pending invitations are admin-only on the backend, so this only fires
  // once `members` (and therefore isAdmin) has actually loaded.
  useEffect(() => {
    if (activeWorkspace) refreshPending(activeWorkspace.id, isAdmin);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [activeWorkspace, isAdmin]);

  async function handleCreateWorkspace(name) {
    const workspace = await workspacesApi.createWorkspace({ name });
    setWorkspaces((prev) => [...prev, workspace]);
    await selectWorkspace(workspace);
  }

  async function handleRevoke(invitation) {
    await invitationsApi.revokeInvitation(invitation.id);
    setPending((prev) => prev.filter((i) => i.id !== invitation.id));
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
        active="members"
        isOpen={isSidebarOpen}
        onClose={() => setIsSidebarOpen(false)}
      />

      <div style={{ flex: 1, display: 'flex', flexDirection: 'column', minWidth: 0 }}>
        <Topbar
          members={members}
          canInvite={isAdmin}
          onInviteClick={() => setShowInviteModal(true)}
          onMenuClick={() => setIsSidebarOpen(true)}
        />

        <div className="page-content" style={{ maxWidth: 640 }}>
          <h1 className="poppins" style={{ fontWeight: 700, fontSize: '1.9rem', marginBottom: 4 }}>
            Members
          </h1>
          <div style={{ fontSize: 13.5, color: 'var(--ink-dim)', marginBottom: 28 }}>
            {members.length} member{members.length === 1 ? '' : 's'} in {activeWorkspace.name}
          </div>

          <div>
            {members.map((member, i) => (
              <div className="pending-row" key={member.id}>
                <div style={{ display: 'flex', alignItems: 'center', gap: 10 }}>
                  <div className="avatar" style={{ width: 32, height: 32, fontSize: 12, background: MEMBER_COLORS[i % MEMBER_COLORS.length] }}>
                    {initials(member.name)}
                  </div>
                  <div>
                    <div className="email">{member.name}</div>
                    <div className="info">{member.email}</div>
                  </div>
                </div>
                <span
                  style={{
                    fontSize: 11.5,
                    fontWeight: 700,
                    textTransform: 'uppercase',
                    letterSpacing: '0.03em',
                    color: member.role === 'admin' ? 'var(--blue-dark)' : 'var(--ink-faint)',
                    background: member.role === 'admin' ? 'var(--tint)' : 'var(--surface)',
                    border: `1px solid ${member.role === 'admin' ? 'var(--tint-strong)' : 'var(--line)'}`,
                    borderRadius: 999,
                    padding: '4px 10px',
                  }}
                >
                  {member.role}
                </span>
              </div>
            ))}
          </div>

          {isAdmin && pending && pending.length > 0 && (
            <div style={{ marginTop: 36 }}>
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
      </div>

      {showInviteModal && (
        <InviteModal
          workspace={activeWorkspace}
          onClose={() => {
            setShowInviteModal(false);
            refreshPending(activeWorkspace.id, isAdmin);
          }}
        />
      )}
    </div>
  );
}
