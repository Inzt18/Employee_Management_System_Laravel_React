import { Link, Outlet } from 'react-router-dom'
import UserMenu from './UserMenu'

export default function EmployeeLayout() {
  return (
    <>
      <nav className="navbar app-navbar sticky-top">
        <div className="container-xl">
          <Link className="navbar-brand" to="/dashboard">
            <span className="brand-mark">EM</span>
            <span>Employee Management</span>
          </Link>
          <UserMenu />
        </div>
      </nav>
      <main>
        <Outlet />
      </main>
    </>
  )
}
