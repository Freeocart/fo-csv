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
  },
  SAVE_NEW_PROFILE (state, name) {
    let profileSettings = Object.assign({}, state.profile)
    Vue.set(state.data.profiles, name, profileSettings)
  },
  ADD_PROFILE (state, { name, profile }) {
    Vue.set(state.data.profiles, name, profile)
  },
  DELETE_PROFILE (state, name) {
    // console.log(name, state.data.profiles[name])
    if (state.data.profiles[name]) {
      Vue.delete(state.data.profiles, name)
    }
  },
  SET_PROFILES (state, profiles) {
    Vue.set(state.data, 'profiles', profiles)
  },
  CLEAR_ALL_PROFILES (state) {
    Vue.set(state.data, 'profiles', {})
  }
}
