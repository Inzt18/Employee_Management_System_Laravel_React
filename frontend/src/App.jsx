import { Navigate, Route, Routes } from 'react-router-dom'
import ProtectedRoute, { GuestRoute } from './components/ProtectedRoute'
import Spinner from './components/Spinner'
import { homePathFor, useAuth } from './context/AuthContext'
import AdminLayout from './layouts/AdminLayout'
import EmployeeLayout from './layouts/EmployeeLayout'
import AdminDashboardPage from './pages/admin/AdminDashboardPage'
import EmployeeSubjectsPage from './pages/admin/EmployeeSubjectsPage'
import EmployeesPage from './pages/admin/EmployeesPage'
import EmployeeTasksPage from './pages/admin/EmployeeTasksPage'
import RequestsPage from './pages/admin/RequestsPage'
import SubjectsPage from './pages/admin/SubjectsPage'
import GoogleCallbackPage from './pages/auth/GoogleCallbackPage'
import LoginPage from './pages/auth/LoginPage'
import RegisterPage from './pages/auth/RegisterPage'
import DashboardPage from './pages/employee/DashboardPage'
import LeaveRequestPage from './pages/employee/LeaveRequestPage'
import ResignationRequestPage from './pages/employee/ResignationRequestPage'
import SubjectRequestPage from './pages/employee/SubjectRequestPage'
import TaskRequestPage from './pages/employee/TaskRequestPage'
import NotFoundPage from './pages/NotFoundPage'

function HomeRedirect() {
  const { user, loading } = useAuth()
  if (loading) return <Spinner fullPage />
  return <Navigate to={user ? homePathFor(user) : '/login'} replace />
}

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<HomeRedirect />} />
      <Route path="/auth/callback" element={<GoogleCallbackPage />} />

      <Route element={<GuestRoute />}>
        <Route path="/login" element={<LoginPage />} />
        <Route path="/register" element={<RegisterPage />} />
      </Route>

      <Route element={<ProtectedRoute role="Employee" />}>
        <Route element={<EmployeeLayout />}>
          <Route path="/dashboard" element={<DashboardPage />} />
          <Route path="/requests/leave" element={<LeaveRequestPage />} />
          <Route path="/requests/resignation" element={<ResignationRequestPage />} />
          <Route path="/requests/subject" element={<SubjectRequestPage />} />
          <Route path="/requests/task" element={<TaskRequestPage />} />
        </Route>
      </Route>

      <Route path="/admin" element={<ProtectedRoute role="Admin" />}>
        <Route element={<AdminLayout />}>
          <Route index element={<AdminDashboardPage />} />
          <Route path="employees" element={<EmployeesPage />} />
          <Route path="employees/:employeeId/subjects" element={<EmployeeSubjectsPage />} />
          <Route path="employees/:employeeId/tasks" element={<EmployeeTasksPage />} />
          <Route path="subjects" element={<SubjectsPage />} />
          <Route path="requests" element={<RequestsPage />} />
        </Route>
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  )
}
