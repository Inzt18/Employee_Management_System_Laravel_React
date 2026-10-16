import { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import { adminApi } from '../../api/services'
import ConfirmDialog from '../../components/ConfirmDialog'
import DataTable from '../../components/DataTable'
import IconButton from '../../components/IconButton'
import Modal from '../../components/Modal'
import PanelCard from '../../components/PanelCard'
import SearchInput from '../../components/SearchInput'
import { FormAlert, SelectField, TextField } from '../../components/form/FormFields'
import { useOptions } from '../../context/OptionsContext'
import { useToast } from '../../context/ToastContext'
import useApi from '../../hooks/useApi'
import useDebounce from '../../hooks/useDebounce'
import useForm from '../../hooks/useForm'
import { parseApiError } from '../../utils/errors'
import { formatTime } from '../../utils/format'
import EmployeePageHeader from './components/EmployeePageHeader'

const EMPTY_ASSIGNMENT = {
  subject_mode: 'existing',
  subject_id: '',
  new_subject_code: '',
  new_subject_name: '',
  new_units: '',
  day: '',
  start_time: '',
  end_time: '',
  room: '',
}

/**
 * Assign a subject (existing or new) with schedule, or edit the schedule of an existing assignment.
 */
function AssignmentModal({ employeeId, assignment, onClose, onSaved }) {
  const { days } = useOptions()
  const form = useForm(EMPTY_ASSIGNMENT)
  const { values, errors, message, submitting, handleChange, setField, submit, reset } = form
  const [available, setAvailable] = useState([])
  const isEdit = Boolean(assignment?.id)

  useEffect(() => {
    if (!assignment) return
    reset(isEdit ? { ...EMPTY_ASSIGNMENT, ...assignment.schedule } : EMPTY_ASSIGNMENT)
    if (!isEdit) {
      adminApi.availableSubjects(employeeId).then(setAvailable).catch(() => setAvailable([]))
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [assignment])

  const handleSubmit = async (event) => {
    event.preventDefault()
    const response = await submit(({ subject_mode: mode, subject_id, new_subject_code, new_subject_name, new_units, ...schedule }) => {
      if (isEdit) return adminApi.updateAssignment(assignment.id, schedule)
      const subject = mode === 'existing' ? { subject_id } : { new_subject_code, new_subject_name, new_units }
      return adminApi.assignSubject(employeeId, { ...subject, ...schedule })
    })
    if (response) onSaved(response.message)
  }

  const field = (name) => ({ name, value: values[name] ?? '', onChange: handleChange, error: errors[name] })
  const subjectError = errors.subject_id ?? errors.new_subject_code

  return (
    <Modal
      show={Boolean(assignment)}
      title={isEdit ? `Edit schedule · ${assignment.subject.subject_code}` : 'Assign Subject'}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn btn-ghost" onClick={onClose}>
            Cancel
          </button>
          <button type="submit" form="assignment-form" className="btn btn-primary" disabled={submitting}>
            {submitting ? 'Saving...' : isEdit ? 'Save Schedule' : 'Assign'}
          </button>
        </>
      }
    >
      <FormAlert message={message} />
      <form id="assignment-form" onSubmit={handleSubmit} noValidate>
        {!isEdit && (
          <div className="mb-3">
            <div className="btn-group w-100 mb-3" role="group" aria-label="Subject source">
              {[
                ['existing', 'Existing subject'],
                ['new', 'Create new subject'],
              ].map(([mode, label]) => (
                <button
                  key={mode}
                  type="button"
                  className={`btn ${values.subject_mode === mode ? 'btn-primary' : 'btn-outline-primary'}`}
                  onClick={() => setField('subject_mode', mode)}
                >
                  {label}
                </button>
              ))}
            </div>

            {values.subject_mode === 'existing' ? (
              <SelectField
                label="Subject"
                options={available.map((subject) => ({
                  value: subject.id,
                  label: `${subject.subject_code} — ${subject.subject_name} (${subject.units} units)`,
                }))}
                placeholder={available.length ? 'Select a subject' : 'No unassigned subjects left'}
                required
                {...field('subject_id')}
                error={subjectError}
              />
            ) : (
              <div className="row">
                <TextField label="Code" className="col-md-4 mb-3" required {...field('new_subject_code')} error={subjectError} />
                <TextField label="Name" className="col-md-5 mb-3" required {...field('new_subject_name')} />
                <TextField label="Units" type="number" min={1} max={10} className="col-md-3 mb-3" required {...field('new_units')} />
              </div>
            )}
          </div>
        )}

        <SelectField label="Day" options={days} placeholder="Select day" required {...field('day')} />
        <div className="row">
          <TextField label="Start Time" type="time" className="col-md-6 mb-3" required {...field('start_time')} />
          <TextField label="End Time" type="time" className="col-md-6 mb-3" required {...field('end_time')} />
        </div>
        <TextField label="Room" placeholder="e.g. Lab 301" required {...field('room')} />
      </form>
    </Modal>
  )
}

export default function EmployeeSubjectsPage() {
  const { employeeId } = useParams()
  const { notify } = useToast()
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const { data: employee } = useApi(() => adminApi.employee(employeeId), [employeeId])
  const { data: assignments, loading, error, reload } = useApi(
    () => adminApi.assignments(employeeId, debouncedSearch),
    [employeeId, debouncedSearch],
    [],
  )
  const [editing, setEditing] = useState(null)
  const [removing, setRemoving] = useState(null)

  const removeAssignment = async () => {
    try {
      const response = await adminApi.removeAssignment(removing.id)
      notify(response.message)
      setRemoving(null)
      reload()
    } catch (err) {
      notify(parseApiError(err).message, 'danger')
    }
  }

  const columns = [
    { key: 'code', label: 'Code', render: (row) => <strong>{row.subject.subject_code}</strong> },
    { key: 'name', label: 'Name', render: (row) => row.subject.subject_name },
    { key: 'units', label: 'Units', render: (row) => row.subject.units },
    { key: 'day', label: 'Day', render: (row) => row.schedule?.day ?? <span className="text-muted">Not scheduled</span> },
    {
      key: 'time',
      label: 'Time',
      render: (row) => (row.schedule ? `${formatTime(row.schedule.start_time)} – ${formatTime(row.schedule.end_time)}` : '—'),
    },
    { key: 'room', label: 'Room', render: (row) => row.schedule?.room ?? '—' },
    {
      key: 'actions',
      label: 'Action',
      className: 'text-end',
      render: (row) => (
        <div className="actions justify-content-end">
          <IconButton icon="bi-pencil" title="Edit schedule" onClick={() => setEditing(row)} />
          <IconButton icon="bi-trash" title="Remove" variant="delete" onClick={() => setRemoving(row)} />
        </div>
      ),
    },
  ]

  return (
    <div className="container-xl dash-wrap">
      <EmployeePageHeader employee={employee} section="Subjects & schedule" />

      <PanelCard
        title="Assigned Subjects"
        actions={
          <>
            <SearchInput value={search} onChange={setSearch} placeholder="Search code, name, day or room..." />
            <button type="button" className="btn btn-primary btn-sm" onClick={() => setEditing({})}>
              <i className="bi bi-plus-circle" /> Assign Subject
            </button>
          </>
        }
      >
        <DataTable columns={columns} rows={assignments} loading={loading} error={error} emptyMessage="No subjects assigned yet." />
      </PanelCard>

      <AssignmentModal
        employeeId={employeeId}
        assignment={editing}
        onClose={() => setEditing(null)}
        onSaved={(message) => {
          notify(message)
          setEditing(null)
          reload()
        }}
      />

      <ConfirmDialog
        show={Boolean(removing)}
        title="Remove subject"
        message={`Remove ${removing?.subject.subject_code} and its schedule from ${employee?.first_name ?? 'this employee'}?`}
        confirmLabel="Remove"
        onConfirm={removeAssignment}
        onClose={() => setRemoving(null)}
      />
    </div>
  )
}
