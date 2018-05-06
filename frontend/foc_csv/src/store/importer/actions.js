import Vue from 'vue'
import { DEFAULT_PROFILE_NAME, SAVE_PROFILE_URL, SAVE_ALL_PROFILES_URL } from '@/config'

export default {
  setInitialData ({ commit, getters }, data) {
    commit('SET_INITIAL_DATA', data)
    commit('SET_CURRENT_PROFILE_NAME', DEFAULT_PROFILE_NAME)
    commit('SET_CURRENT_PROFILE', getters.currentProfile)
  },
  setCurrentProfileName ({ commit, getters }, profile) {
    commit('SET_CURRENT_PROFILE_NAME', profile)
    commit('SET_CURRENT_PROFILE', getters.currentProfile)
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
      await Vue.http.post(this.actionUrl(SAVE_PROFILE_URL), {
        name,
        profile: state.profile
      })

      commit('SAVE_NEW_PROFILE', name)
      commit('SET_CURRENT_PROFILE_NAME', name)
    }
    catch (e) {
      alert('error on profile saving!')
    }
  },
  applyProfile ({ commit }, { name, profile }) {
    commit('ADD_PROFILE', { name, profile })
    commit('SET_CURRENT_PROFILE_NAME', name)
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
  async saveAllProfiles ({ commit }, profiles) {
    await Vue.http.post(this.actionUrl(SAVE_ALL_PROFILES_URL), {
      profiles
    })
    commit('CLEAR_ALL_PROFILES')
    commit('SET_PROFILES', profiles)
  },
  deleteProfile ({ commit }, name) {
    commit('DELETE_PROFILE', name)
  }
}
