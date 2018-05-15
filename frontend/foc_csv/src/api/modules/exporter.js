import Vue from 'vue'
import {
  SAVE_PROFILE_URL
} from '@/api/routes'

export default {
  async saveProfile (mkUrl, options) {
    return Vue.http.post(mkUrl(SAVE_PROFILE_URL), options)
  }
}
