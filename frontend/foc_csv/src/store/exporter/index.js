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
  'language'
], 'profile')

export default {
  namespaced: true,
  state,
  mutations: {
    ...mutations,
    ...fields.mutations
  },
  actions: {
    ...actions,
    ...fields.actions
  },
  getters: {
    ...getters,
    ...fields.getters
  }
}
