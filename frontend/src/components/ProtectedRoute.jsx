import { Navigate, Outlet, useLocation } from 'react-router-dom'
import { homePathFor, useAuth } from '../context/AuthContext'
import Spinner from './Spinner'

/**
 * Only renders child routes for a signed-in user with the given role.
 */
export default function ProtectedRoute({ role }) {
  const { user, loading } = useAuth()
  const location = useLocation()

  if (loading) {
    return <Spinner fullPage />
  }

  if (!user) {
    return <Navigate to="/login" replace state={{ from: location }} />
  }

  if (role && user.role !== role) {
    return <Navigate to={homePathFor(user)} replace />
  }

  return <Outlet />
}

/**
 * Sends already signed-in users away from the login and register pages.
 */
export function GuestRoute() {
  const { user, loading } = useAuth()

  if (loading) {
    return <Spinner fullPage />
  }

  return user ? <Navigate to={homePathFor(user)} replace /> : <Outlet />
}
