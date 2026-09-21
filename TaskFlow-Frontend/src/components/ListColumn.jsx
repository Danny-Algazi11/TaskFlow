import { useState } from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { SortableContext, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { useDroppable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import Card from './Card';
import * as boardsApi from '../api/boards';

/**
 * Cards state now lives in Board (see that file's docblock on why) — this
 * component just renders what it's given and reports actions upward.
 */
export default function ListColumn({ list, cards, onOpenCard, onCardCreated, onListUpdated, onListDeleted }) {
  const [isAdding, setIsAdding] = useState(false);
  const [title, setTitle] = useState('');
  const [name, setName] = useState(list.name);

  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: `list-${list.id}`,
    data: { type: 'list' },
  });

  // A list with zero cards has nowhere for useSortable's card items to
  // register a drop target — this makes the list body itself droppable so
  // a card can still be dragged into an empty list.
  const { setNodeRef: setDroppableRef } = useDroppable({
    id: `list-drop-${list.id}`,
    data: { type: 'list', listId: list.id },
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.5 : 1,
  };

  async function handleAddCard(e) {
    e.preventDefault();
    if (!title.trim()) return;
    const card = await boardsApi.createCard(list.id, { title: title.trim() });
    onCardCreated(list.id, card);
    setTitle('');
    setIsAdding(false);
  }

  async function handleRenameList() {
    if (!name.trim() || name === list.name) {
      setName(list.name);
      return;
    }
    const updated = await boardsApi.updateList(list.id, { name: name.trim() });
    onListUpdated(updated);
    setName(updated.name);
  }

  async function handleDeleteList() {
    if (!window.confirm(`Delete "${list.name}"? All its cards will be permanently removed. This cannot be undone.`)) {
      return;
    }
    await boardsApi.deleteList(list.id);
    onListDeleted(list.id);
  }

  return (
    <div ref={setNodeRef} style={style} className="list">
      <div className="list-head" {...attributes} {...listeners} style={{ cursor: 'grab' }}>
        <div className="title">
          <input
            className="list-name-input"
            value={name}
            onChange={(e) => setName(e.target.value)}
            onBlur={handleRenameList}
            onPointerDown={(e) => e.stopPropagation()}
          />
          <span className="count">{cards.length}</span>
        </div>
        <button
          type="button"
          className="list-delete"
          onClick={handleDeleteList}
          onPointerDown={(e) => e.stopPropagation()}
          title="Delete list"
        >
          <svg className="icon" style={{ width: 13, height: 13 }} viewBox="0 0 16 16" fill="none">
            <path d="M3.5 5h9M6.5 5V3.5h3V5M4.5 5l.6 8h5.8l.6-8" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" strokeLinejoin="round" />
          </svg>
        </button>
      </div>

      <SortableContext items={cards.map((c) => `card-${c.id}`)} strategy={verticalListSortingStrategy}>
        <div className="list-cards" ref={setDroppableRef}>
          {cards.map((card) => (
            <Card key={card.id} card={card} onOpenCard={() => onOpenCard(card, list.name)} />
          ))}
        </div>
      </SortableContext>

      {isAdding ? (
        <form onSubmit={handleAddCard} style={{ padding: '0 10px 12px 10px' }}>
          <input
            autoFocus
            className="inline-input"
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            onBlur={() => !title.trim() && setIsAdding(false)}
            placeholder="Card title"
          />
        </form>
      ) : (
        <button type="button" className="add-card-row" onClick={() => setIsAdding(true)}>
          <svg className="icon" style={{ width: 14, height: 14 }} viewBox="0 0 16 16" fill="none">
            <path d="M8 3v10M3 8h10" stroke="currentColor" strokeWidth="1.6" strokeLinecap="round" />
          </svg>
          Add card
        </button>
      )}
    </div>
  );
}
