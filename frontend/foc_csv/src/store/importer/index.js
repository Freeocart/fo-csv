import state from './state'
import mutations from './mutations'
import actions from './actions'
import getters from './getters'

import { genVuexModels } from 'vuex-models'

/*
  Generate mutation/action/getters for profile fields
*/
let fields = genVuexModels([
  'keyField',
  'fillParentCategories',
  'skipLines',
  'csvWithoutHeaders',
  'csvHeadersLineNumber',
  'store',
  'language',
  'importMode',
  'encoding',
  'defaultStatus',
  'defaultStockStatus',
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
  'attributesCSVField',
  'skipLineOnEmptyFields',
  'multicolumnFields',
  'replaceAttributes'
], 'profile')

let importProgress = genVuexModels([
  'importJobTotal',
  'importJobCurrent',
  'importJobWorking'
], 'importJob')

export default {
  namespaced: true,
  state,
  mutations: {
    ...fields.mutations,
    ...importProgress.mutations,
    ...mutations
  },
  actions: {
    ...fields.actions,
    ...importProgress.actions,
    ...actions
  },
  getters: {
    ...fields.getters,
    ...importProgress.getters,
    ...getters
  }
}
