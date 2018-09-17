import Vue from 'vue'
import {
  EXPORT_URL,
  SAVE_PROFILE_URL,
  SAVE_ALL_PROFILES_URL
} from '@/api/routes'

export default {
  async saveProfile (mkUrl, options) {
    return Vue.http.post(mkUrl(SAVE_PROFILE_URL), options)
  },

  async saveProfiles (mkUrl, profiles) {
    return Vue.http.post(mkUrl(SAVE_ALL_PROFILES_URL), {
      profiles
    })
  },

  async submitData (mkUrl, profile) {
    let request = new FormData()
    request.append('profile-json', JSON.stringify(profile))

    return Vue.http.post(mkUrl(EXPORT_URL), request, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  },

  async submitPart (_mkUrl, { callbackUrl, options }) {
    return Vue.http.post(callbackUrl, options)
  }
}
