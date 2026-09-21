import client from './client';

export async function getBoard(boardId) {
  const { data } = await client.get(`/api/boards/${boardId}`);
  return data;
}

export async function updateBoard(boardId, { name }) {
  const { data } = await client.patch(`/api/boards/${boardId}`, { name });
  return data;
}

export async function deleteBoard(boardId) {
  await client.delete(`/api/boards/${boardId}`);
}

export async function listLists(boardId) {
  const { data } = await client.get(`/api/boards/${boardId}/lists`);
  return data;
}

export async function createList(boardId, { name }) {
  const { data } = await client.post(`/api/boards/${boardId}/lists`, { name });
  return data;
}

export async function updateList(listId, { name }) {
  const { data } = await client.patch(`/api/lists/${listId}`, { name });
  return data;
}

export async function deleteList(listId) {
  await client.delete(`/api/lists/${listId}`);
}

export async function listCards(listId) {
  const { data } = await client.get(`/api/lists/${listId}/cards`);
  return data;
}

export async function createCard(listId, { title, description = null, dueDate = null }) {
  const { data } = await client.post(`/api/lists/${listId}/cards`, {
    title,
    description,
    due_date: dueDate,
  });
  return data;
}

/**
 * Lists on a board are a closed set on the backend — listIds must be
 * exactly the board's current lists, just reordered.
 */
export async function reorderLists(boardId, listIds) {
  await client.patch(`/api/boards/${boardId}/lists/reorder`, { list_ids: listIds });
}

/**
 * cardIds is the COMPLETE, final ordered set of cards that should end up
 * in targetListId — this one call covers both a same-list reorder and a
 * card dragged in from a different list on the same board, matching how
 * the backend itself models the operation.
 */
export async function reorderCards(targetListId, cardIds) {
  await client.patch(`/api/lists/${targetListId}/cards/reorder`, { card_ids: cardIds });
}
