import Vue from 'vue'

import {
  IMPORT_URL,
  SAVE_IMPORT_PROFILE_URL,
  SAVE_ALL_IMPORT_PROFILES_URL
} from '@/api/routes'

export default {

  async saveProfile (mkUrl, options) {
    return Vue.http.post(mkUrl(SAVE_IMPORT_PROFILE_URL), options)
  },

  async saveProfiles (mkUrl, profiles) {
    return Vue.http.post(mkUrl(SAVE_ALL_IMPORT_PROFILES_URL), {
      profiles
    })
  },

  async submitData (mkUrl, { data, profile }) {
    let request = new FormData()
    request.append('csv-file', data.csvFileRef.files[0])

    if (data.imagesZipFileRef && data.imagesZipFileRef.files.length > 0) {
      request.append('images-zip', data.imagesZipFileRef.files[0])
    }

    request.append('profile-json', JSON.stringify(profile))

    return Vue.http.post(mkUrl(IMPORT_URL), request, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
  }

}
