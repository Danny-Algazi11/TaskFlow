import { useEffect, useState } from "react";
import { useNavigate, useSearchParams } from "react-router-dom";
import Sidebar from "../components/Sidebar";
import Topbar from "../components/Topbar";
import InviteModal from "../components/InviteModal";
import { useAuth } from "../context/AuthContext";
import * as workspacesApi from "../api/workspaces";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";

const BANNER_GRADIENTS = [
  "linear-gradient(135deg, #4C86F5, #2F6FED)",
  "linear-gradient(135deg, #35C08C, #1F9B6E)",
  "linear-gradient(135deg, #E97C5A, #D9587B)",
  "linear-gradient(135deg, #8B7CE8, #6A56C9)",
];

function formatRelativeTime(isoString) {
  const diffMs = Date.now() - new Date(isoString).getTime();
  const minutes = Math.round(diffMs / 60000);
  if (minutes < 1) return "just now";
  if (minutes < 60) return `${minutes} minute${minutes === 1 ? "" : "s"} ago`;
  const hours = Math.round(minutes / 60);
  if (hours < 24) return `${hours} hour${hours === 1 ? "" : "s"} ago`;
  const days = Math.round(hours / 24);
  return `${days} day${days === 1 ? "" : "s"} ago`;
}

export default function Dashboard() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();
  const { user } = useAuth();
  const [workspaces, setWorkspaces] = useState(null);
  const [activeWorkspace, setActiveWorkspace] = useState(null);
  const [boards, setBoards] = useState([]);
  const [members, setMembers] = useState([]);
  const [isLoadingWorkspace, setIsLoadingWorkspace] = useState(false);
  const [isCreatingBoard, setIsCreatingBoard] = useState(false);
  const [newBoardName, setNewBoardName] = useState("");
  const [showInviteModal, setShowInviteModal] = useState(false);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);

  useEffect(() => {
    workspacesApi.listWorkspaces().then((list) => {
      setWorkspaces(list);
      const requestedId = Number(searchParams.get("workspace"));
      const initial = list.find((w) => w.id === requestedId) ?? list[0];
      if (initial) selectWorkspace(initial);
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  async function selectWorkspace(workspace) {
    setActiveWorkspace(workspace);
    setIsLoadingWorkspace(true);
    const [boardList, memberList] = await Promise.all([
      workspacesApi.listBoards(workspace.id),
      workspacesApi.listMembers(workspace.id),
    ]);
    setBoards(boardList);
    setMembers(memberList);
    setIsLoadingWorkspace(false);
  }

  async function handleCreateWorkspace(name) {
    const workspace = await workspacesApi.createWorkspace({ name });
    setWorkspaces((prev) => [...prev, workspace]);
    await selectWorkspace(workspace);
  }

  async function handleCreateBoard(e) {
    e.preventDefault();
    if (!newBoardName.trim()) return;
    const board = await workspacesApi.createBoard(activeWorkspace.id, {
      name: newBoardName.trim(),
    });
    setBoards((prev) => [...prev, board]);
    setNewBoardName("");
    setIsCreatingBoard(false);
  }

  // Still loading the initial workspace list.
  if (workspaces === null) {
    return null;
  }

  // No workspace yet — the very first thing a brand new account sees.
  if (workspaces.length === 0) {
    return <FirstWorkspacePrompt onCreate={handleCreateWorkspace} />;
  }

  return (
    <div className="app-shell">
      <Sidebar
        workspaces={workspaces}
        activeWorkspace={activeWorkspace}
        onSelectWorkspace={selectWorkspace}
        onCreateWorkspace={handleCreateWorkspace}
        active="boards"
        isOpen={isSidebarOpen}
        onClose={() => setIsSidebarOpen(false)}
      />

      <div
        style={{
          flex: 1,
          display: "flex",
          flexDirection: "column",
          minWidth: 0,
        }}
      >
        <Topbar
          members={members}
          canInvite={members.some(
            (m) => m.id === user.id && m.role === "admin",
          )}
          onInviteClick={() => setShowInviteModal(true)}
          onMenuClick={() => setIsSidebarOpen(true)}
        />

        <div className="page-content">
          <div
            style={{
              display: "flex",
              alignItems: "flex-end",
              justifyContent: "space-between",
              marginBottom: 28,
            }}
          >
            <div>
              <h1
                className="poppins"
                style={{ fontWeight: 700, fontSize: "1.9rem", marginBottom: 4 }}
              >
                {activeWorkspace.name}
              </h1>
              <div style={{ fontSize: 13.5, color: "var(--ink-dim)" }}>
                {members.length} member{members.length === 1 ? "" : "s"} -{" "}
                {boards.length} board{boards.length === 1 ? "" : "s"}
              </div>
            </div>
          </div>

          {isLoadingWorkspace ? (
            <div className="board-grid">
              {Array.from({ length: 4 }).map((_, i) => (
                <div key={i} className="board-card">
                  <Skeleton height={80} />

                  <div className="body">
                    <Skeleton width="60%" height={18} />
                    <Skeleton width="40%" height={14} />
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="board-grid">
              {boards.map((board, i) => (
                <button
                  key={board.id}
                  type="button"
                  className="board-card"
                  onClick={() => navigate(`/boards/${board.id}`)}
                >
                  <div
                    className="banner"
                    style={{
                      background: BANNER_GRADIENTS[i % BANNER_GRADIENTS.length],
                    }}
                  />
                  <div className="body">
                    <div className="name">{board.name}</div>
                    <div className="updated">
                      Updated {formatRelativeTime(board.updated_at)}
                    </div>
                  </div>
                </button>
              ))}

              {isCreatingBoard ? (
                <form
                  onSubmit={handleCreateBoard}
                  className="new-board-card"
                  style={{ borderStyle: "solid", cursor: "default" }}
                >
                  <input
                    autoFocus
                    value={newBoardName}
                    onChange={(e) => setNewBoardName(e.target.value)}
                    onBlur={() =>
                      !newBoardName.trim() && setIsCreatingBoard(false)
                    }
                    placeholder="Board name"
                    style={{
                      width: "100%",
                      height: 36,
                      borderRadius: 8,
                      border: "1px solid var(--line)",
                      padding: "0 10px",
                      fontSize: 13.5,
                      fontFamily: "Inter, sans-serif",
                      outline: "none",
                    }}
                  />
                </form>
              ) : (
                <button
                  type="button"
                  className="new-board-card"
                  onClick={() => setIsCreatingBoard(true)}
                >
                  <svg
                    className="icon"
                    style={{ width: 22, height: 22 }}
                    viewBox="0 0 16 16"
                    fill="none"
                  >
                    <path
                      d="M8 3v10M3 8h10"
                      stroke="currentColor"
                      strokeWidth="1.6"
                      strokeLinecap="round"
                    />
                  </svg>
                  <span>Create a board</span>
                </button>
              )}
            </div>
          )}
        </div>
      </div>

      {showInviteModal && (
        <InviteModal
          workspace={activeWorkspace}
          onClose={() => setShowInviteModal(false)}
        />
      )}
    </div>
  );
}

function FirstWorkspacePrompt({ onCreate }) {
  const [name, setName] = useState("");
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit(e) {
    e.preventDefault();
    if (!name.trim()) return;
    setIsSubmitting(true);
    await onCreate(name.trim());
    setIsSubmitting(false);
  }

  return (
    <div
      style={{
        minHeight: "100vh",
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        padding: "0 24px",
      }}
    >
      <form
        onSubmit={handleSubmit}
        style={{
          width: "100%",
          maxWidth: 360,
          display: "flex",
          flexDirection: "column",
          gap: 20,
          textAlign: "center",
        }}
      >
        <div>
          <h1
            className="poppins"
            style={{ fontWeight: 700, fontSize: "1.7rem", marginBottom: 6 }}
          >
            Create your first workspace
          </h1>
          <p style={{ color: "var(--ink-dim)", fontSize: 14 }}>
            A workspace holds your team's boards. You can add more later.
          </p>
        </div>

        <div className="field" style={{ textAlign: "left" }}>
          <label htmlFor="workspace-name">Workspace name</label>
          <input
            id="workspace-name"
            value={name}
            onChange={(e) => setName(e.target.value)}
            placeholder="Product Team"
            required
          />
        </div>

        <button
          type="submit"
          className="btn btn-primary"
          disabled={isSubmitting}
          style={{ height: 44 }}
        >
          {isSubmitting ? "Creating..." : "Create workspace"}
        </button>
      </form>
    </div>
  );
}
