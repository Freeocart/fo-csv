import { DEFAULT_PROFILE_NAME } from '@/config'

export default {
  importJob: {
    importJobWorking: false,
    importJobCurrent: 0,
    importJobTotal: 0
  },
  urls: {
    import: ''
  },
  currentProfile: DEFAULT_PROFILE_NAME,
  data: {},
  profile: {
    statusRewrites: {},
    stockStatusRewrites: {},
    multicolumnFields: []
  }
}
