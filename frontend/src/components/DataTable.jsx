import Spinner from './Spinner'

/**
 * Table with built-in loading, error and empty states.
 *
 * columns: [{ key, label, render?: (row) => node, className? }]
 */
export default function DataTable({ columns, rows, loading, error, emptyMessage = 'No records found.', onRowClick, rowKey = 'id' }) {
  const renderState = (content) => (
    <tr>
      <td colSpan={columns.length} className="empty-state">
        {content}
      </td>
    </tr>
  )

  let body
  if (loading && !rows?.length) {
    body = renderState(<Spinner />)
  } else if (error) {
    body = renderState(<span className="text-danger">{error}</span>)
  } else if (!rows?.length) {
    body = renderState(emptyMessage)
  } else {
    body = rows.map((row) => (
      <tr
        key={row[rowKey]}
        onClick={onRowClick ? () => onRowClick(row) : undefined}
        className={onRowClick ? 'clickable-row' : undefined}
      >
        {columns.map((column) => (
          <td key={column.key} className={column.className}>
            {column.render ? column.render(row) : row[column.key]}
          </td>
        ))}
      </tr>
    ))
  }

  return (
    <div className="table-responsive">
      <table className="table app-table">
        <thead>
          <tr>
            {columns.map((column) => (
              <th key={column.key} className={column.className}>
                {column.label}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>{body}</tbody>
      </table>
    </div>
  )
}
