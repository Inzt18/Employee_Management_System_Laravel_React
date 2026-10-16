import { useMemo } from 'react'
import { useOutletContext, useSearchParams } from 'react-router-dom'
import { adminApi } from '../../api/services'
import DataTable from '../../components/DataTable'
import PanelCard from '../../components/PanelCard'
import StatusBadge from '../../components/StatusBadge'
import useApi from '../../hooks/useApi'
import { formatDate } from '../../utils/format'

const STATUS_TABS = ['Pending', 'Approved', 'Rejected', 'All']

const TYPE_FILTERS = [
  { value: '', label: 'All types' },
  { value: 'leave', label: 'Leave' },
  { value: 'resignation', label: 'Resignation' },
  { value: 'subject', label: 'Subject' },
  { value: 'task', label: 'Task' },
]

const COLUMNS = [
  { key: 'category', label: 'Type', render: (row) => <span className={`type-pill ${row.request_type}`}>{row.request_category}</span> },
  {
    key: 'employee',
    label: 'Employee',
    render: (row) => (
      <>
        <div className="fw-semibold">{row.employee?.name}</div>
        <div className="small text-muted">{row.employee?.department}</div>
      </>
    ),
  },
  { key: 'summary', label: 'Request', render: (row) => row.summary },
  { key: 'created_at', label: 'Submitted', render: (row) => formatDate(row.created_at) },
  { key: 'attachments', label: 'Files', render: (row) => (row.attachments?.length ? <><i className="bi bi-paperclip" /> {row.attachments.length}</> : '—') },
  { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
]

export default function RequestsPage() {
  const { dataVersion, openReview } = useOutletContext()
  const [params, setParams] = useSearchParams()
  const status = params.get('status') ?? 'Pending'
  const type = params.get('type') ?? ''

  const { data: requests, loading, error } = useApi(
    () => adminApi.requests(status === 'All' ? undefined : status),
    [status, dataVersion],
    [],
  )

  const rows = useMemo(
    () =>
      requests
        .filter((request) => !type || request.request_type === type)
        .map((request) => ({ ...request, key: `${request.request_type}-${request.id}` })),
    [requests, type],
  )

  const updateFilter = (name, value) => {
    const next = new URLSearchParams(params)
    if (value) next.set(name, value)
    else next.delete(name)
    setParams(next, { replace: true })
  }

  return (
    <div className="container-xl dash-wrap">
      <PanelCard
        title="Employee Requests"
        subtitle="Click a request to view details and attachments, then approve or reject it."
        actions={
          <select className="form-select form-select-sm w-auto" value={type} onChange={(event) => updateFilter('type', event.target.value)}>
            {TYPE_FILTERS.map((filter) => (
              <option key={filter.value} value={filter.value}>
                {filter.label}
              </option>
            ))}
          </select>
        }
      >
        <ul className="nav nav-pills status-tabs mb-3">
          {STATUS_TABS.map((tab) => (
            <li key={tab} className="nav-item">
              <button type="button" className={`nav-link ${status === tab ? 'active' : ''}`} onClick={() => updateFilter('status', tab)}>
                {tab}
              </button>
            </li>
          ))}
        </ul>
        <DataTable
          columns={COLUMNS}
          rows={rows}
          rowKey="key"
          loading={loading}
          error={error}
          onRowClick={openReview}
          emptyMessage={`No ${status === 'All' ? '' : status.toLowerCase()} requests.`}
        />
      </PanelCard>
    </div>
  )
}
