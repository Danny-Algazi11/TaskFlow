import client from './client';

export async function listWorkspaces() {
  const { data } = await client.get('/api/workspaces');
  return data;
}

export async function createWorkspace({ name }) {
  const { data } = await client.post('/api/workspaces', { name });
  return data;
}

export async function getWorkspace(workspaceId) {
  const { data } = await client.get(`/api/workspaces/${workspaceId}`);
  return data;
}

export async function updateWorkspace(workspaceId, { name }) {
  const { data } = await client.patch(`/api/workspaces/${workspaceId}`, { name });
  return data;
}

export async function deleteWorkspace(workspaceId) {
  await client.delete(`/api/workspaces/${workspaceId}`);
}

export async function listMembers(workspaceId) {
  const { data } = await client.get(`/api/workspaces/${workspaceId}/members`);
  return data;
}

export async function listBoards(workspaceId) {
  const { data } = await client.get(`/api/workspaces/${workspaceId}/boards`);
  return data;
}

export async function createBoard(workspaceId, { name }) {
  const { data } = await client.post(`/api/workspaces/${workspaceId}/boards`, { name });
  return data;
}

export async function listWorkspaceLabels(workspaceId) {
  const { data } = await client.get(`/api/workspaces/${workspaceId}/labels`);
  return data;
}

export async function createLabel(workspaceId, { name, color }) {
  const { data } = await client.post(`/api/workspaces/${workspaceId}/labels`, { name, color });
  return data;
}
