import { useCallback, useEffect, useState } from 'react'
import { Link, NavLink, Outlet } from 'react-router-dom'
import { adminApi } from '../api/services'
import RequestReviewModal from '../pages/admin/components/RequestReviewModal'
import InboxDropdown from './InboxDropdown'
import UserMenu from './UserMenu'

const INBOX_REFRESH_MS = 30_000

const NAV_ITEMS = [
  { to: '/admin', label: 'Dashboard', icon: 'bi-speedometer2', end: true },
  { to: '/admin/employees', label: 'Employees', icon: 'bi-people' },
  { to: '/admin/subjects', label: 'Subjects', icon: 'bi-journal-bookmark' },
  { to: '/admin/requests', label: 'Requests', icon: 'bi-inbox' },
]

export default function AdminLayout() {
  const [pending, setPending] = useState([])
  const [inboxError, setInboxError] = useState(false)
  const [reviewing, setReviewing] = useState(null)
  const [dataVersion, setDataVersion] = useState(0)

  const loadInbox = useCallback(async () => {
    try {
      setPending(await adminApi.requests('Pending'))
      setInboxError(false)
    } catch {
      setInboxError(true)
    }
  }, [])

  /**
   * Called after anything changes requests, so the inbox and open pages stay in sync.
   */
  const refreshRequests = useCallback(() => {
    loadInbox()
    setDataVersion((version) => version + 1)
  }, [loadInbox])

  useEffect(() => {
    loadInbox()
    const timer = setInterval(loadInbox, INBOX_REFRESH_MS)
    return () => clearInterval(timer)
  }, [loadInbox])

  return (
    <>
      <nav className="navbar navbar-expand-lg app-navbar admin-navbar sticky-top">
        <div className="container-xl gap-3">
          <Link className="navbar-brand" to="/admin">
            <span className="brand-mark">EM</span>
            <span>
              Employee Management <small className="admin-tag">Admin</small>
            </span>
          </Link>
          <div className="admin-nav-links">
            {NAV_ITEMS.map((item) => (
              <NavLink key={item.to} to={item.to} end={item.end} className="admin-nav-link">
                <i className={`bi ${item.icon}`} /> <span>{item.label}</span>
              </NavLink>
            ))}
          </div>
          <div className="d-flex align-items-center gap-3 ms-lg-auto">
            <InboxDropdown requests={pending} error={inboxError} onSelect={setReviewing} />
            <UserMenu />
          </div>
        </div>
      </nav>

      <main>
        <Outlet context={{ refreshRequests, dataVersion, openReview: setReviewing }} />
      </main>

      <RequestReviewModal
        key={reviewing ? `${reviewing.request_type}-${reviewing.id}` : 'closed'}
        request={reviewing}
        onClose={() => setReviewing(null)}
        onReviewed={() => {
          setReviewing(null)
          refreshRequests()
        }}
      />
    </>
  )
}
