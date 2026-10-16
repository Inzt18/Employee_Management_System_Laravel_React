import { useNavigate } from 'react-router-dom'
import Modal from '../../../components/Modal'
import StatusBadge from '../../../components/StatusBadge'
import { formatDate } from '../../../utils/format'

export default function EmployeeDetailsModal({ employee, onClose, onEdit }) {
  const navigate = useNavigate()

  if (!employee) return null

  const details = [
    ['Full Name', employee.full_name],
    ['Birth Date', formatDate(employee.birth_date)],
    ['Gender', employee.gender],
    ['Email', employee.email],
    ['Department', employee.department],
    ['Address', employee.address],
    ['Username', employee.username],
    ['Role', employee.role],
    ['Status', <StatusBadge key="status" status={employee.employment_status} />],
    ['Google Sign-in', employee.has_google ? 'Linked' : 'Not linked'],
    ['Facebook Sign-in', employee.has_facebook ? 'Linked' : 'Not linked'],
  ]

  return (
    <Modal
      show
      title="Employee Details"
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn btn-soft" onClick={() => navigate(`/admin/employees/${employee.id}/subjects`)}>
            <i className="bi bi-journal-bookmark" /> Subjects
          </button>
          <button type="button" className="btn btn-soft" onClick={() => navigate(`/admin/employees/${employee.id}/tasks`)}>
            <i className="bi bi-list-check" /> Tasks
          </button>
          <button type="button" className="btn btn-soft" onClick={() => onEdit(employee)}>
            <i className="bi bi-pencil" /> Edit
          </button>
          <button type="button" className="btn btn-ghost ms-auto" onClick={onClose}>
            Close
          </button>
        </>
      }
    >
      <dl className="detail-list">
        {details.map(([label, value]) => (
          <div key={label} className="detail-row">
            <dt>{label}</dt>
            <dd>{value || '—'}</dd>
          </div>
        ))}
      </dl>
    </Modal>
  )
}
