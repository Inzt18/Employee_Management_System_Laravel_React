import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { employeeApi } from '../../api/services'
import AttachmentLinks from '../../components/AttachmentLinks'
import DataTable from '../../components/DataTable'
import PanelCard from '../../components/PanelCard'
import SearchInput from '../../components/SearchInput'
import StatCard from '../../components/StatCard'
import StatusBadge from '../../components/StatusBadge'
import useApi from '../../hooks/useApi'
import useDebounce from '../../hooks/useDebounce'
import { formatDate, formatTime, initials } from '../../utils/format'

/**
 * A searchable panel backed by one employee endpoint.
 */
function SearchablePanel({ title, fetcher, columns, emptyMessage, actionLabel, actionTo, placeholder }) {
  const navigate = useNavigate()
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const { data, loading, error } = useApi(() => fetcher(debouncedSearch), [debouncedSearch], [])

  return (
    <PanelCard
      title={title}
      actions={
        <>
          {placeholder && <SearchInput value={search} onChange={setSearch} placeholder={placeholder} />}
          {actionTo && (
            <button type="button" className="btn btn-primary btn-sm" onClick={() => navigate(actionTo)}>
              <i className="bi bi-plus-circle" /> {actionLabel}
            </button>
          )}
        </>
      }
    >
      <DataTable columns={columns} rows={data} loading={loading} error={error} emptyMessage={emptyMessage} />
    </PanelCard>
  )
}

const reviewNote = (row) =>
  row.admin_comment ? <span className="small text-muted pre-wrap">{row.admin_comment}</span> : <span className="text-muted">—</span>

const SCHEDULE_COLUMNS = [
  { key: 'code', label: 'Subject Code', render: (row) => <strong>{row.subject.subject_code}</strong> },
  { key: 'name', label: 'Subject Name', render: (row) => row.subject.subject_name },
  { key: 'units', label: 'Units', render: (row) => row.subject.units },
  { key: 'day', label: 'Day', render: (row) => row.schedule?.day ?? <span className="text-muted">TBA</span> },
  {
    key: 'time',
    label: 'Time',
    render: (row) => (row.schedule ? `${formatTime(row.schedule.start_time)} – ${formatTime(row.schedule.end_time)}` : '—'),
  },
  { key: 'room', label: 'Room', render: (row) => row.schedule?.room ?? '—' },
]

const TASK_COLUMNS = [
  {
    key: 'task_title',
    label: 'Task',
    render: (row) => (
      <>
        <div className="fw-semibold">{row.task_title}</div>
        {row.task_description && <div className="small text-muted">{row.task_description}</div>}
      </>
    ),
  },
  { key: 'due_date', label: 'Due Date', render: (row) => formatDate(row.due_date) },
  { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
]

const LEAVE_COLUMNS = [
  { key: 'type', label: 'Leave Type', render: (row) => row.fields.leave_type },
  { key: 'start', label: 'Start Date', render: (row) => formatDate(row.fields.start_date) },
  { key: 'end', label: 'End Date', render: (row) => formatDate(row.fields.end_date) },
  { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
  { key: 'comment', label: 'Admin Comment', render: reviewNote },
  { key: 'attachments', label: 'Attachments', render: (row) => <AttachmentLinks attachments={row.attachments} /> },
]

const RESIGNATION_COLUMNS = [
  { key: 'date', label: 'Effective Date', render: (row) => formatDate(row.fields.resignation_date) },
  { key: 'reason', label: 'Reason', render: (row) => <span className="pre-wrap">{row.fields.reason}</span> },
  { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
  { key: 'comment', label: 'Admin Comment', render: reviewNote },
  { key: 'attachments', label: 'Attachments', render: (row) => <AttachmentLinks attachments={row.attachments} /> },
]

const MY_REQUEST_COLUMNS = [
  { key: 'category', label: 'Type', render: (row) => row.request_category },
  { key: 'summary', label: 'Request', render: (row) => row.summary },
  { key: 'created_at', label: 'Submitted', render: (row) => formatDate(row.created_at) },
  { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
  { key: 'comment', label: 'Admin Comment', render: reviewNote },
]

async function loadSubjectAndTaskRequests() {
  const [subjects, tasks] = await Promise.all([employeeApi.subjectRequests(), employeeApi.taskRequests()])
  return [...subjects, ...tasks]
    .map((request) => ({ ...request, key: `${request.request_type}-${request.id}` }))
    .sort((a, b) => b.created_at.localeCompare(a.created_at))
}

export default function DashboardPage() {
  const { data: dashboard } = useApi(employeeApi.dashboard, [])
  const { data: myRequests, loading: myRequestsLoading, error: myRequestsError } = useApi(loadSubjectAndTaskRequests, [], [])

  const employee = dashboard?.employee
  const totals = dashboard?.totals ?? {}
  const pendingTotal = dashboard?.pending?.total ?? 0

  return (
    <div className="container-xl dash-wrap">
      {pendingTotal > 0 && (
        <div className="alert alert-pending">
          <i className="bi bi-hourglass-split" /> You have {pendingTotal} request(s) waiting for admin approval.
        </div>
      )}

      <div className="profile-card">
        {employee?.avatar_url ? (
          <img src={employee.avatar_url} alt="" className="profile-avatar" referrerPolicy="no-referrer" />
        ) : (
          <div className="profile-avatar">{initials(employee?.first_name)}</div>
        )}
        <div>
          <div className="profile-kicker">Employee dashboard</div>
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

      <div className="row g-3 mb-4">
        <div className="col-6 col-md-3"><StatCard icon="bi-calendar-week" color="green" value={totals.subjects} label="Subjects" /></div>
        <div className="col-6 col-md-3"><StatCard icon="bi-list-task" color="amber" value={totals.tasks} label="Tasks" /></div>
        <div className="col-6 col-md-3"><StatCard icon="bi-airplane" color="blue" value={totals.leave_requests} label="Leave Requests" /></div>
        <div className="col-6 col-md-3"><StatCard icon="bi-door-open" color="rose" value={totals.resignation_requests} label="Resignation" /></div>
      </div>

      <SearchablePanel
        title="Subjects & Schedule"
        fetcher={employeeApi.schedule}
        columns={SCHEDULE_COLUMNS}
        placeholder="Search subject, day or room..."
        emptyMessage="No subjects or schedule yet."
        actionLabel="Request Subject"
        actionTo="/requests/subject"
      />

      <SearchablePanel
        title="Current Tasks"
        fetcher={employeeApi.tasks}
        columns={TASK_COLUMNS}
        placeholder="Search task..."
        emptyMessage="No tasks assigned yet."
        actionLabel="Request Task"
        actionTo="/requests/task"
      />

      <PanelCard title="Subject & Task Requests" subtitle="Requests you sent to the admin and their result.">
        <DataTable
          columns={MY_REQUEST_COLUMNS}
          rows={myRequests}
          loading={myRequestsLoading}
          error={myRequestsError}
          rowKey="key"
          emptyMessage="You have not requested any subjects or tasks."
        />
      </PanelCard>

      <SearchablePanel
        title="Leave Requests"
        fetcher={employeeApi.leaveRequests}
        columns={LEAVE_COLUMNS}
        placeholder="Search leave..."
        emptyMessage="No leave requests yet."
        actionLabel="File Leave"
        actionTo="/requests/leave"
      />

      <SearchablePanel
        title="Resignation Requests"
        fetcher={employeeApi.resignationRequests}
        columns={RESIGNATION_COLUMNS}
        placeholder="Search resignation..."
        emptyMessage="No resignation requests yet."
        actionLabel="File Resignation"
        actionTo="/requests/resignation"
      />
    </div>
  )
}
