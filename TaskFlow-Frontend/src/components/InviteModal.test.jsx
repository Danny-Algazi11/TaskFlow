import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import InviteModal from './InviteModal';
import * as invitationsApi from '../api/invitations';

vi.mock('../api/invitations');

const workspace = { id: 1, name: 'Product Team' };

describe('InviteModal', () => {
  beforeEach(() => {
    vi.resetAllMocks();
    invitationsApi.listInvitations.mockResolvedValue([]);
  });

  it('fetches and renders pending invitations for the workspace on mount', async () => {
    invitationsApi.listInvitations.mockResolvedValue([
      { id: 1, email: 'sam@example.com', role: 'member', created_at: new Date().toISOString() },
    ]);

    render(<InviteModal workspace={workspace} onClose={vi.fn()} />);

    expect(await screen.findByText('sam@example.com')).toBeInTheDocument();
    expect(invitationsApi.listInvitations).toHaveBeenCalledWith(1);
  });

  it('sends an invite with the entered email and default "member" role', async () => {
    invitationsApi.createInvitation.mockResolvedValue({
      id: 2,
      email: 'new@example.com',
      role: 'member',
      token: 'abc123',
      created_at: new Date().toISOString(),
    });
    const user = userEvent.setup();
    render(<InviteModal workspace={workspace} onClose={vi.fn()} />);

    await user.type(screen.getByLabelText('Email address'), 'new@example.com');
    await user.click(screen.getByRole('button', { name: 'Send invite' }));

    await waitFor(() =>
      expect(invitationsApi.createInvitation).toHaveBeenCalledWith(1, { email: 'new@example.com', role: 'member' })
    );
  });

  it('sends the "admin" role once selected', async () => {
    invitationsApi.createInvitation.mockResolvedValue({
      id: 2,
      email: 'new@example.com',
      role: 'admin',
      token: 'abc123',
      created_at: new Date().toISOString(),
    });
    const user = userEvent.setup();
    render(<InviteModal workspace={workspace} onClose={vi.fn()} />);

    await user.click(screen.getByText('Admin'));
    await user.type(screen.getByLabelText('Email address'), 'new@example.com');
    await user.click(screen.getByRole('button', { name: 'Send invite' }));

    await waitFor(() =>
      expect(invitationsApi.createInvitation).toHaveBeenCalledWith(1, { email: 'new@example.com', role: 'admin' })
    );
  });

  it('shows the shareable accept link after a successful invite', async () => {
    invitationsApi.createInvitation.mockResolvedValue({
      id: 2,
      email: 'new@example.com',
      role: 'member',
      token: 'the-token',
      created_at: new Date().toISOString(),
    });
    const user = userEvent.setup();
    render(<InviteModal workspace={workspace} onClose={vi.fn()} />);

    await user.type(screen.getByLabelText('Email address'), 'new@example.com');
    await user.click(screen.getByRole('button', { name: 'Send invite' }));

    expect(await screen.findByText(/\/invitations\/the-token\/accept/)).toBeInTheDocument();
  });

  it('shows the server error message when the invite is rejected', async () => {
    invitationsApi.createInvitation.mockRejectedValue({
      response: { data: { message: 'This person is already a member of the workspace.' } },
    });
    const user = userEvent.setup();
    render(<InviteModal workspace={workspace} onClose={vi.fn()} />);

    await user.type(screen.getByLabelText('Email address'), 'existing@example.com');
    await user.click(screen.getByRole('button', { name: 'Send invite' }));

    expect(await screen.findByText('This person is already a member of the workspace.')).toBeInTheDocument();
  });

  it('revokes a pending invitation and removes it from the list', async () => {
    invitationsApi.listInvitations.mockResolvedValue([
      { id: 5, email: 'sam@example.com', role: 'member', created_at: new Date().toISOString() },
    ]);
    invitationsApi.revokeInvitation.mockResolvedValue();
    const user = userEvent.setup();
    render(<InviteModal workspace={workspace} onClose={vi.fn()} />);

    await screen.findByText('sam@example.com');
    await user.click(screen.getByRole('button', { name: 'Revoke' }));

    expect(invitationsApi.revokeInvitation).toHaveBeenCalledWith(5);
    await waitFor(() => expect(screen.queryByText('sam@example.com')).not.toBeInTheDocument());
  });

  it('copies the accept link to the clipboard', async () => {
    invitationsApi.createInvitation.mockResolvedValue({
      id: 2,
      email: 'new@example.com',
      role: 'member',
      token: 'the-token',
      created_at: new Date().toISOString(),
    });
    const user = userEvent.setup();
    // Must come after userEvent.setup(), which installs its own clipboard
    // stub on navigator.clipboard — defining this first just gets clobbered.
    const writeText = vi.fn().mockResolvedValue();
    Object.defineProperty(navigator, 'clipboard', { value: { writeText }, configurable: true });

    render(<InviteModal workspace={workspace} onClose={vi.fn()} />);

    await user.type(screen.getByLabelText('Email address'), 'new@example.com');
    await user.click(screen.getByRole('button', { name: 'Send invite' }));
    await screen.findByText(/\/invitations\/the-token\/accept/);
    await user.click(screen.getByRole('button', { name: 'Copy' }));

    expect(writeText).toHaveBeenCalledWith(expect.stringContaining('/invitations/the-token/accept'));
    expect(await screen.findByText('Copied!')).toBeInTheDocument();
  });

  it('calls onClose when the scrim is clicked', async () => {
    const onClose = vi.fn();
    const user = userEvent.setup();
    const { container } = render(<InviteModal workspace={workspace} onClose={onClose} />);

    await user.click(container.querySelector('.scrim'));

    expect(onClose).toHaveBeenCalled();
  });
});
