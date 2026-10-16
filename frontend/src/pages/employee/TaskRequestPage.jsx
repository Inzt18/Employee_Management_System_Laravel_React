import { employeeApi } from '../../api/services'
import { SelectField, TextAreaField, TextField } from '../../components/form/FormFields'
import { useOptions } from '../../context/OptionsContext'
import useForm from '../../hooks/useForm'
import { todayIso } from '../../utils/format'
import RequestFormLayout from './RequestFormLayout'

export default function TaskRequestPage() {
  const { task_statuses: taskStatuses } = useOptions()
  const form = useForm({ task_title: '', task_description: '', due_date: '', task_status: 'Pending' })
  const { values, errors, handleChange } = form

  return (
    <RequestFormLayout
      form={form}
      onSubmit={employeeApi.submitTask}
      hero={{
        icon: 'bi-list-task',
        tone: 'task',
        title: 'Request a Task',
        description: 'This will be sent to admin for approval before it appears on your dashboard.',
      }}
    >
      <TextField
        label="Task Title"
        name="task_title"
        placeholder="What needs to be done?"
        value={values.task_title}
        onChange={handleChange}
        error={errors.task_title}
        required
      />
      <TextAreaField
        label="Description"
        name="task_description"
        rows={4}
        placeholder="Add context, deliverables, or notes"
        value={values.task_description}
        onChange={handleChange}
        error={errors.task_description}
        required
      />
      <div className="row">
        <TextField
          label="Due Date"
          name="due_date"
          type="date"
          className="col-md-6 mb-3"
          min={todayIso()}
          value={values.due_date}
          onChange={handleChange}
          error={errors.due_date}
          required
        />
        <SelectField
          label="Status"
          name="task_status"
          className="col-md-6 mb-3"
          options={taskStatuses}
          value={values.task_status}
          onChange={handleChange}
          error={errors.task_status}
          required
        />
      </div>
    </RequestFormLayout>
  )
}
