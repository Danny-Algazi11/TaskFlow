import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

function formatDueDate(dateString) {
  return new Date(dateString + 'T00:00:00').toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

export default function Card({ card, onOpenCard }) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: `card-${card.id}`,
    data: { type: 'card', listId: card.board_list_id },
  });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
    opacity: isDragging ? 0.4 : 1,
  };

  return (
    <button
      ref={setNodeRef}
      style={style}
      {...attributes}
      {...listeners}
      type="button"
      className="card"
      onClick={() => onOpenCard(card)}
    >
      <div className="title-text">{card.title}</div>
      {card.description && <div className="desc">{card.description}</div>}
      {card.due_date && (
        <div className="due-pill">
          <svg className="icon" style={{ width: 12, height: 12 }} viewBox="0 0 16 16" fill="none">
            <rect x="2.5" y="3.5" width="11" height="10" rx="1.5" stroke="currentColor" strokeWidth="1.4" />
            <path d="M2.5 6.5h11M5.5 2v3M10.5 2v3" stroke="currentColor" strokeWidth="1.4" strokeLinecap="round" />
          </svg>
          {formatDueDate(card.due_date)}
        </div>
      )}
    </button>
  );
}
