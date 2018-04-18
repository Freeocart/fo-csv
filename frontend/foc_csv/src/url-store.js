export default class UrlStore {
  constructor (config) {
    this._config = config
  }

  actionUrl (action) {
    console.log(this._config)
    return `${this._config.baseUrl}${this._config.baseRoute}/${action}&${this._config.tokenName}=${this._config.token}`
  }
}

// import { Vuex } from 'vuex'

// export default function (config) {
//   // let urlStore = Vuex.Store({
//   //   state: {
//   //     ...config
//   //   },
//   //   getters: {
//   //     baseUrl (state) {
//   //       return state.baseUrl
//   //     },
//   //     baseRoute (state) {
//   //       return state.baseRoute
//   //     },
//   //     token (state) {
//   //       return state.token
//   //     }
//   //   }
//   // })

//   // return urlStore
//   class
// }
