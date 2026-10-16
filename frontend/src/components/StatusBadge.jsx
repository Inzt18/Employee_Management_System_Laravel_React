export default function StatusBadge({ status }) {
  const label = status || 'Unknown'
  return <span className={`badge-status ${label.toLowerCase().replace(/\s+/g, '-')}`}>{label}</span>
}
