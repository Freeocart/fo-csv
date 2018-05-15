import state from './state'
import mutations from './mutations'
import actions from './actions'
import getters from './getters'

import { genStoreFields } from '@/helpers'

let fields = genStoreFields([
  'entriesPerQuery',
  'encoding',
  'dumpParentCategories',
  'categoriesNestingDelimiter',
  'categoriesDelimiter',
  'exportImagesMode',
  'createImagesZIP',
  'galleryImagesDelimiter',
  'csvFieldDelimiter',
  'store',
  'language',
  'csvFieldDelimiter',
  'csvHeader',
  'bindings'
], 'profile')

let exportProgress = genStoreFields([
  'exportJobTotal',
  'exportJobCurrent',
  'exportJobWorking'
], 'exportJob')

export default {
  namespaced: true,
  state,
  mutations: {
    ...mutations,
    ...fields.mutations,
    ...exportProgress.mutations
  },
  actions: {
    ...actions,
    ...fields.actions,
    ...exportProgress.actions
  },
  getters: {
    ...getters,
    ...fields.getters,
    ...exportProgress.getters
  }
}
