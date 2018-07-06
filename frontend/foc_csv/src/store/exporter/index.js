import state from './state'
import mutations from './mutations'
import actions from './actions'
import getters from './getters'

import { genVuexModels } from 'vuex-models'

let fields = genVuexModels([
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

let exportProgress = genVuexModels([
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
