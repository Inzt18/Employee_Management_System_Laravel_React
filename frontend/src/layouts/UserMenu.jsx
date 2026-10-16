import { useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { initials } from '../utils/format'

export default function UserMenu() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  const handleLogout = async () => {
    await logout()
    navigate('/login', { replace: true })
  }

  return (
    <div className="d-flex align-items-center gap-3">
      <div className="nav-user">
        {user.avatar_url ? (
          <img src={user.avatar_url} alt="" className="nav-avatar" referrerPolicy="no-referrer" />
        ) : (
          <span className="nav-avatar">{initials(user.first_name)}</span>
        )}
        <span className="d-none d-sm-inline">{user.first_name}</span>
      </div>
      <button type="button" className="btn btn-logout" onClick={handleLogout}>
        <i className="bi bi-box-arrow-right" /> Logout
      </button>
    </div>
  )
}
