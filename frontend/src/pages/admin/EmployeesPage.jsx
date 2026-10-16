import { useState } from 'react'
import { adminApi } from '../../api/services'
import ConfirmDialog from '../../components/ConfirmDialog'
import DataTable from '../../components/DataTable'
import IconButton from '../../components/IconButton'
import PanelCard from '../../components/PanelCard'
import SearchInput from '../../components/SearchInput'
import StatusBadge from '../../components/StatusBadge'
import { useAuth } from '../../context/AuthContext'
import { useToast } from '../../context/ToastContext'
import useApi from '../../hooks/useApi'
import useDebounce from '../../hooks/useDebounce'
import { parseApiError } from '../../utils/errors'
import EmployeeDetailsModal from './components/EmployeeDetailsModal'
import EmployeeFormModal from './components/EmployeeFormModal'

export default function EmployeesPage() {
  const { user } = useAuth()
  const { notify } = useToast()
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const { data: employees, loading, error, reload } = useApi(() => adminApi.employees(debouncedSearch), [debouncedSearch], [])

  const [editing, setEditing] = useState(null)
  const [viewing, setViewing] = useState(null)
  const [deleting, setDeleting] = useState(null)

  const deleteEmployee = async () => {
    try {
      const response = await adminApi.deleteEmployee(deleting.id)
      notify(response.message)
      setDeleting(null)
      reload()
    } catch (err) {
      notify(parseApiError(err).message, 'danger')
    }
  }

  const columns = [
    {
      key: 'name',
      label: 'Full Name',
      render: (row) => (
        <div className="d-flex align-items-center gap-2">
          <span className="nav-avatar small-avatar">{row.first_name.charAt(0)}</span>
          <span className="fw-semibold">{row.sortable_name}</span>
          {row.id === user.id && <span className="badge text-bg-light">You</span>}
        </div>
      ),
    },
    { key: 'department', label: 'Department' },
    { key: 'email', label: 'Email' },
    { key: 'role', label: 'Role', render: (row) => <span className={`role-pill ${row.role.toLowerCase()}`}>{row.role}</span> },
    { key: 'employment_status', label: 'Status', render: (row) => <StatusBadge status={row.employment_status} /> },
    {
      key: 'actions',
      label: 'Action',
      className: 'text-end',
      render: (row) => (
        <div className="actions justify-content-end">
          <IconButton icon="bi-pencil" title="Edit" onClick={() => setEditing(row)} />
          {row.id !== user.id && <IconButton icon="bi-trash" title="Delete" variant="delete" onClick={() => setDeleting(row)} />}
        </div>
      ),
    },
  ]

  return (
    <div className="container-xl dash-wrap">
      <PanelCard
        title="Employees"
        subtitle="Click a row to see full details, subjects and tasks."
        actions={
          <>
            <SearchInput value={search} onChange={setSearch} placeholder="Search name, email, department..." />
            <button type="button" className="btn btn-primary btn-sm" onClick={() => setEditing({})}>
              <i className="bi bi-person-plus" /> Add Employee
            </button>
          </>
        }
      >
        <DataTable
          columns={columns}
          rows={employees}
          loading={loading}
          error={error}
          onRowClick={setViewing}
          emptyMessage="No employees found."
        />
      </PanelCard>

      <EmployeeFormModal
        employee={editing}
        onClose={() => setEditing(null)}
        onSaved={(message) => {
          notify(message)
          setEditing(null)
          reload()
        }}
      />

      <EmployeeDetailsModal
        employee={viewing}
        onClose={() => setViewing(null)}
        onEdit={(employee) => {
          setViewing(null)
          setEditing(employee)
        }}
      />

      <ConfirmDialog
        show={Boolean(deleting)}
        title="Delete employee"
        message={`Delete ${deleting?.full_name}? Their subjects, tasks and requests will also be removed.`}
        onConfirm={deleteEmployee}
        onClose={() => setDeleting(null)}
      />
    </div>
  )
}
