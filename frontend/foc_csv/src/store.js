import Vue from 'vue'
import Vuex from 'vuex'

import axios from 'axios'

Vue.use(Vuex)

const DEFAULT_PROFILE_NAME = 'default'

const store = new Vuex.Store({
  state: {
    urls: {
      import: ''
    },
    currentProfile: 'default',
    data: {},
    profile: {}
  },
  actions: {
    setInitialData ({ commit }, data) {
      commit('SET_INITIAL_DATA', data)
      commit('SET_CURRENT_PROFILE', DEFAULT_PROFILE_NAME)
    },
    setKeyField ({ commit }, keyField) {
      commit('SET_CURRENT_KEY_FIELD', keyField)
    },
    setSkipFirstLine ({ commit }, val) {
      commit('SET_SKIP_FIRST_LINE', val)
    },
    setCurrentProfileName ({ commit }, profile) {
      commit('SET_CURRENT_PROFILE', profile)
    },
    setCsvFieldNames ({ commit }, fieldNames) {
      commit('SET_CSV_FIELD_NAMES', fieldNames)
    },
    setCategoryDelimiter ({ commit }, delimiter) {
      commit('SET_CATEGORY_DELIMITER', delimiter)
    },
    setCsvFieldDelimiter ({ commit }, delimiter) {
      commit('SET_CSVFIELD_DELIMITER', delimiter)
    },
    setEncoding ({ commit }, encoding) {
      commit('SET_ENCODING', encoding)
    },
    bindDBToCsvField ({ commit }, fields) {
      commit('BIND_DB_TO_CSV_FIELD', fields)
    },
    setCsvFileRef ({ commit }, ref) {
      commit('SET_CSV_FILE_REF', ref)
    },
    setImagesZipRef ({ commit }, ref) {
      commit('SET_IMAGES_ZIP_FILE_REF', ref)
    },
    setDownloadImages ({ commit }, download/* ? */) {
      commit('SET_DOWNLOAD_IMAGES', download)
    },
    setImportMode ({ commit }, mode) {
      commit('SET_IMPORT_MODE', mode)
    },
    setCsvImageFieldDelimiter ({ commit }, delimiter) {
      commit('SET_CSV_IMAGE_FIELD_DELIMITER', delimiter)
    },
    setProcessAtStepNum ({ commit }, num) {
      commit('SET_PROCESS_AT_STEP_NUM', num)
    },
    setImagesImportMode ({ commit }, mode) {
      commit('SET_IMAGES_IMPORT_MODE', mode)
    },
    setPreviewFromGallery ({ commit }, toggle) {
      commit('SET_PREVIEW_FROM_GALLERY', toggle)
    },
    setClearGalleryBeforeImport ({ commit }, toggle) {
      commit('SET_CLEAR_GALLERY_BEFORE_IMPORT', toggle)
    },
    setCategoryLevelDelimiter ({ commit }, delimiter) {
      commit('SET_CATEGORY_LEVEL_DELIMITER', delimiter)
    },
    setStore ({ commit }, store) {
      commit('SET_STORE', store)
    },
    setLanguage ({ commit }, language) {
      commit('SET_LANGUAGE', language)
    },
    async saveNewProfile ({ commit, state }, name) {
      try {
        await axios.post(this.actionUrl('saveProfile'), {
          name,
          profile: state.profile
        })

        commit('SAVE_NEW_PROFILE', name)
      }
      catch (e) {
        alert('error on profile saving!')
      }
    }
  },
  mutations: {
    SET_INITIAL_DATA (state, initial) {
      Vue.set(state, 'data', initial.data)
    },
    SET_CURRENT_PROFILE (state, profile) {
      state.currentProfile = profile
      state.profile = this.getters.currentProfile
    },
    SET_PROCESS_AT_STEP_NUM (state, num) {
      Vue.set(state.profile, 'processAtStepNum', num)
    },
    SET_STORE (state, storeId) {
      state.profile.storeId = storeId
    },
    SET_LANGUAGE (state, langId) {
      state.profile.languageId = langId
    },
    SET_SKIP_FIRST_LINE (state, val) {
      Vue.set(state.profile, 'skipFirstLine', val)
    },
    SAVE_NEW_PROFILE (state, name) {
      let profileSettings = Object.assign({}, state.profile)
      Vue.set(state.data.profiles, name, profileSettings)
    },
    SET_CURRENT_KEY_FIELD (state, field) {
      Vue.set(state.profile, 'keyField', field)
    },
    SET_CSV_FIELD_NAMES (state, fields) {
      state.data.csvFields = fields
    },
    SET_CATEGORY_DELIMITER (state, delimiter) {
      state.profile.categoryDelimiter = delimiter
    },
    SET_CATEGORY_LEVEL_DELIMITER (state, delimiter) {
      state.profile.categoryLevelDelimiter = delimiter
    },
    SET_CSVFIELD_DELIMITER (state, delimiter) {
      state.profile.csvFieldDelimiter = delimiter
    },
    SET_ENCODING (state, encoding) {
      state.profile.encoding = encoding
    },
    BIND_DB_TO_CSV_FIELD (state, [ dbField, csvField ]) {
      Vue.set(state.profile.bindings, csvField, dbField)
    },
    SET_CSV_FILE_REF (state, ref) {
      Vue.set(state.data, 'csvFileRef', ref)
    },
    SET_IMAGES_ZIP_FILE_REF (state, ref) {
      Vue.set(state.data, 'imagesZipFileRef', ref)
    },
    SET_DOWNLOAD_IMAGES (state, download) {
      Vue.set(state.profile, 'downloadImages', download)
    },
    SET_IMPORT_MODE (state, mode) {
      Vue.set(state.profile, 'importMode', mode)
    },
    SET_CSV_IMAGE_FIELD_DELIMITER (state, delimiter) {
      Vue.set(state.profile, 'csvImageFieldDelimiter', delimiter)
    },
    SET_IMAGES_IMPORT_MODE (state, mode) {
      Vue.set(state.profile, 'imagesImportMode', mode)
    },
    SET_PREVIEW_FROM_GALLERY (state, toggle) {
      Vue.set(state.profile, 'previewFromGallery', toggle)
    },
    SET_CLEAR_GALLERY_BEFORE_IMPORT (state, toggle) {
      Vue.set(state.profile, 'clearGalleryBeforeImport', toggle)
    }
  },
  getters: {
    dbFields (state) {
      return state.data.dbFields
    },
    csvFields (state) {
      return state.data.csvFields
    },
    encodings (state) {
      return state.data.encodings
    },
    profiles (state) {
      return state.data.profiles
    },
    keyFields (state) {
      return state.data.keyFields
    },
    languages (state) {
      return state.data.languages
    },
    stores (state) {
      return state.data.stores
    },
    keyField (state) {
      return state.profile.keyField
    },
    currentProfileName (state) {
      return state.currentProfile
    },
    processAtStepNum (state) {
      return state.profile.processAtStepNum
    },
    currentProfile (state) {
      let profileData = state.data.profiles[state.currentProfile]

      if (!profileData) {
        profileData = state.data.profiles[DEFAULT_PROFILE_NAME]
      }

      return profileData
    },
    profile (state) {
      return state.profile
    },
    store (state) {
      return state.profile.storeId
    },
    language (state) {
      return state.profile.languageId
    },
    csvFieldDelimiter (state) {
      return state.profile.csvFieldDelimiter
    },
    categoryDelimiter (state) {
      return state.profile.categoryDelimiter
    },
    categoryLevelDelimiter (state) {
      return state.profile.categoryLevelDelimiter
    },
    encoding (state) {
      return state.profile.encoding
    },
    downloadImages (state) {
      return state.profile.downloadImages
    },
    importMode (state) {
      return state.profile.importMode
    },
    imagesImportMode (state) {
      return state.profile.imagesImportMode
    },
    csvImageFieldDelimiter (state) {
      return state.profile.csvImageFieldDelimiter
    },
    skipFirstLine (state) {
      return state.profile.skipFirstLine
    },
    previewFromGallery (state) {
      return state.profile.previewFromGallery
    },
    clearGalleryBeforeImport (state) {
      return state.profile.clearGalleryBeforeImport
    },
    submittableData (state) {
      return {
        profile: state.profile,
        data: state.data
      }
    }
  }
})

export default store
