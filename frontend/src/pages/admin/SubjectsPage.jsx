import { useEffect, useState } from 'react'
import { adminApi } from '../../api/services'
import ConfirmDialog from '../../components/ConfirmDialog'
import DataTable from '../../components/DataTable'
import IconButton from '../../components/IconButton'
import Modal from '../../components/Modal'
import PanelCard from '../../components/PanelCard'
import SearchInput from '../../components/SearchInput'
import { FormAlert, TextField } from '../../components/form/FormFields'
import { useToast } from '../../context/ToastContext'
import useApi from '../../hooks/useApi'
import useDebounce from '../../hooks/useDebounce'
import useForm from '../../hooks/useForm'
import { parseApiError } from '../../utils/errors'

const EMPTY_SUBJECT = { subject_code: '', subject_name: '', units: '' }

function SubjectFormModal({ subject, onClose, onSaved }) {
  const { values, errors, message, submitting, handleChange, submit, reset } = useForm(EMPTY_SUBJECT)
  const isEdit = Boolean(subject?.id)

  useEffect(() => {
    if (subject) reset(subject.id ? { subject_code: subject.subject_code, subject_name: subject.subject_name, units: subject.units } : EMPTY_SUBJECT)
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [subject])

  const handleSubmit = async (event) => {
    event.preventDefault()
    const response = await submit((data) => (isEdit ? adminApi.updateSubject(subject.id, data) : adminApi.createSubject(data)))
    if (response) onSaved(response.message)
  }

  return (
    <Modal
      show={Boolean(subject)}
      title={isEdit ? 'Edit Subject' : 'Add Subject'}
      onClose={onClose}
      footer={
        <>
          <button type="button" className="btn btn-ghost" onClick={onClose}>
            Cancel
          </button>
          <button type="submit" form="subject-form" className="btn btn-primary" disabled={submitting}>
            {submitting ? 'Saving...' : 'Save'}
          </button>
        </>
      }
    >
      <FormAlert message={message} />
      <form id="subject-form" onSubmit={handleSubmit} noValidate>
        <TextField label="Subject Code" name="subject_code" value={values.subject_code} onChange={handleChange} error={errors.subject_code} required />
        <TextField label="Subject Name" name="subject_name" value={values.subject_name} onChange={handleChange} error={errors.subject_name} required />
        <TextField label="Units" name="units" type="number" min={1} max={10} value={values.units} onChange={handleChange} error={errors.units} required />
      </form>
    </Modal>
  )
}

export default function SubjectsPage() {
  const { notify } = useToast()
  const [search, setSearch] = useState('')
  const debouncedSearch = useDebounce(search)
  const { data: subjects, loading, error, reload } = useApi(() => adminApi.subjects(debouncedSearch), [debouncedSearch], [])
  const [editing, setEditing] = useState(null)
  const [deleting, setDeleting] = useState(null)

  const deleteSubject = async () => {
    try {
      const response = await adminApi.deleteSubject(deleting.id)
      notify(response.message)
      setDeleting(null)
      reload()
    } catch (err) {
      notify(parseApiError(err).message, 'danger')
    }
  }

  const columns = [
    { key: 'subject_code', label: 'Code', render: (row) => <strong>{row.subject_code}</strong> },
    { key: 'subject_name', label: 'Name' },
    { key: 'units', label: 'Units' },
    { key: 'employees_count', label: 'Assigned To', render: (row) => `${row.employees_count} employee(s)` },
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
      <PanelCard
        title="Subjects"
        subtitle="The subject catalog that can be assigned to employees."
        actions={
          <>
            <SearchInput value={search} onChange={setSearch} placeholder="Search by code or name..." />
            <button type="button" className="btn btn-primary btn-sm" onClick={() => setEditing({})}>
              <i className="bi bi-plus-circle" /> Add Subject
            </button>
          </>
        }
      >
        <DataTable columns={columns} rows={subjects} loading={loading} error={error} emptyMessage="No subjects found." />
      </PanelCard>

      <SubjectFormModal
        subject={editing}
        onClose={() => setEditing(null)}
        onSaved={(message) => {
          notify(message)
          setEditing(null)
          reload()
        }}
      />

      <ConfirmDialog
        show={Boolean(deleting)}
        title="Delete subject"
        message={`Delete ${deleting?.subject_code}? It will also be removed from ${deleting?.employees_count ?? 0} employee(s).`}
        onConfirm={deleteSubject}
        onClose={() => setDeleting(null)}
      />
    </div>
  )
}
