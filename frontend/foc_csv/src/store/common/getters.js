/*
  Getters for both importer and exporter
*/
import { DEFAULT_PROFILE_NAME } from '@/config'

export default {
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
  encodings (state) {
    return state.data.encodings
  },
  dbFields (state) {
    return state.data.dbFields
  },
  languages (state) {
    return state.data.languages
  },
  stores (state) {
    return state.data.stores
  },
  profiles (state) {
    return state.data.profiles
  },
  statuses (state) {
    return state.data.statuses
  },
  stock_statuses (state) {
    return state.data.stock_statuses
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
  }
}
