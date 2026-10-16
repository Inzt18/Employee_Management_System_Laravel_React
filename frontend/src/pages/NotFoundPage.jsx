import { Link } from 'react-router-dom'

export default function NotFoundPage() {
  return (
    <div className="full-page-center">
      <div className="text-center">
        <div className="display-4 fw-bold text-primary">404</div>
        <p className="text-muted">The page you are looking for does not exist.</p>
        <Link to="/" className="btn btn-primary">
          Go home
        </Link>
      </div>
    </div>
  )
}
