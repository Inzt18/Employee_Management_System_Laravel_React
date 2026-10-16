import { Link, useOutletContext } from 'react-router-dom'
import { adminApi } from '../../api/services'
import PanelCard from '../../components/PanelCard'
import Spinner from '../../components/Spinner'
import StatCard from '../../components/StatCard'
import { useAuth } from '../../context/AuthContext'
import useApi from '../../hooks/useApi'

const PENDING_TYPES = [
  { key: 'leave', label: 'Leave', icon: 'bi-airplane' },
  { key: 'resignation', label: 'Resignation', icon: 'bi-door-open' },
  { key: 'subject', label: 'Subject', icon: 'bi-journal-plus' },
  { key: 'task', label: 'Task', icon: 'bi-list-check' },
]

export default function AdminDashboardPage() {
  const { user } = useAuth()
  const { dataVersion } = useOutletContext()
  const { data: stats, loading } = useApi(adminApi.dashboard, [dataVersion])

  if (loading && !stats) return <Spinner fullPage />

  const departments = Object.entries(stats?.departments ?? {})
  const largestDepartment = Math.max(1, ...departments.map(([, total]) => total))

  return (
    <div className="container-xl dash-wrap">
      <div className="profile-card">
        <div className="profile-avatar">
          <i className="bi bi-shield-lock" />
        </div>
        <div>
          <div className="profile-kicker">Administrator</div>
          <h2>Good day, {user.first_name}!</h2>
          <div className="profile-meta">
            <span>Here is what is happening across the organization.</span>
          </div>
        </div>
      </div>

      <div className="row g-3 mb-4">
        <div className="col-6 col-lg-3"><StatCard icon="bi-people" color="blue" value={stats.employees} label="Employees" /></div>
        <div className="col-6 col-lg-3"><StatCard icon="bi-person-check" color="green" value={stats.active_employees} label="Active Employees" /></div>
        <div className="col-6 col-lg-3"><StatCard icon="bi-journal-bookmark" color="amber" value={stats.subjects} label="Subjects" /></div>
        <div className="col-6 col-lg-3"><StatCard icon="bi-inbox" color="rose" value={stats.pending.total} label="Pending Requests" /></div>
      </div>

      <div className="row g-3">
        <div className="col-lg-6">
          <PanelCard
            title="Pending by type"
            actions={
              <Link to="/admin/requests" className="btn btn-primary btn-sm">
                Review requests
              </Link>
            }
          >
            <div className="pending-grid">
              {PENDING_TYPES.map((type) => (
                <Link key={type.key} to={`/admin/requests?type=${type.key}`} className="pending-tile">
                  <i className={`bi ${type.icon}`} />
                  <span className="pending-count">{stats.pending[type.key]}</span>
                  <span className="pending-label">{type.label}</span>
                </Link>
              ))}
            </div>
          </PanelCard>
        </div>

        <div className="col-lg-6">
          <PanelCard title="Employees per department">
            {departments.length === 0 && <p className="text-muted mb-0">No employees yet.</p>}
            {departments.map(([department, total]) => (
              <div key={department} className="dept-row">
                <div className="d-flex justify-content-between small fw-semibold mb-1">
                  <span>{department}</span>
                  <span>{total}</span>
                </div>
                <div className="progress" role="progressbar" aria-valuenow={total} aria-valuemin={0} aria-valuemax={largestDepartment}>
                  <div className="progress-bar" style={{ width: `${(total / largestDepartment) * 100}%` }} />
                </div>
              </div>
            ))}
          </PanelCard>
        </div>
      </div>
    </div>
  )
}
