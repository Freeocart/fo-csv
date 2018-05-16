/*
  Actions for exporter module
*/

import Vue from 'vue'
import commonActions from '@/store/common/actions'

export default {
  ...commonActions,
  async saveNewProfile ({ commit, state }, name) {
    try {
      await Vue.$api.exporter.saveProfile({
        name,
        profile: state.profile
      })

      commit('SAVE_NEW_PROFILE', name)
      commit('SET_CURRENT_PROFILE_NAME', name)
    }
    catch (e) {
      console.log(e)
      alert('error on profile saving!')
    }
  },
  async saveAllProfiles ({ commit }, profiles) {
    await Vue.$api.exporter.saveProfiles(profiles)

    commit('CLEAR_ALL_PROFILES')
    commit('SET_PROFILES', profiles)
  }
}
