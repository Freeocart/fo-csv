import { DEFAULT_PROFILE_NAME } from '@/config'

export default {
  data: {},
  profile: {},
  exportJob: {
    exportJobWorking: false,
    exportJobCurrent: 0,
    exportJobTotal: 0
  },
  currentProfile: DEFAULT_PROFILE_NAME
}
