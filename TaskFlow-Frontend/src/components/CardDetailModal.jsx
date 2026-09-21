import { useEffect, useRef, useState } from 'react';
import { useAuth } from '../context/AuthContext';
import * as cardsApi from '../api/cards';
import * as workspacesApi from '../api/workspaces';

const LABEL_COLOR_CHOICES = ['#E0473F', '#2F6FED', '#22B573', '#E0972B', '#7C5CE0', '#D9587B'];
const MEMBER_COLORS = ['var(--teal)', 'var(--green)', 'var(--violet)', 'var(--rose)', 'var(--blue)'];

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

export default function CardDetailModal({ cardId, listName, workspaceId, members, onClose, onUpdated, onDeleted }) {
  const { user } = useAuth();
  const membersById = Object.fromEntries(members.map((m) => [m.id, m]));

  const [card, setCard] = useState(null);
  const [title, setTitle] = useState('');
  const [description, setDescription] = useState('');
  const [dueDate, setDueDate] = useState('');
  const [isEditingDescription, setIsEditingDescription] = useState(false);

  const [labels, setLabels] = useState([]);
  const [workspaceLabels, setWorkspaceLabels] = useState([]);
  const [showLabelPicker, setShowLabelPicker] = useState(false);
  const [newLabelName, setNewLabelName] = useState('');
  const [newLabelColor, setNewLabelColor] = useState(LABEL_COLOR_CHOICES[0]);

  const [assignees, setAssignees] = useState([]);
  const [showAssigneePicker, setShowAssigneePicker] = useState(false);

  const [checklistItems, setChecklistItems] = useState([]);
  const [newItemTitle, setNewItemTitle] = useState('');
  const newItemInputRef = useRef(null);
  const dueDateInputRef = useRef(null);

  const [comments, setComments] = useState([]);
  const [newComment, setNewComment] = useState('');

  useEffect(() => {
    let isCurrent = true;

    Promise.all([
      cardsApi.getCard(cardId),
      cardsApi.listCardLabels(cardId),
      cardsApi.listCardAssignees(cardId),
      cardsApi.listChecklistItems(cardId),
      cardsApi.listComments(cardId),
      workspacesApi.listWorkspaceLabels(workspaceId),
    ]).then(([cardData, labelData, assigneeData, itemData, commentData, wsLabelData]) => {
      if (!isCurrent) return;
      setCard(cardData);
      setTitle(cardData.title);
      setDescription(cardData.description ?? '');
      setDueDate(cardData.due_date ?? '');
      setLabels(labelData);
      setAssignees(assigneeData);
      setChecklistItems(itemData);
      setComments(commentData);
      setWorkspaceLabels(wsLabelData);
    });

    return () => {
      isCurrent = false;
    };
  }, [cardId, workspaceId]);

  async function saveFields(overrides = {}) {
    const payload = {
      title: overrides.title ?? title,
      description: overrides.description ?? description,
      dueDate: overrides.dueDate ?? dueDate,
    };
    const updated = await cardsApi.updateCard(cardId, payload);
    setCard(updated);
    setTitle(updated.title);
    setDescription(updated.description ?? '');
    setDueDate(updated.due_date ?? '');
    onUpdated(updated);
  }

  async function handleToggleLabel(label) {
    const isAttached = labels.some((l) => l.id === label.id);
    if (isAttached) {
      await cardsApi.detachLabel(cardId, label.id);
      setLabels((prev) => prev.filter((l) => l.id !== label.id));
    } else {
      await cardsApi.attachLabel(cardId, label.id);
      setLabels((prev) => [...prev, label]);
    }
  }

  async function handleCreateLabel(e) {
    e.preventDefault();
    if (!newLabelName.trim()) return;
    const label = await workspacesApi.createLabel(workspaceId, { name: newLabelName.trim(), color: newLabelColor });
    setWorkspaceLabels((prev) => [...prev, label]);
    await cardsApi.attachLabel(cardId, label.id);
    setLabels((prev) => [...prev, label]);
    setNewLabelName('');
  }

  async function handleToggleAssignee(member) {
    const isAssigned = assignees.some((a) => a.id === member.id);
    if (isAssigned) {
      await cardsApi.unassignUser(cardId, member.id);
      setAssignees((prev) => prev.filter((a) => a.id !== member.id));
    } else {
      await cardsApi.assignUser(cardId, member.id);
      setAssignees((prev) => [...prev, member]);
    }
  }

  async function handleAddChecklistItem(e) {
    e.preventDefault();
    if (!newItemTitle.trim()) return;
    const item = await cardsApi.createChecklistItem(cardId, { title: newItemTitle.trim() });
    setChecklistItems((prev) => [...prev, item]);
    setNewItemTitle('');
  }

  async function handleToggleChecklistItem(item) {
    const updated = await cardsApi.toggleChecklistItem(item.id);
    setChecklistItems((prev) => prev.map((i) => (i.id === item.id ? updated : i)));
  }

  async function handleDeleteChecklistItem(item) {
    await cardsApi.deleteChecklistItem(item.id);
    setChecklistItems((prev) => prev.filter((i) => i.id !== item.id));
  }

  async function handleAddComment(e) {
    e.preventDefault();
    if (!newComment.trim()) return;
    const comment = await cardsApi.createComment(cardId, { body: newComment.trim() });
    setComments((prev) => [comment, ...prev]);
    setNewComment('');
  }

  async function handleDeleteComment(comment) {
    await cardsApi.deleteComment(comment.id);
    setComments((prev) => prev.filter((c) => c.id !== comment.id));
  }

  async function handleDeleteCard() {
    if (!window.confirm('Delete this card? This cannot be undone.')) return;
    await cardsApi.deleteCard(cardId);
    onDeleted();
  }

  if (!card) return null;

  const doneCount = checklistItems.filter((i) => i.is_complete).length;
  const progressPct = checklistItems.length ? Math.round((doneCount / checklistItems.length) * 100) : 0;
  const availableLabels = workspaceLabels.filter((l) => !labels.some((attached) => attached.id === l.id));
  const availableMembers = members;

  return (
    <>
      <div className="scrim" onClick={onClose} />
      <div className="modal">
        <div className="modal-body">
          <div className="modal-head">
            <div className="crumb">
              <svg className="icon" style={{ width: 14, height: 14 }} viewBox="0 0 20 20" fill="none">
                <rect x="3" y="3" width="6" height="14" rx="1.5" stroke="currentColor" strokeWidth="1.6" />
                <rect x="11" y="3" width="6" height="8" rx="1.5" stroke="currentColor" strokeWidth="1.6" />
              </svg>
              in list <b>{listName}</b>
            </div>
            <button type="button" className="modal-close" onClick={onClose}>
              <svg className="icon" viewBox="0 0 16 16" fill="none">
                <path d="M3.5 3.5l9 9M12.5 3.5l-9 9" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
              </svg>
            </button>
          </div>

          <div style={{ padding: '10px 24px 0 24px' }}>
            <input
              className="modal-title-input"
              value={title}
              onChange={(e) => setTitle(e.target.value)}
              onBlur={() => title.trim() && title !== card.title && saveFields({ title: title.trim() })}
            />
          </div>

          <div className="modal-section">
            <div className="meta-grid">
              <div className="meta-col">
                <span className="section-label">Assignees</span>
                <div className="chip-row">
                  {assignees.map((a, i) => (
                    <div
                      key={a.id}
                      className="avatar"
                      style={{ width: 24, height: 24, fontSize: 10, background: MEMBER_COLORS[i % MEMBER_COLORS.length], cursor: 'pointer' }}
                      title={`${a.name} (click to remove)`}
                      onClick={() => handleToggleAssignee(a)}
                    >
                      {initials(a.name)}
                    </div>
                  ))}
                  <button type="button" className="add-pill" onClick={() => setShowAssigneePicker((v) => !v)}>
                    <svg className="icon" style={{ width: 11, height: 11 }} viewBox="0 0 16 16" fill="none">
                      <path d="M8 3v10M3 8h10" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                    </svg>
                  </button>
                </div>
                {showAssigneePicker && (
                  <div className="picker">
                    {availableMembers.map((m) => (
                      <button key={m.id} type="button" className={`picker-item${assignees.some((a) => a.id === m.id) ? ' selected' : ''}`} onClick={() => handleToggleAssignee(m)}>
                        {m.name}
                      </button>
                    ))}
                  </div>
                )}
              </div>

              <div className="meta-col">
                <span className="section-label">Labels</span>
                <div className="chip-row">
                  {labels.map((label) => (
                    <span key={label.id} className="chip" style={{ background: label.color, cursor: 'pointer' }} onClick={() => handleToggleLabel(label)} title="Click to remove">
                      {label.name}
                    </span>
                  ))}
                  <button type="button" className="add-pill square" onClick={() => setShowLabelPicker((v) => !v)}>
                    <svg className="icon" style={{ width: 11, height: 11 }} viewBox="0 0 16 16" fill="none">
                      <path d="M8 3v10M3 8h10" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                    </svg>
                  </button>
                </div>
                {showLabelPicker && (
                  <div className="picker">
                    {availableLabels.map((label) => (
                      <button key={label.id} type="button" className="picker-item" onClick={() => handleToggleLabel(label)}>
                        <span style={{ width: 12, height: 12, borderRadius: 3, background: label.color, display: 'inline-block' }} />
                        {label.name}
                      </button>
                    ))}
                    <form onSubmit={handleCreateLabel} style={{ display: 'flex', flexDirection: 'column', gap: 6, padding: 6, borderTop: '1px solid var(--line)', marginTop: 4 }}>
                      <input
                        className="inline-input"
                        style={{ height: 28 }}
                        placeholder="New label name"
                        value={newLabelName}
                        onChange={(e) => setNewLabelName(e.target.value)}
                      />
                      <div style={{ display: 'flex', gap: 5 }}>
                        {LABEL_COLOR_CHOICES.map((color) => (
                          <button
                            key={color}
                            type="button"
                            onClick={() => setNewLabelColor(color)}
                            style={{
                              width: 18, height: 18, borderRadius: '50%', background: color, border: newLabelColor === color ? '2px solid var(--ink)' : '2px solid transparent', cursor: 'pointer', padding: 0,
                            }}
                          />
                        ))}
                      </div>
                      <button type="submit" className="btn btn-primary" style={{ height: 28, fontSize: 12 }}>Create &amp; attach</button>
                    </form>
                  </div>
                )}
              </div>

              <div className="meta-col">
                <span className="section-label">Due date</span>
                <input
                  ref={dueDateInputRef}
                  type="date"
                  className="due-input"
                  value={dueDate ?? ''}
                  onChange={(e) => {
                    setDueDate(e.target.value);
                    saveFields({ dueDate: e.target.value });
                  }}
                />
              </div>
            </div>

            <div className="desc-row">
              <div className="row-label">
                <svg className="icon" style={{ width: 14, height: 14, color: 'var(--ink-faint)' }} viewBox="0 0 16 16" fill="none">
                  <path d="M2.5 3.5h11M2.5 7h11M2.5 10.5h7" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                </svg>
                <span className="section-label">Description</span>
              </div>
              {isEditingDescription ? (
                <textarea
                  autoFocus
                  className="desc-textarea"
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  onBlur={() => {
                    setIsEditingDescription(false);
                    if (description !== (card.description ?? '')) saveFields({ description });
                  }}
                />
              ) : (
                <div className="desc-text" onClick={() => setIsEditingDescription(true)}>
                  {description || <span style={{ color: 'var(--ink-faint)' }}>Add a description...</span>}
                </div>
              )}
            </div>

            <div className="desc-row">
              <div className="row-label">
                <svg className="icon" style={{ width: 14, height: 14, color: 'var(--ink-faint)' }} viewBox="0 0 16 16" fill="none">
                  <rect x="2.5" y="4.5" width="11" height="9" rx="1.5" stroke="currentColor" strokeWidth="1.6" />
                  <path d="M5 7.2l2 2 3.3-3.6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
                </svg>
                <span className="section-label">Checklist</span>
                {checklistItems.length > 0 && (
                  <span style={{ fontSize: 12, fontWeight: 700, color: 'var(--ink-faint)' }}>
                    {doneCount}/{checklistItems.length}
                  </span>
                )}
              </div>
              {checklistItems.length > 0 && (
                <div className="checklist-progress">
                  <div className="fill" style={{ width: `${progressPct}%` }} />
                </div>
              )}
              <div className="checklist-items">
                {checklistItems.map((item) => (
                  <div className="check-row" key={item.id}>
                    <button type="button" className={`check-box ${item.is_complete ? 'done' : 'pending'}`} onClick={() => handleToggleChecklistItem(item)}>
                      {item.is_complete && (
                        <svg className="icon" style={{ width: 10, height: 10 }} viewBox="0 0 16 16" fill="none">
                          <path d="M3.5 8.5l3 3 6-7" stroke="white" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                        </svg>
                      )}
                    </button>
                    <button type="button" className={`check-label${item.is_complete ? ' done' : ''}`} onClick={() => handleToggleChecklistItem(item)}>
                      {item.title}
                    </button>
                    <button type="button" className="check-remove" onClick={() => handleDeleteChecklistItem(item)} title="Remove">
                      <svg className="icon" style={{ width: 13, height: 13 }} viewBox="0 0 16 16" fill="none">
                        <path d="M3.5 3.5l9 9M12.5 3.5l-9 9" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
                      </svg>
                    </button>
                  </div>
                ))}

                <form onSubmit={handleAddChecklistItem} style={{ display: 'flex', gap: 7 }}>
                  <input
                    ref={newItemInputRef}
                    className="inline-input"
                    style={{ height: 30 }}
                    placeholder="Add item"
                    value={newItemTitle}
                    onChange={(e) => setNewItemTitle(e.target.value)}
                  />
                </form>
              </div>
            </div>

            <div className="desc-row">
              <div className="row-label">
                <svg className="icon" style={{ width: 14, height: 14, color: 'var(--ink-faint)' }} viewBox="0 0 16 16" fill="none">
                  <path d="M2.5 3.5h11v7h-6l-3 2.5v-2.5h-2z" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
                </svg>
                <span className="section-label">Comments</span>
              </div>

              {comments.map((comment) => {
                const author = membersById[comment.user_id];
                return (
                  <div className="comment-row" key={comment.id}>
                    <div className="avatar" style={{ width: 24, height: 24, fontSize: 10, background: 'var(--ink-faint)', marginTop: 2 }}>
                      {author ? initials(author.name) : '?'}
                    </div>
                    <div style={{ flex: 1 }}>
                      <div style={{ display: 'flex', alignItems: 'baseline', gap: 8, marginBottom: 3 }}>
                        <span className="comment-author">{author?.name ?? 'Unknown'}</span>
                        <span className="comment-time">{relativeTime(comment.created_at)}</span>
                        {comment.user_id === user.id && (
                          <button type="button" className="comment-remove" onClick={() => handleDeleteComment(comment)}>
                            Delete
                          </button>
                        )}
                      </div>
                      <div className="comment-body">{comment.body}</div>
                    </div>
                  </div>
                );
              })}

              <form onSubmit={handleAddComment} className="comment-row">
                <div className="avatar" style={{ width: 24, height: 24, fontSize: 10, background: 'var(--violet)', marginTop: 2 }}>
                  {initials(user.name)}
                </div>
                <textarea
                  className="comment-input"
                  placeholder="Write a comment..."
                  value={newComment}
                  onChange={(e) => setNewComment(e.target.value)}
                  rows={1}
                />
              </form>
            </div>
          </div>
        </div>

        <div className="rail">
          <span className="section-label" style={{ marginBottom: 2 }}>Add to card</span>
          <button type="button" className="rail-btn" onClick={() => setShowAssigneePicker((v) => !v)}>
            <svg className="icon" style={{ width: 14, height: 14 }} viewBox="0 0 20 20" fill="none">
              <circle cx="7" cy="7" r="3" stroke="currentColor" strokeWidth="1.6" />
              <path d="M2 17c0-3 2.2-5 5-5s5 2 5 5" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
            </svg>
            Members
          </button>
          <button type="button" className="rail-btn" onClick={() => setShowLabelPicker((v) => !v)}>
            <svg className="icon" style={{ width: 14, height: 14 }} viewBox="0 0 16 16" fill="none">
              <path d="M2 8l6-6h5.5V7.5L7.5 14 2 8z" stroke="currentColor" strokeWidth="1.6" strokeLinejoin="round" />
              <circle cx="10.3" cy="5.7" r="1" fill="currentColor" />
            </svg>
            Labels
          </button>
          <button type="button" className="rail-btn" onClick={() => newItemInputRef.current?.focus()}>
            <svg className="icon" style={{ width: 14, height: 14 }} viewBox="0 0 16 16" fill="none">
              <rect x="2.5" y="4.5" width="11" height="9" rx="1.5" stroke="currentColor" strokeWidth="1.6" />
              <path d="M5 7.2l2 2 3.3-3.6" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            Checklist
          </button>
          <button type="button" className="rail-btn" onClick={() => dueDateInputRef.current?.focus()}>
            <svg className="icon" style={{ width: 14, height: 14 }} viewBox="0 0 16 16" fill="none">
              <rect x="2.5" y="3.5" width="11" height="10" rx="1.5" stroke="currentColor" strokeWidth="1.6" />
              <path d="M2.5 6.5h11M5.5 2v3M10.5 2v3" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
            </svg>
            Due date
          </button>
          <button type="button" className="rail-btn" disabled title="Not built yet">
            <svg className="icon" style={{ width: 14, height: 14 }} viewBox="0 0 16 16" fill="none">
              <path d="M10 4L4.5 9.5a2.2 2.2 0 003 3L13 7" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            Attachment
          </button>

          <div style={{ height: 1, background: 'var(--line)', margin: '8px 0' }} />

          <button type="button" className="rail-btn" style={{ color: 'var(--red)', borderColor: 'rgba(224,71,63,0.3)' }} onClick={handleDeleteCard}>
            <svg className="icon" style={{ width: 14, height: 14 }} viewBox="0 0 16 16" fill="none">
              <path d="M3.5 5h9M6.5 5V3.5h3V5M4.5 5l.6 8h5.8l.6-8" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
            Delete card
          </button>
        </div>
      </div>
    </>
  );
}
