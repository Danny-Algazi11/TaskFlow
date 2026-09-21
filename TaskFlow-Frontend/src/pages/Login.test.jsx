import { describe, expect, it, vi, beforeEach } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { MemoryRouter } from 'react-router-dom';
import Login from './Login';
import { useAuth } from '../context/AuthContext';

vi.mock('../context/AuthContext', () => ({
  useAuth: vi.fn(),
}));

describe('Login', () => {
  const login = vi.fn();

  beforeEach(() => {
    login.mockReset();
    useAuth.mockReturnValue({ login });
  });

  function renderLogin() {
    return render(
      <MemoryRouter>
        <Login />
      </MemoryRouter>
    );
  }

  it('submits the typed email and password', async () => {
    login.mockResolvedValueOnce({ id: 1 });
    const user = userEvent.setup();
    renderLogin();

    await user.type(screen.getByLabelText('Email address'), 'test@example.com');
    await user.type(screen.getByLabelText('Password'), 'password');
    await user.click(screen.getByRole('button', { name: /sign in/i }));

    expect(login).toHaveBeenCalledWith({ email: 'test@example.com', password: 'password' });
  });

  it('shows "Invalid credentials." on a 401 response', async () => {
    login.mockRejectedValueOnce({ response: { status: 401 } });
    const user = userEvent.setup();
    renderLogin();

    await user.type(screen.getByLabelText('Email address'), 'test@example.com');
    await user.type(screen.getByLabelText('Password'), 'wrong');
    await user.click(screen.getByRole('button', { name: /sign in/i }));

    expect(await screen.findByText('Invalid credentials.')).toBeInTheDocument();
  });

  it('shows a generic error message on any other failure', async () => {
    login.mockRejectedValueOnce(new Error('network down'));
    const user = userEvent.setup();
    renderLogin();

    await user.type(screen.getByLabelText('Email address'), 'test@example.com');
    await user.type(screen.getByLabelText('Password'), 'password');
    await user.click(screen.getByRole('button', { name: /sign in/i }));

    expect(await screen.findByText('Something went wrong. Please try again.')).toBeInTheDocument();
  });

  it('does not call login when required fields are left empty', async () => {
    const user = userEvent.setup();
    renderLogin();

    await user.click(screen.getByRole('button', { name: /sign in/i }));

    // Native "required" validation blocks the submit handler from ever running.
    expect(login).not.toHaveBeenCalled();
  });
});
