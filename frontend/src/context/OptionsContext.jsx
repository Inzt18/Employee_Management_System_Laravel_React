import { createContext, useContext, useEffect, useState } from 'react'
import { optionsApi } from '../api/services'

const EMPTY_OPTIONS = {
  departments: [],
  genders: [],
  roles: [],
  employment_statuses: [],
  task_statuses: [],
  days: [],
  leave_types: [],
  resignation_documents: [],
  upload: { max_kilobytes: 5120, extensions: [] },
}

const OptionsContext = createContext(EMPTY_OPTIONS)

/**
 * Loads dropdown values (departments, leave types and their documents, ...) once from the API.
 */
export function OptionsProvider({ children }) {
  const [options, setOptions] = useState(EMPTY_OPTIONS)

  useEffect(() => {
    optionsApi
      .get()
      .then(setOptions)
      .catch(() => setOptions(EMPTY_OPTIONS))
  }, [])

  return <OptionsContext.Provider value={options}>{children}</OptionsContext.Provider>
}

export function useOptions() {
  return useContext(OptionsContext)
}
