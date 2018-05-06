import state from './state'
import mutations from './mutations'
import actions from './actions'
import getters from './getters'

import { genStoreFields } from '@/helpers'

/*
  Generate mutation/action/getters for profile fields
*/
let fields = genStoreFields([
  'keyField',
  'fillParentCategories',
  'skipFirstLine',
  'store',
  'language',
  'importMode',
  'encoding',
  'defaultStatus',
  'categoryDelimiter',
  'imagesImportMode',
  'categoryLevelDelimiter',
  'processAtStepNum',
  'downloadImages',
  'clearGalleryBeforeImport',
  'previewFromGallery',
  'csvImageFieldDelimiter',
  'csvFieldDelimiter',
  'removeCharsFromCategory',
  'removeManufacturersBeforeImport',
  'defaultAttributesGroup',
  'attributesCSVField'
], 'profile')

export default {
  namespaced: true,
  state,
  mutations: {
    ...fields.mutations,
    ...mutations
  },
  actions: {
    ...fields.actions,
    ...actions
  },
  getters: {
    ...fields.getters,
    ...getters
  }
}
