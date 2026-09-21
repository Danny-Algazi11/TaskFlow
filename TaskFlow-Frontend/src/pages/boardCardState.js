/**
 * Pure state-transition functions for Board.jsx's `cardsByList` state
 * ({ [listId]: Card[] }), extracted specifically so they're unit-testable
 * without rendering the board: every one of these has already been the
 * site of a real bug once (see PROJECT_HANDOFF.md's real-time section),
 * because they're shared between two different callers with different
 * timing — the acting user's own optimistic update, and the eventual Echo
 * broadcast of that same change (which can arrive before OR after the
 * optimistic one, since ShouldBroadcastNow broadcasts synchronously
 * mid-request). Every function here has to behave correctly regardless of
 * how many times, or in what order, it's called for the same change.
 */

/**
 * Upsert rather than a plain append: this runs both for a card this tab
 * just created (from the POST response) and for the card.created echo of
 * that same creation arriving over the socket — whichever happens first
 * inserts the card, whichever happens second finds it already there.
 */
export function applyCardCreated(cardsByList, listId, card) {
  const items = cardsByList[listId] ?? [];
  if (items.some((c) => c.id === card.id)) return cardsByList;
  return { ...cardsByList, [listId]: [...items, card] };
}

/**
 * Replace-by-id is naturally idempotent — applying the same update twice
 * (once locally, once from its own echo) just replaces with identical
 * data the second time. No-ops if the card isn't known yet.
 */
export function applyCardUpdated(cardsByList, updatedCard) {
  const listId = updatedCard.board_list_id;
  const items = cardsByList[listId] ?? [];
  const idx = items.findIndex((c) => c.id === updatedCard.id);
  if (idx === -1) return cardsByList;
  const next = [...items];
  next[idx] = updatedCard;
  return { ...cardsByList, [listId]: next };
}

/**
 * Filter-by-id across every list — also naturally idempotent, and doesn't
 * assume which list the card was in (a caller doesn't always know).
 */
export function applyCardDeleted(cardsByList, cardId) {
  const next = {};
  for (const [listId, items] of Object.entries(cardsByList)) {
    next[listId] = items.filter((c) => c.id !== cardId);
  }
  return next;
}

/**
 * Mirrors the backend's own model of a reorder (see CardsReordered's
 * docblock in the backend): the target list's card_ids is authoritative
 * for what's in it now, in what order — whether that's a same-list
 * reorder or a card dragged in from elsewhere on the board. Rebuilding
 * every list from a single lookup of currently-known cards (rather than
 * patching positions in place) makes this safe to apply idempotently: the
 * acting user's own drag already applied this locally, and this function
 * also runs again for that same drag's echo.
 *
 * Card ids not found in `cardsByList` are skipped rather than throwing —
 * defensive against a card this tab hasn't loaded yet, which shouldn't
 * normally happen since the initial board load fetches every list's cards.
 */
export function applyCardsReordered(cardsByList, { list_id: listId, card_ids: cardIds }) {
  const byId = {};
  for (const items of Object.values(cardsByList)) {
    for (const card of items) byId[card.id] = card;
  }

  const next = {};
  for (const [id, items] of Object.entries(cardsByList)) {
    next[id] = items.filter((c) => !cardIds.includes(c.id));
  }

  next[listId] = cardIds
    .map((id) => (byId[id] ? { ...byId[id], board_list_id: listId } : null))
    .filter(Boolean);

  return next;
}
