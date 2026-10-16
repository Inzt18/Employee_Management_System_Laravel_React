import { useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { formatDate } from '../utils/format'

const CATEGORY_ICONS = {
  leave: 'bi-airplane',
  resignation: 'bi-door-open',
  subject: 'bi-journal-plus',
  task: 'bi-list-check',
}

export default function InboxDropdown({ requests, error, onSelect }) {
  const [open, setOpen] = useState(false)
  const wrapperRef = useRef(null)

  useEffect(() => {
    const closeOnOutsideClick = (event) => {
      if (!wrapperRef.current?.contains(event.target)) setOpen(false)
    }
    document.addEventListener('mousedown', closeOnOutsideClick)
    return () => document.removeEventListener('mousedown', closeOnOutsideClick)
  }, [])

  const select = (request) => {
    setOpen(false)
    onSelect(request)
  }

  return (
    <div className="inbox-wrapper" ref={wrapperRef}>
      <button type="button" className="inbox-button" aria-expanded={open} onClick={() => setOpen(!open)}>
        <i className="bi bi-bell" />
        <span className="d-none d-md-inline">Inbox</span>
        {requests.length > 0 && <span className="inbox-badge">{requests.length}</span>}
      </button>

      {open && (
        <div className="inbox-panel">
          <div className="inbox-title">Pending requests</div>
          <div className="inbox-list">
            {error && <div className="inbox-empty">Unable to load requests.</div>}
            {!error && requests.length === 0 && <div className="inbox-empty">No pending requests.</div>}
            {requests.slice(0, 8).map((request) => (
              <button
                key={`${request.request_type}-${request.id}`}
                type="button"
                className="inbox-item"
                onClick={() => select(request)}
              >
                <i className={`bi ${CATEGORY_ICONS[request.request_type]} inbox-item-icon`} />
                <span>
                  <span className="inbox-item-label">
                    {request.request_category} · {request.employee?.name}
                  </span>
                  <span className="inbox-item-title">{request.summary}</span>
                  <span className="inbox-item-meta">{formatDate(request.created_at)}</span>
                </span>
              </button>
            ))}
          </div>
          <Link to="/admin/requests" className="inbox-footer" onClick={() => setOpen(false)}>
            View all requests <i className="bi bi-arrow-right" />
          </Link>
        </div>
      )}
    </div>
  )
}
