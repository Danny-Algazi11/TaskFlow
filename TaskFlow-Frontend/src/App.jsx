import { Navigate, Route, Routes, useLocation } from 'react-router-dom';
import { useAuth } from './context/AuthContext';
import Login from './pages/Login';
import Register from './pages/Register';
import Dashboard from './pages/Dashboard';
import Board from './pages/Board';
import Members from './pages/Members';
import Settings from './pages/Settings';
import AcceptInvitation from './pages/AcceptInvitation';

function ProtectedRoute({ children }) {
  const { user, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return null;
  }

  // Carries the page someone was trying to reach (e.g. an invite accept
  // link) through login/register so they land back on it afterward,
  // instead of always bouncing to the dashboard.
  return user ? children : <Navigate to="/login" replace state={{ from: location }} />;
}

function GuestOnlyRoute({ children }) {
  const { user, isLoading } = useAuth();
  const location = useLocation();

  if (isLoading) {
    return null;
  }

  // The single place that decides where a freshly-authenticated visitor
  // lands: Login/Register don't navigate themselves after a successful
  // call, since that raced with this component's own redirect (both fire
  // off the same auth-context update, and this one was winning) — letting
  // this be the only source of the redirect removes the race entirely.
  if (user) {
    const from = location.state?.from
      ? `${location.state.from.pathname}${location.state.from.search || ''}`
      : '/';
    return <Navigate to={from} replace />;
  }

  return children;
}

export default function App() {
  return (
    <Routes>
      <Route
        path="/login"
        element={
          <GuestOnlyRoute>
            <Login />
          </GuestOnlyRoute>
        }
      />
      <Route
        path="/register"
        element={
          <GuestOnlyRoute>
            <Register />
          </GuestOnlyRoute>
        }
      />
      <Route
        path="/"
        element={
          <ProtectedRoute>
            <Dashboard />
          </ProtectedRoute>
        }
      />
      <Route
        path="/boards/:boardId"
        element={
          <ProtectedRoute>
            <Board />
          </ProtectedRoute>
        }
      />
      <Route
        path="/members"
        element={
          <ProtectedRoute>
            <Members />
          </ProtectedRoute>
        }
      />
      <Route
        path="/settings"
        element={
          <ProtectedRoute>
            <Settings />
          </ProtectedRoute>
        }
      />
      <Route
        path="/invitations/:token/accept"
        element={
          <ProtectedRoute>
            <AcceptInvitation />
          </ProtectedRoute>
        }
      />
    </Routes>
  );
}
