export default function SearchInput({ value, onChange, placeholder = 'Search...' }) {
  return (
    <div className="search-wrap">
      <i className="bi bi-search" />
      <input
        type="search"
        className="form-control form-control-sm search-input"
        value={value}
        placeholder={placeholder}
        onChange={(event) => onChange(event.target.value)}
      />
    </div>
  )
}
