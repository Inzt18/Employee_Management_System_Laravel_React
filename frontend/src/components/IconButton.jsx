export default function IconButton({ icon, title, variant = 'edit', onClick }) {
  return (
    <button
      type="button"
      className={`icon-btn ${variant}`}
      title={title}
      aria-label={title}
      onClick={(event) => {
        event.stopPropagation()
        onClick()
      }}
    >
      <i className={`bi ${icon}`} />
    </button>
  )
}
