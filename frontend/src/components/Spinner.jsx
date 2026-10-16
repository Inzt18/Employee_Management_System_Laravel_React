export default function Spinner({ fullPage = false, label = 'Loading...' }) {
  const spinner = (
    <div className="d-flex align-items-center justify-content-center gap-2 text-muted py-4">
      <span className="spinner-border spinner-border-sm" role="status" aria-hidden="true" />
      <span>{label}</span>
    </div>
  )

  return fullPage ? <div className="full-page-center">{spinner}</div> : spinner
}
