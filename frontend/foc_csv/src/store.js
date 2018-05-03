import Vue from 'vue'
import Vuex from 'vuex'

import axios from 'axios'
import { genStoreFields } from '@/helpers'

import { SAVE_PROFILE_URL } from '@/urls'

Vue.use(Vuex)

let fields = genStoreFields([
  'keyField',
  'fillParentCategories',
  'skipFirstLine',
  'store',
  'language',
  'importMode',
  'encoding',
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

const DEFAULT_PROFILE_NAME = 'default'

const store = new Vuex.Store({
  state: {
    urls: {
      import: ''
    },
    currentProfile: 'default',
    data: {},
    profile: {
      statusRewrites: {},
      stockStatusRewrites: {}
    }
  },
  actions: {
    setInitialData ({ commit }, data) {
      commit('SET_INITIAL_DATA', data)
      commit('SET_CURRENT_PROFILE', DEFAULT_PROFILE_NAME)
    },
    setCurrentProfileName ({ commit }, profile) {
      commit('SET_CURRENT_PROFILE', profile)
    },
    setCsvFieldNames ({ commit }, fieldNames) {
      commit('SET_CSV_FIELD_NAMES', fieldNames)
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
    async saveNewProfile ({ commit, state }, name) {
      try {
        await axios.post(this.actionUrl(SAVE_PROFILE_URL), {
          name,
          profile: state.profile
        })

        commit('SAVE_NEW_PROFILE', name)
      }
      catch (e) {
        alert('error on profile saving!')
      }
    },
    setStockStatusRewriteRule ({ commit }, rule) {
      commit('SET_STOCK_STATUS_REWRITE_RULE', rule)
    },
    setStatusRewriteRule ({ commit }, rule) {
      commit('SET_STATUS_REWRITE_RULE', rule)
    },
    setAttributeParser ({ commit }, parser) {
      commit('SET_ATTRIBUTE_PARSER', parser)
    },
    setAttributeParserData ({ commit }, data) {
      commit('SET_ATTRIBUTE_PARSER_DATA', data)
    },
    ...fields.actions
  },
  mutations: {
    SET_INITIAL_DATA (state, initial) {
      Vue.set(state, 'data', initial.data)
    },
    SET_STOCK_STATUS_REWRITE_RULE (state, { value, id }) {
      if (!state.profile.stockStatusRewrites) {
        Vue.set(state.profile, 'stockStatusRewrites', {})
      }
      Vue.set(state.profile.stockStatusRewrites, id, value)
    },
    SET_STATUS_REWRITE_RULE (state, { value, id }) {
      if (!state.profile.statusRewrites) {
        Vue.set(state.profile, 'statusRewrites', {})
      }
      Vue.set(state.profile.statusRewrites, id, value)
    },
    SET_CURRENT_PROFILE (state, profile) {
      state.currentProfile = profile
      state.profile = this.getters.currentProfile
    },
    SET_PROCESS_AT_STEP_NUM (state, num) {
      Vue.set(state.profile, 'processAtStepNum', num)
    },
    SAVE_NEW_PROFILE (state, name) {
      let profileSettings = Object.assign({}, state.profile)
      Vue.set(state.data.profiles, name, profileSettings)
    },
    SET_CSV_FIELD_NAMES (state, fields) {
      state.data.csvFields = fields
    },
    SET_CATEGORY_DELIMITER (state, delimiter) {
      state.profile.categoryDelimiter = delimiter
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
    SET_ATTRIBUTE_PARSER (state, parser) {
      Vue.set(state.profile, 'attributeParser', parser)

      if (!parser) {
        return
      }

      if (!state.profile.attributeParserData || !state.profile.attributeParserData[parser]) {
        Vue.set(state.profile, 'attributeParserData', {
          [parser]: {}
        })
      }

      let parserObj = state.data.attributeParsers[parser]

      if (parserObj.options) {
        for (let key in parserObj.options) {
          if (!state.profile.attributeParserData[parser][key] && parserObj.options[key].default) {
            Vue.set(state.profile.attributeParserData[parser], key, parserObj.options[key].default)
          }
        }
      }
    },
    SET_ATTRIBUTE_PARSER_DATA (state, [ key, value ]) {
      let parser = state.profile.attributeParser
      Vue.set(state.profile.attributeParserData[parser], key, value)
    },
    ...fields.mutations
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
    attributeParsers (state) {
      return state.data.attributeParsers
    },
    currentAttributeParser (state) {
      return state.profile.attributeParser
    },
    attributeParserOptions (state) {
      let parser = state.profile.attributeParser

      if (!parser || !state.data.attributeParsers[parser]) {
        return []
      }

      if (state.data.attributeParsers[parser].options) {
        return state.data.attributeParsers[parser].options
      }

      return []
    },
    attributeParserOptionData (state) {
      let parser = state.profile.attributeParser
      return state.profile.attributeParserData[parser] || []
    },
    currentProfileName (state) {
      return state.currentProfile
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
    categoryDelimiter (state) {
      return state.profile.categoryDelimiter
    },
    statusRewrites (state) {
      return state.profile.statusRewrites || {}
    },
    stockStatusRewrites (state) {
      return state.profile.stockStatusRewrites || {}
    },
    submittableData (state) {
      return {
        profile: state.profile,
        data: state.data
      }
    },
    ...fields.getters
  }
})

export default store
