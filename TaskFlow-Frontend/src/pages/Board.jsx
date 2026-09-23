import { useEffect, useState } from "react";
import { Link, useNavigate, useParams } from "react-router-dom";
import {
  DndContext,
  DragOverlay,
  PointerSensor,
  closestCorners,
  useSensor,
  useSensors,
} from "@dnd-kit/core";
import {
  SortableContext,
  arrayMove,
  horizontalListSortingStrategy,
} from "@dnd-kit/sortable";
import ListColumn from "../components/ListColumn";
import CardDetailModal from "../components/CardDetailModal";
import InviteModal from "../components/InviteModal";
import { useAuth } from "../context/AuthContext";
import echo from "../echo";
import {
  applyCardCreated,
  applyCardUpdated,
  applyCardDeleted,
  applyCardsReordered,
} from "./boardCardState";
import * as boardsApi from "../api/boards";
import * as workspacesApi from "../api/workspaces";
import Skeleton from "react-loading-skeleton";
import "react-loading-skeleton/dist/skeleton.css";

function initials(name) {
  return name
    .split(" ")
    .map((p) => p[0])
    .slice(0, 2)
    .join("")
    .toUpperCase();
}

const MEMBER_COLORS = [
  "var(--teal)",
  "var(--green)",
  "var(--violet)",
  "var(--rose)",
  "var(--blue)",
];

/**
 * Sortable ids share one flat namespace across the whole DndContext (lists
 * and cards alike), so a list id and a card id could otherwise collide —
 * every id is prefixed by type and parsed back here.
 */
function parseSortableId(id) {
  if (typeof id !== "string") return null;
  if (id.startsWith("list-drop-"))
    return { type: "list", id: Number(id.slice("list-drop-".length)) };
  if (id.startsWith("list-"))
    return { type: "list", id: Number(id.slice("list-".length)) };
  if (id.startsWith("card-"))
    return { type: "card", id: Number(id.slice("card-".length)) };
  return null;
}

export default function Board() {
  const { boardId } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [board, setBoard] = useState(null);
  const [boardName, setBoardName] = useState("");
  const [workspace, setWorkspace] = useState(null);
  const [members, setMembers] = useState([]);
  const [lists, setLists] = useState(null);
  // { [listId]: Card[] } — owned here, not per-list, because a cross-list
  // drag needs to move one card between two lists' arrays atomically.
  const [cardsByList, setCardsByList] = useState({});
  const [isAddingList, setIsAddingList] = useState(false);
  const [newListName, setNewListName] = useState("");
  const [openCard, setOpenCard] = useState(null); // { card, listName }
  const [activeItem, setActiveItem] = useState(null); // { type, data } — for DragOverlay
  const [showInviteModal, setShowInviteModal] = useState(false);

  const sensors = useSensors(
    useSensor(PointerSensor, { activationConstraint: { distance: 8 } }),
  );

  useEffect(() => {
    let isCurrent = true;

    async function load() {
      const boardData = await boardsApi.getBoard(boardId);
      if (!isCurrent) return;
      setBoard(boardData);
      setBoardName(boardData.name);

      const [listData, workspaceData, memberData] = await Promise.all([
        boardsApi.listLists(boardId),
        workspacesApi.getWorkspace(boardData.workspace_id),
        workspacesApi.listMembers(boardData.workspace_id),
      ]);
      if (!isCurrent) return;
      setLists(listData);
      setWorkspace(workspaceData);
      setMembers(memberData);

      const cardLists = await Promise.all(
        listData.map((list) => boardsApi.listCards(list.id)),
      );
      if (!isCurrent) return;
      const byList = {};
      listData.forEach((list, i) => {
        byList[list.id] = cardLists[i];
      });
      setCardsByList(byList);
    }

    load();
    return () => {
      isCurrent = false;
    };
  }, [boardId]);

  function findListIdForCard(cardId) {
    for (const [listId, items] of Object.entries(cardsByList)) {
      if (items.some((c) => c.id === cardId)) return Number(listId);
    }
    return null;
  }

  async function handleAddList(e) {
    e.preventDefault();
    if (!newListName.trim()) return;
    const list = await boardsApi.createList(boardId, {
      name: newListName.trim(),
    });
    setLists((prev) => [...prev, list]);
    setCardsByList((prev) => ({ ...prev, [list.id]: [] }));
    setNewListName("");
    setIsAddingList(false);
  }

  async function handleRenameBoard() {
    if (!boardName.trim() || boardName === board.name) {
      setBoardName(board.name);
      return;
    }
    const updated = await boardsApi.updateBoard(boardId, {
      name: boardName.trim(),
    });
    setBoard(updated);
    setBoardName(updated.name);
  }

  async function handleDeleteBoard() {
    if (
      !window.confirm(
        `Delete "${board.name}"? All its lists and cards will be permanently removed. This cannot be undone.`,
      )
    ) {
      return;
    }
    await boardsApi.deleteBoard(boardId);
    navigate(`/?workspace=${board.workspace_id}`);
  }

  function handleListUpdated(updatedList) {
    setLists((prev) =>
      prev.map((l) => (l.id === updatedList.id ? updatedList : l)),
    );
  }

  function handleListDeleted(listId) {
    setLists((prev) => prev.filter((l) => l.id !== listId));
    setCardsByList((prev) => {
      const next = { ...prev };
      delete next[listId];
      return next;
    });
  }

  // The actual state-transition logic for all four of these lives in
  // boardCardState.js (pure, unit-tested there) — each has to behave
  // correctly whether it's applied once (a local action) or twice (that
  // same action plus its own Echo broadcast; see that file's docblock).
  function handleCardCreated(listId, card) {
    setCardsByList((prev) => applyCardCreated(prev, listId, card));
  }

  function handleCardUpdated(updatedCard) {
    setCardsByList((prev) => applyCardUpdated(prev, updatedCard));
  }

  // Also used for remote card.deleted events (see the Echo effect below),
  // where the deleted card usually isn't the one open locally — only close
  // the modal when it actually is.
  function handleCardDeleted(cardId) {
    setCardsByList((prev) => applyCardDeleted(prev, cardId));
    setOpenCard((prev) => (prev?.card.id === cardId ? null : prev));
  }

  function handleCardsReordered(payload) {
    setCardsByList((prev) => applyCardsReordered(prev, payload));
  }

  // Live sync with other members viewing the same board — the backend
  // broadcasts card CRUD/reorder on a private per-board channel (see
  // PROJECT_HANDOFF.md's real-time section). Every event name needs its
  // leading "." since these events declare a custom broadcastAs() name;
  // without it Echo listens for the (wrong) default PHP class-name event.
  useEffect(() => {
    const channel = echo.private(`board.${boardId}`);

    channel.listen(".card.created", (payload) =>
      handleCardCreated(payload.board_list_id, payload),
    );
    channel.listen(".card.updated", handleCardUpdated);
    channel.listen(".card.deleted", (payload) => handleCardDeleted(payload.id));
    channel.listen(".cards.reordered", handleCardsReordered);

    return () => {
      echo.leave(`board.${boardId}`);
    };
  }, [boardId]);

  function handleDragStart(event) {
    const parsed = parseSortableId(event.active.id);
    if (!parsed) return;
    if (parsed.type === "card") {
      const listId = findListIdForCard(parsed.id);
      setActiveItem({
        type: "card",
        data: cardsByList[listId]?.find((c) => c.id === parsed.id),
      });
    } else {
      setActiveItem({
        type: "list",
        data: lists.find((l) => l.id === parsed.id),
      });
    }
  }

  // Moves a dragged card between lists' arrays live, so it visually jumps
  // to the hovered column while still dragging — the final in-list order
  // and the API call happen in handleDragEnd.
  function handleDragOver(event) {
    const { active, over } = event;
    if (!over) return;
    const activeParsed = parseSortableId(active.id);
    if (!activeParsed || activeParsed.type !== "card") return;

    const activeListId = findListIdForCard(activeParsed.id);
    const overListId = over.data.current?.listId;
    if (activeListId === null || !overListId || activeListId === overListId)
      return;

    setCardsByList((prev) => {
      const sourceItems = prev[activeListId] ?? [];
      const destItems = prev[overListId] ?? [];
      const activeIndex = sourceItems.findIndex(
        (c) => c.id === activeParsed.id,
      );
      if (activeIndex === -1) return prev;

      const movedCard = {
        ...sourceItems[activeIndex],
        board_list_id: overListId,
      };
      const newSource = sourceItems.filter((c) => c.id !== activeParsed.id);

      let insertIndex = destItems.length;
      const overParsed = parseSortableId(over.id);
      if (overParsed?.type === "card") {
        const idx = destItems.findIndex((c) => c.id === overParsed.id);
        if (idx !== -1) insertIndex = idx;
      }
      const newDest = [
        ...destItems.slice(0, insertIndex),
        movedCard,
        ...destItems.slice(insertIndex),
      ];

      return { ...prev, [activeListId]: newSource, [overListId]: newDest };
    });
  }

  // Reads `lists`/`cardsByList` from the render closure rather than via
  // the setState-updater form, and fires the persistence call outside any
  // updater — React 18 StrictMode intentionally double-invokes updater
  // functions in development, so a side effect (the API call) placed
  // inside one would silently fire twice in dev only. Keeping updaters
  // pure and doing the API call as a separate statement avoids that.
  function handleDragEnd(event) {
    const { active, over } = event;
    setActiveItem(null);
    if (!over) return;

    const activeParsed = parseSortableId(active.id);
    if (!activeParsed) return;

    if (activeParsed.type === "list") {
      const overParsed = parseSortableId(over.id);
      if (
        !overParsed ||
        overParsed.type !== "list" ||
        activeParsed.id === overParsed.id
      )
        return;

      const oldIndex = lists.findIndex((l) => l.id === activeParsed.id);
      const newIndex = lists.findIndex((l) => l.id === overParsed.id);
      if (oldIndex === -1 || newIndex === -1) return;

      const newLists = arrayMove(lists, oldIndex, newIndex);
      setLists(newLists);
      boardsApi
        .reorderLists(
          boardId,
          newLists.map((l) => l.id),
        )
        .catch(() => {});
      return;
    }

    // Card: onDragOver already placed it in the right list's array if this
    // was a cross-list move — this just fixes the final position within
    // that list and persists the whole thing, matching the backend's own
    // "target list's array is authoritative" model.
    const targetListId = findListIdForCard(activeParsed.id);
    if (targetListId === null) return;

    const items = cardsByList[targetListId] ?? [];
    const activeIndex = items.findIndex((c) => c.id === activeParsed.id);
    if (activeIndex === -1) return;

    let overIndex = items.length - 1;
    const overParsed = parseSortableId(over.id);
    if (overParsed?.type === "card") {
      const idx = items.findIndex((c) => c.id === overParsed.id);
      if (idx !== -1) overIndex = idx;
    }

    const reordered = arrayMove(items, activeIndex, overIndex);
    setCardsByList((prev) => ({ ...prev, [targetListId]: reordered }));
    boardsApi
      .reorderCards(
        targetListId,
        reordered.map((c) => c.id),
      )
      .catch(() => {});
  }

  if (!board || lists === null) {
    return (
      <div
        className="app-shell"
        style={{ flexDirection: "column", height: "100vh" }}
      >
        <div className="board-topbar">
          <div className="crumbs">
            <Skeleton width={22} height={22} borderRadius={7} />
            <div
              className="crumb-divider"
              style={{ width: 1, height: 20, background: "var(--line)" }}
            />
            <Skeleton width={140} height={18} />
          </div>
          <div className="board-actions">
            <div className="avatars-inline">
              {Array.from({ length: 3 }).map((_, i) => (
                <Skeleton key={i} circle width={28} height={28} />
              ))}
            </div>
          </div>
        </div>

        <div className="board-columns">
          {Array.from({ length: 3 }).map((_, i) => (
            <div key={i} className="list">
              <div className="list-head">
                <Skeleton width="55%" height={14} />
                <Skeleton width={18} height={12} />
              </div>
              <div className="list-cards">
                {Array.from({ length: 3 }).map((__, j) => (
                  <div key={j} className="card" style={{ cursor: "default" }}>
                    <Skeleton width="70%" height={13.5} />
                    <Skeleton width="45%" height={12} />
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div
      className="app-shell"
      style={{ flexDirection: "column", height: "100vh" }}
    >
      <div className="board-topbar">
        <div className="crumbs">
          <Link
            to="/"
            style={{ display: "flex", alignItems: "center", gap: 12 }}
          >
            <span
              style={{
                width: 22,
                height: 22,
                borderRadius: 7,
                background: "var(--blue)",
                display: "inline-block",
                flexShrink: 0,
              }}
            />
            <div
              className="crumb-divider"
              style={{ width: 1, height: 20, background: "var(--line)" }}
            />
            <span className="ws-name">{workspace?.name}</span>
          </Link>
          <svg
            className="icon"
            style={{ width: 13, height: 13, color: "var(--ink-faint)" }}
            viewBox="0 0 16 16"
            fill="none"
          >
            <path
              d="M6 3l5 5-5 5"
              stroke="currentColor"
              strokeWidth="1.6"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
          </svg>
          <input
            className="board-name-input"
            value={boardName}
            onChange={(e) => setBoardName(e.target.value)}
            onBlur={handleRenameBoard}
          />
          <button
            type="button"
            className="board-delete"
            onClick={handleDeleteBoard}
            title="Delete board"
          >
            <svg
              className="icon"
              style={{ width: 14, height: 14 }}
              viewBox="0 0 16 16"
              fill="none"
            >
              <path
                d="M3.5 5h9M6.5 5V3.5h3V5M4.5 5l.6 8h5.8l.6-8"
                stroke="currentColor"
                strokeWidth="1.6"
                strokeLinecap="round"
                strokeLinejoin="round"
              />
            </svg>
          </button>
        </div>
        <div className="board-actions">
          <div className="avatars-inline">
            {members.slice(0, 4).map((member, i) => (
              <div
                key={member.id}
                className="avatar"
                style={{
                  width: 28,
                  height: 28,
                  fontSize: 11,
                  background: MEMBER_COLORS[i % MEMBER_COLORS.length],
                }}
                title={member.name}
              >
                {initials(member.name)}
              </div>
            ))}
          </div>
          <button
            className="btn btn-ghost"
            style={{ padding: "8px 16px", fontSize: 13 }}
            disabled={
              !members.some((m) => m.id === user.id && m.role === "admin")
            }
            title={
              members.some((m) => m.id === user.id && m.role === "admin")
                ? undefined
                : "Only workspace admins can invite"
            }
            onClick={() => setShowInviteModal(true)}
          >
            Invite
          </button>
        </div>
      </div>

      <DndContext
        sensors={sensors}
        collisionDetection={closestCorners}
        onDragStart={handleDragStart}
        onDragOver={handleDragOver}
        onDragEnd={handleDragEnd}
      >
        <SortableContext
          items={lists.map((l) => `list-${l.id}`)}
          strategy={horizontalListSortingStrategy}
        >
          <div className="board-columns">
            {lists.map((list) => (
              <ListColumn
                key={list.id}
                list={list}
                cards={cardsByList[list.id] ?? []}
                onOpenCard={(card, listName) => setOpenCard({ card, listName })}
                onCardCreated={handleCardCreated}
                onListUpdated={handleListUpdated}
                onListDeleted={handleListDeleted}
              />
            ))}

            {isAddingList ? (
              <form
                onSubmit={handleAddList}
                style={{ width: 264, flexShrink: 0 }}
              >
                <input
                  autoFocus
                  className="inline-input"
                  style={{ height: 44 }}
                  value={newListName}
                  onChange={(e) => setNewListName(e.target.value)}
                  onBlur={() => !newListName.trim() && setIsAddingList(false)}
                  placeholder="List name"
                />
              </form>
            ) : (
              <button
                type="button"
                className="add-list"
                onClick={() => setIsAddingList(true)}
              >
                <svg
                  className="icon"
                  style={{ width: 15, height: 15 }}
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
                Add another list
              </button>
            )}
          </div>
        </SortableContext>

        <DragOverlay>
          {activeItem?.type === "card" && activeItem.data && (
            <div className="card" style={{ cursor: "grabbing" }}>
              <div className="title-text">{activeItem.data.title}</div>
            </div>
          )}
          {activeItem?.type === "list" && activeItem.data && (
            <div className="list" style={{ cursor: "grabbing", opacity: 0.9 }}>
              <div className="list-head">
                <div className="title">
                  <span className="name">{activeItem.data.name}</span>
                </div>
              </div>
            </div>
          )}
        </DragOverlay>
      </DndContext>

      {openCard && (
        <CardDetailModal
          cardId={openCard.card.id}
          listName={openCard.listName}
          workspaceId={board.workspace_id}
          members={members}
          onClose={() => setOpenCard(null)}
          onUpdated={handleCardUpdated}
          onDeleted={() => handleCardDeleted(openCard.card.id)}
        />
      )}

      {showInviteModal && (
        <InviteModal
          workspace={workspace}
          onClose={() => setShowInviteModal(false)}
        />
      )}
    </div>
  );
}
