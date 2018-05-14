import Vue from 'vue'

export default {
  SET_INITIAL_DATA (state, initial) {
    Vue.set(state, 'data', initial)
  },
  SET_CURRENT_PROFILE_NAME (state, profileName) {
    state.currentProfile = profileName
  },
  SET_CURRENT_PROFILE (state, profile) {
    state.profile = profile
  }
}
