import client from './client';

export async function getCard(cardId) {
  const { data } = await client.get(`/api/cards/${cardId}`);
  return data;
}

/**
 * The backend's UpdateCardRequest takes title/description/due_date
 * together — there's no partial-update endpoint. Omitting a field here
 * would send it as null and silently wipe it, so every caller must pass
 * all three, sourced from the card's current known values.
 */
export async function updateCard(cardId, { title, description, dueDate }) {
  const { data } = await client.put(`/api/cards/${cardId}`, {
    title,
    description,
    due_date: dueDate,
  });
  return data;
}

export async function deleteCard(cardId) {
  await client.delete(`/api/cards/${cardId}`);
}

export async function listCardLabels(cardId) {
  const { data } = await client.get(`/api/cards/${cardId}/labels`);
  return data;
}

export async function attachLabel(cardId, labelId) {
  await client.post(`/api/cards/${cardId}/labels/${labelId}`);
}

export async function detachLabel(cardId, labelId) {
  await client.delete(`/api/cards/${cardId}/labels/${labelId}`);
}

export async function listCardAssignees(cardId) {
  const { data } = await client.get(`/api/cards/${cardId}/assignees`);
  return data;
}

export async function assignUser(cardId, userId) {
  await client.post(`/api/cards/${cardId}/assignees/${userId}`);
}

export async function unassignUser(cardId, userId) {
  await client.delete(`/api/cards/${cardId}/assignees/${userId}`);
}

export async function listChecklistItems(cardId) {
  const { data } = await client.get(`/api/cards/${cardId}/checklist-items`);
  return data;
}

export async function createChecklistItem(cardId, { title }) {
  const { data } = await client.post(`/api/cards/${cardId}/checklist-items`, { title });
  return data;
}

export async function toggleChecklistItem(itemId) {
  const { data } = await client.patch(`/api/checklist-items/${itemId}/toggle`);
  return data;
}

export async function deleteChecklistItem(itemId) {
  await client.delete(`/api/checklist-items/${itemId}`);
}

export async function listComments(cardId) {
  const { data } = await client.get(`/api/cards/${cardId}/comments`);
  return data;
}

export async function createComment(cardId, { body }) {
  const { data } = await client.post(`/api/cards/${cardId}/comments`, { body });
  return data;
}

export async function deleteComment(commentId) {
  await client.delete(`/api/comments/${commentId}`);
}
