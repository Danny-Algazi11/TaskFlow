import client from './client';

export async function listInvitations(workspaceId) {
  const { data } = await client.get(`/api/workspaces/${workspaceId}/invitations`);
  return data;
}

export async function createInvitation(workspaceId, { email, role }) {
  const { data } = await client.post(`/api/workspaces/${workspaceId}/invitations`, { email, role });
  return data;
}

export async function revokeInvitation(invitationId) {
  await client.delete(`/api/invitations/${invitationId}`);
}

export async function acceptInvitation(token) {
  const { data } = await client.post(`/api/invitations/${token}/accept`);
  return data;
}
