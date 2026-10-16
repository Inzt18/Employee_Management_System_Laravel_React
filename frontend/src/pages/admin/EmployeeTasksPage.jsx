import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { adminApi } from '../../api/services'
import ConfirmDialog from '../../components/ConfirmDialog'
import DataTable from '../../components/DataTable'
import IconButton from '../../components/IconButton'
import Modal from '../../components/Modal'
import PanelCard from '../../components/PanelCard'
import SearchInput from '../../components/SearchInput'
import StatusBadge from '../../components/StatusBadge'
import { FormAlert, SelectField, TextAreaField, TextField } from '../../components/form/FormFields'
import { useOptions } from '../../context/OptionsContext'
import { useToast } from '../../context/ToastContext'
import useApi from '../../hooks/useApi'
import useDebounce from '../../hooks/useDebounce'
import useForm from '../../hooks/useForm'
import { parseApiError } from '../../utils/errors'
import { formatDate } from '../../utils/format'
import EmployeePageHeader from './components/EmployeePageHeader'

const EMPTY_TASK = { task_title: '', task_description: '', due_date: '', status: 'Pending' }

function TaskFormModal({ employeeId, task, onClose, onSaved }) {
  const { task_statuses: statuses } = useOptions()
  const { values, errors, message, submitting, handleChange, setValues, submit, reset } = useForm(EMPTY_TASK)
  const [templates, setTemplates] = useState([])
  const isEdit = Boolean(task?.id)

  useEffect(() => {
    if (!task) return
    reset(
      isEdit
        ? { task_title: task.task_title, task_description: task.task_description ?? '', due_date: task.due_date ?? '', status: task.status }
        : EMPTY_TASK,
    )
    if (!isEdit) {
      adminApi.taskTemplates().then(setTemplates).catch(() => setTemplates([]))
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [task])

  const applyTemplate = (event) => {
    const template = templates[event.target.value]
    if (template) {
      setValues({ ...values, task_title: template.task_title, task_description: template.task_description ?? '' })
    }
  }

  const handleSubmit = async (event) => {
    event.preventDefault()
    const response = await submit((data) => (isEdit ? adminApi.updateTask(task.id, data) : adminApi.createTask(employeeId, data)))
    if (response) onSaved(response.message)
  }

  const field = (name) => ({ name, value: values[name], onChange: handleChange, error: errors[name] })

  return (
    <Modal
      show={Boolean(task)}
      title={isEdit ? 'Edit Task' : 'Add Task'}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn btn-ghost" onClick={onClose}>
            Cancel
          </button>
          <button type="submit" form="task-form" className="btn btn-primary" disabled={submitting}>
            {submitting ? 'Saving...' : 'Save'}
          </button>
        </>
      }
    >
      <FormAlert message={message} />
      <form id="task-form" onSubmit={handleSubmit} noValidate>
        {!isEdit && templates.length > 0 && (
          <SelectField
            label="Start from an existing task (optional)"
            name="template"
            defaultValue=""
            onChange={applyTemplate}
            options={templates.map((template, index) => ({ value: String(index), label: template.task_title }))}
            placeholder="-- Select to prefill --"
            hint="Pick a task used before to prefill the title and description."
          />
        )}
        <TextField label="Title" required {...field('task_title')} />
        <TextAreaField label="Description" rows={3} {...field('task_description')} />
        <div className="row">
          <TextField label="Due Date" type="date" className="col-md-6 mb-3" {...field('due_date')} />
          <SelectField label="Status" className="col-md-6 mb-3" options={statuses} required {...field('status')} />
        </div>
      </form>
    </Modal>
  )
}

export default function EmployeeTasksPage() {
  const { employeeId } = useParams()
  const { notify } = useToast()
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const { data: employee } = useApi(() => adminApi.employee(employeeId), [employeeId])
  const { data: tasks, loading, error, reload } = useApi(
    () => adminApi.employeeTasks(employeeId, debouncedSearch),
    [employeeId, debouncedSearch],
    [],
  )
  const [editing, setEditing] = useState(null)
  const [deleting, setDeleting] = useState(null)

  const deleteTask = async () => {
    try {
      const response = await adminApi.deleteTask(deleting.id)
      notify(response.message)
      setDeleting(null)
      reload()
    } catch (err) {
      notify(parseApiError(err).message, 'danger')
    }
  }

  const columns = [
    { key: 'task_title', label: 'Title', render: (row) => <span className="fw-semibold">{row.task_title}</span> },
    { key: 'task_description', label: 'Description', render: (row) => row.task_description || <span className="text-muted">—</span> },
    { key: 'due_date', label: 'Due Date', render: (row) => formatDate(row.due_date) },
    { key: 'status', label: 'Status', render: (row) => <StatusBadge status={row.status} /> },
    {
      key: 'actions',
      label: 'Action',
      className: 'text-end',
      render: (row) => (
        <div className="actions justify-content-end">
          <IconButton icon="bi-pencil" title="Edit" onClick={() => setEditing(row)} />
          <IconButton icon="bi-trash" title="Delete" variant="delete" onClick={() => setDeleting(row)} />
        </div>
      ),
    },
  ]

  return (
    <div className="container-xl dash-wrap">
      <EmployeePageHeader employee={employee} section="Tasks" />

      <PanelCard
        title="Assigned Tasks"
        actions={
          <>
            <SearchInput value={search} onChange={setSearch} placeholder="Search title or description..." />
            <button type="button" className="btn btn-primary btn-sm" onClick={() => setEditing({})}>
              <i className="bi bi-plus-circle" /> Add Task
            </button>
          </>
        }
      >
        <DataTable columns={columns} rows={tasks} loading={loading} error={error} emptyMessage="No tasks assigned yet." />
      </PanelCard>

      <TaskFormModal
        employeeId={employeeId}
        task={editing}
        onClose={() => setEditing(null)}
        onSaved={(message) => {
          notify(message)
          setEditing(null)
          reload()
        }}
      />

      <ConfirmDialog
        show={Boolean(deleting)}
        title="Delete task"
        message={`Delete the task "${deleting?.task_title}"?`}
        onConfirm={deleteTask}
        onClose={() => setDeleting(null)}
      />
    </div>
  )
}
