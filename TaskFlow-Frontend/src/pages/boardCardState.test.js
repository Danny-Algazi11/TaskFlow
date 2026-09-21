import { describe, expect, it } from 'vitest';
import { applyCardCreated, applyCardUpdated, applyCardDeleted, applyCardsReordered } from './boardCardState';

describe('applyCardCreated', () => {
  it('appends a new card to the target list', () => {
    const state = { 1: [{ id: 10, board_list_id: 1, title: 'Existing' }] };
    const card = { id: 11, board_list_id: 1, title: 'New' };

    const next = applyCardCreated(state, 1, card);

    expect(next[1]).toEqual([
      { id: 10, board_list_id: 1, title: 'Existing' },
      card,
    ]);
  });

  it('creates the list entry if the list has no cards yet', () => {
    const next = applyCardCreated({}, 5, { id: 1, board_list_id: 5, title: 'First' });

    expect(next[5]).toEqual([{ id: 1, board_list_id: 5, title: 'First' }]);
  });

  it('does not duplicate a card that already exists in the list (upsert)', () => {
    // This is the exact race this function exists to handle: the backend
    // broadcasts card.created back to the tab that created the card, and
    // that broadcast can arrive before or after this same card was already
    // added from the create request's own HTTP response.
    const card = { id: 11, board_list_id: 1, title: 'New' };
    const state = { 1: [card] };

    const next = applyCardCreated(state, 1, card);

    expect(next[1]).toHaveLength(1);
    expect(next).toBe(state); // no-op: same reference back out
  });

  it('produces the same single-entry result regardless of call order', () => {
    // Simulates both possible arrival orders of "local optimistic add" vs.
    // "this card's own echo" — the end state must be identical either way.
    const card = { id: 1, board_list_id: 1, title: 'Race' };

    const echoFirst = applyCardCreated(applyCardCreated({}, 1, card), 1, card);
    const localFirst = applyCardCreated(applyCardCreated({}, 1, card), 1, card);

    expect(echoFirst).toEqual(localFirst);
    expect(echoFirst[1]).toHaveLength(1);
  });

  it('does not mutate the input state', () => {
    const state = { 1: [{ id: 1, board_list_id: 1, title: 'A' }] };
    const snapshot = JSON.parse(JSON.stringify(state));

    applyCardCreated(state, 1, { id: 2, board_list_id: 1, title: 'B' });

    expect(state).toEqual(snapshot);
  });
});

describe('applyCardUpdated', () => {
  it('replaces the matching card by id, in place', () => {
    const state = {
      1: [
        { id: 1, board_list_id: 1, title: 'Old title' },
        { id: 2, board_list_id: 1, title: 'Untouched' },
      ],
    };

    const next = applyCardUpdated(state, { id: 1, board_list_id: 1, title: 'New title' });

    expect(next[1]).toEqual([
      { id: 1, board_list_id: 1, title: 'New title' },
      { id: 2, board_list_id: 1, title: 'Untouched' },
    ]);
  });

  it('no-ops if the card is not known yet', () => {
    const state = { 1: [{ id: 1, board_list_id: 1, title: 'A' }] };

    const next = applyCardUpdated(state, { id: 999, board_list_id: 1, title: 'Ghost' });

    expect(next).toBe(state);
  });

  it('only touches the card list named by the updated card', () => {
    const state = {
      1: [{ id: 1, board_list_id: 1, title: 'A' }],
      2: [{ id: 2, board_list_id: 2, title: 'B' }],
    };

    const next = applyCardUpdated(state, { id: 1, board_list_id: 1, title: 'A2' });

    expect(next[2]).toBe(state[2]); // untouched list keeps its original reference
  });

  it('is idempotent when applied twice with the same data', () => {
    const state = { 1: [{ id: 1, board_list_id: 1, title: 'Old' }] };
    const updated = { id: 1, board_list_id: 1, title: 'New' };

    const once = applyCardUpdated(state, updated);
    const twice = applyCardUpdated(once, updated);

    expect(twice).toEqual(once);
  });
});

describe('applyCardDeleted', () => {
  it('removes the card from whichever list holds it', () => {
    const state = {
      1: [{ id: 1, board_list_id: 1 }, { id: 2, board_list_id: 1 }],
      2: [{ id: 3, board_list_id: 2 }],
    };

    const next = applyCardDeleted(state, 2);

    expect(next[1]).toEqual([{ id: 1, board_list_id: 1 }]);
    expect(next[2]).toEqual([{ id: 3, board_list_id: 2 }]);
  });

  it('no-ops (returns equivalent state) if the card does not exist', () => {
    const state = { 1: [{ id: 1, board_list_id: 1 }] };

    const next = applyCardDeleted(state, 999);

    expect(next).toEqual(state);
  });

  it('is idempotent when applied twice for the same id', () => {
    const state = { 1: [{ id: 1, board_list_id: 1 }, { id: 2, board_list_id: 1 }] };

    const once = applyCardDeleted(state, 1);
    const twice = applyCardDeleted(once, 1);

    expect(twice).toEqual(once);
    expect(twice[1]).toEqual([{ id: 2, board_list_id: 1 }]);
  });
});

describe('applyCardsReordered', () => {
  it('reorders cards within the same list', () => {
    const state = {
      1: [
        { id: 1, board_list_id: 1, title: 'A' },
        { id: 2, board_list_id: 1, title: 'B' },
      ],
    };

    const next = applyCardsReordered(state, { list_id: 1, card_ids: [2, 1] });

    expect(next[1].map((c) => c.id)).toEqual([2, 1]);
  });

  it('moves a card from another list into the target list, updating board_list_id', () => {
    const state = {
      1: [{ id: 1, board_list_id: 1, title: 'Moving' }],
      2: [{ id: 2, board_list_id: 2, title: 'Staying' }],
    };

    // Card 1 dragged into list 2, ending up second.
    const next = applyCardsReordered(state, { list_id: 2, card_ids: [2, 1] });

    expect(next[1]).toEqual([]);
    expect(next[2]).toEqual([
      { id: 2, board_list_id: 2, title: 'Staying' },
      { id: 1, board_list_id: 2, title: 'Moving' },
    ]);
  });

  it('is idempotent when applied twice with the same payload', () => {
    const state = {
      1: [{ id: 1, board_list_id: 1 }],
      2: [{ id: 2, board_list_id: 2 }],
    };
    const payload = { list_id: 2, card_ids: [2, 1] };

    const once = applyCardsReordered(state, payload);
    const twice = applyCardsReordered(once, payload);

    expect(twice).toEqual(once);
  });

  it('skips card ids it does not have locally instead of throwing', () => {
    const state = { 1: [{ id: 1, board_list_id: 1 }] };

    const next = applyCardsReordered(state, { list_id: 1, card_ids: [1, 999] });

    expect(next[1]).toEqual([{ id: 1, board_list_id: 1 }]);
  });
});
