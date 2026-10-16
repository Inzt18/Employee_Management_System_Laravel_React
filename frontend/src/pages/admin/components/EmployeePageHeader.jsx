import { Link } from 'react-router-dom'
import StatusBadge from '../../../components/StatusBadge'
import { initials } from '../../../utils/format'

export default function EmployeePageHeader({ employee, section }) {
  return (
    <>
      <Link className="back-link" to="/admin/employees">
        <i className="bi bi-arrow-left" /> Back to employees
      </Link>
      <div className="profile-card">
        <div className="profile-avatar">{initials(employee?.first_name)}</div>
        <div>
          <div className="profile-kicker">{section}</div>
          <h2>{employee?.full_name ?? 'Loading...'}</h2>
          {employee && (
            <div className="profile-meta">
              <span><i className="bi bi-building" /> {employee.department}</span>
              <span><i className="bi bi-envelope" /> {employee.email}</span>
              <StatusBadge status={employee.employment_status} />
            </div>
          )}
        </div>
      </div>
    </>
  )
}
