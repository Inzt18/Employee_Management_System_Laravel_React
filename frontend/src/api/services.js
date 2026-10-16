import client from './client'

const data = (response) => response.data
const unwrap = (response) => response.data.data

/**
 * Builds multipart form data for requests that carry file uploads.
 */
function toFormData(values) {
  const formData = new FormData()
  Object.entries(values).forEach(([key, value]) => {
    if (value !== null && value !== undefined && value !== '') {
      formData.append(key, value)
    }
  })
  return formData
}

export const authApi = {
  login: (credentials) => client.post('/auth/login', credentials).then(data),
  register: (values) => client.post('/auth/register', values).then(data),
  me: () => client.get('/auth/me').then(unwrap),
  logout: () => client.post('/auth/logout').then(data),
  providers: () => client.get('/auth/providers').then(data),
}

export const optionsApi = {
  get: () => client.get('/options').then(data),
}

export const attachmentApi = {
  /**
   * Fetches a protected file as a Blob using the bearer token.
   */
  blob: (attachment) => client.get(`/attachments/${attachment.id}`, { responseType: 'blob' }).then(data),

  /**
   * Saves a protected file to the user's device under its original name.
   */
  download: async (attachment) => {
    const url = URL.createObjectURL(await attachmentApi.blob(attachment))
    const link = document.createElement('a')
    link.href = url
    link.download = attachment.original_name
    link.click()
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  },

  /**
   * Downloads a protected file with the bearer token and opens it in a new tab.
   */
  open: async (attachment) => {
    const preview = window.open('', '_blank')
    let response
    try {
      response = await client.get(`/attachments/${attachment.id}`, { responseType: 'blob' })
    } catch (error) {
      preview?.close()
      throw error
    }
    const url = URL.createObjectURL(response.data)
    if (preview) {
      preview.location.href = url
    } else {
      window.location.assign(url)
    }
    setTimeout(() => URL.revokeObjectURL(url), 60_000)
  },
}

export const employeeApi = {
  dashboard: () => client.get('/me/dashboard').then(data),
  schedule: (search) => client.get('/me/schedule', { params: { search } }).then(unwrap),
  tasks: (search) => client.get('/me/tasks', { params: { search } }).then(unwrap),
  leaveRequests: (search) => client.get('/me/leave-requests', { params: { search } }).then(unwrap),
  resignationRequests: (search) => client.get('/me/resignation-requests', { params: { search } }).then(unwrap),
  subjectRequests: () => client.get('/me/subject-requests').then(unwrap),
  taskRequests: () => client.get('/me/task-requests').then(unwrap),
  submitLeave: (values) => client.post('/me/leave-requests', toFormData(values)).then(data),
  submitResignation: (values) => client.post('/me/resignation-requests', toFormData(values)).then(data),
  submitSubject: (values) => client.post('/me/subject-requests', values).then(data),
  submitTask: (values) => client.post('/me/task-requests', values).then(data),
}

export const adminApi = {
  dashboard: () => client.get('/admin/dashboard').then(data),

  employees: (search) => client.get('/admin/employees', { params: { search } }).then(unwrap),
  employee: (id) => client.get(`/admin/employees/${id}`).then(unwrap),
  createEmployee: (values) => client.post('/admin/employees', values).then(data),
  updateEmployee: (id, values) => client.put(`/admin/employees/${id}`, values).then(data),
  deleteEmployee: (id) => client.delete(`/admin/employees/${id}`).then(data),

  subjects: (search) => client.get('/admin/subjects', { params: { search } }).then(unwrap),
  createSubject: (values) => client.post('/admin/subjects', values).then(data),
  updateSubject: (id, values) => client.put(`/admin/subjects/${id}`, values).then(data),
  deleteSubject: (id) => client.delete(`/admin/subjects/${id}`).then(data),

  assignments: (employeeId, search) =>
    client.get(`/admin/employees/${employeeId}/subjects`, { params: { search } }).then(unwrap),
  availableSubjects: (employeeId) => client.get(`/admin/employees/${employeeId}/available-subjects`).then(unwrap),
  assignSubject: (employeeId, values) => client.post(`/admin/employees/${employeeId}/subjects`, values).then(data),
  updateAssignment: (id, values) => client.put(`/admin/assignments/${id}`, values).then(data),
  removeAssignment: (id) => client.delete(`/admin/assignments/${id}`).then(data),

  employeeTasks: (employeeId, search) =>
    client.get(`/admin/employees/${employeeId}/tasks`, { params: { search } }).then(unwrap),
  taskTemplates: () => client.get('/admin/task-templates').then(unwrap),
  createTask: (employeeId, values) => client.post(`/admin/employees/${employeeId}/tasks`, values).then(data),
  updateTask: (id, values) => client.put(`/admin/tasks/${id}`, values).then(data),
  deleteTask: (id) => client.delete(`/admin/tasks/${id}`).then(data),

  requests: (status) => client.get('/admin/requests', { params: { status } }).then(unwrap),
  reviewRequest: (request, values) =>
    client.patch(`/admin/requests/${request.request_type}/${request.id}`, values).then(data),
}
