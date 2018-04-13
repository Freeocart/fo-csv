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
    SET_CSVFIELD_DELIMITER (state, delimiter) {
      state.profile.csvFieldDelimiter = delimiter
    },
    SET_ENCODING (state, encoding) {
      state.profile.encoding = encoding
    },
    BIND_DB_TO_CSV_FIELD (state, [ dbField, csvField ]) {
      Vue.set(state.profile.bindings, dbField, csvField)
    },
    SET_CSV_FILE_REF (state, ref) {
      Vue.set(state.data, 'csvFileRef', ref)
    },
    SET_DOWNLOAD_IMAGES (state, download) {
      Vue.set(state.profile, 'downloadImages', download)
    },
    SET_IMPORT_MODE (state, mode) {
      Vue.set(state.profile, 'importMode', mode)
    },
    SET_CSV_IMAGE_FIELD_DELIMITER (state, delimiter) {
      Vue.set(state.profile, 'csvImageFieldDelimiter', delimiter)
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
    csvFieldDelimiter (state) {
      return state.profile.csvFieldDelimiter
    },
    categoryDelimiter (state) {
      return state.profile.categoryDelimiter
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
    csvImageFieldDelimiter (state) {
      return state.profile.csvImageFieldDelimiter
    },
    skipFirstLine (state) {
      return state.profile.skipFirstLine
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
